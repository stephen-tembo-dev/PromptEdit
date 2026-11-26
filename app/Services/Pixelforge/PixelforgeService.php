<?php

namespace App\Services\Pixelforge;

use App\Enums\Generations\GenerationStatus;
use App\Models\Generations\Generation;
use App\Support\Data\Pixelforge\GenerationPage;
use App\Support\Data\Pixelforge\GenerationSnapshot;
use App\Support\Data\Pixelforge\UsageDay;
use App\Support\Exceptions\Pixelforge\PixelforgeConnectionException;
use App\Support\Exceptions\Pixelforge\PixelforgeRequestException;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * The only class that calls the Pixelforge API.
 *
 * If Pixelforge answers with an error, we throw PixelforgeRequestException.
 * If it doesn't answer at all, we throw PixelforgeConnectionException.
 */
class PixelforgeService
{
    public function __construct(
        #[Config('services.pixelforge.base_url')] private string $baseUrl,
        #[Config('services.pixelforge.api_key')] private ?string $apiKey,
    ) {}

    /**
     * Get Pixelforge's current price list. This endpoint doesn't need our API key.
     * We return a plain array so it's easy to cache.
     *
     * @return array<string, mixed>
     *
     * @throws PixelforgeRequestException|PixelforgeConnectionException
     */
    public function fetchPricing(): array
    {
        return $this->send(fn (): Response => $this->request()->get('pricing'))->json();
    }

    /**
     * Ask Pixelforge to make a picture.
     *
     * We send our uuid twice: as the Idempotency-Key, so a retry gets back the
     * same picture instead of a new one, and in the metadata, so webhooks can
     * tell us which picture they're about. Everything we send comes from the
     * database, so a retry always sends exactly the same request.
     *
     * @throws PixelforgeRequestException|PixelforgeConnectionException
     */
    public function createGeneration(Generation $generation): GenerationSnapshot
    {
        $body = [
            'prompt' => $generation->prompt,
            'size' => $generation->size->value,
            'quality' => $generation->quality->value,
            'metadata' => ['generation_id' => $generation->uuid],
        ];

        $referenceImages = $generation->referenceImages->pluck('url')->all();

        if ($referenceImages !== []) {
            $body['reference_images'] = $referenceImages;
        }

        $response = $this->send(fn (): Response => $this->authenticated()
            ->withHeaders(['Idempotency-Key' => $generation->uuid])
            ->post('generations', $body));

        return GenerationSnapshot::fromApi($response->json());
    }

    /**
     * Look up one picture by Pixelforge's id (the gen_ id).
     *
     * @throws PixelforgeRequestException|PixelforgeConnectionException
     */
    public function getGeneration(string $providerId): GenerationSnapshot
    {
        $response = $this->send(fn (): Response => $this->authenticated()->get('generations/'.rawurlencode($providerId)));

        return GenerationSnapshot::fromApi($response->json());
    }

    /**
     * Get one page of Pixelforge's pictures with the given status.
     * Pass the cursor from the last page to get the next one.
     *
     * @throws PixelforgeRequestException|PixelforgeConnectionException
     */
    public function listGenerations(GenerationStatus $status, ?string $cursor = null, int $limit = 100): GenerationPage
    {
        $response = $this->send(fn (): Response => $this->authenticated()->get('generations', array_filter([
            'status' => $status->value,
            'limit' => $limit,
            'cursor' => $cursor,
        ])));

        return new GenerationPage(
            generations: array_map(GenerationSnapshot::fromApi(...), $response->json('data', [])),
            hasMore: (bool) $response->json('has_more', false),
            nextCursor: $response->json('next_cursor'),
        );
    }

    /**
     * Get what Pixelforge says it charged us, one entry per UTC day, keyed by
     * date. Both dates are included, up to 92 days. Days with no use come back
     * as zeros.
     *
     * @return array<string, UsageDay>
     *
     * @throws PixelforgeRequestException|PixelforgeConnectionException
     */
    public function fetchUsage(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $response = $this->send(fn (): Response => $this->authenticated()->get('usage', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]));

        $days = array_map(UsageDay::fromApi(...), $response->json('data', []));

        return array_combine(array_map(fn (UsageDay $day): string => $day->date, $days), $days);
    }

    /**
     * A basic request to Pixelforge, with JSON and timeouts set.
     */
    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15);
    }

    /**
     * The same request, with our API key added.
     */
    private function authenticated(): PendingRequest
    {
        return $this->request()->withToken((string) $this->apiKey);
    }

    /**
     * Make the request and turn both kinds of failure into our own exceptions,
     * so the rest of the app never has to know about Laravel's HTTP client.
     *
     * @param  Closure(): Response  $request
     *
     * @throws PixelforgeRequestException|PixelforgeConnectionException
     */
    private function send(Closure $request): Response
    {
        try {
            $response = $request();
        } catch (ConnectionException $exception) {
            throw new PixelforgeConnectionException($exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw PixelforgeRequestException::fromResponse($response);
        }

        return $response;
    }
}
