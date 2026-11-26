<?php

namespace Tests\Traits;

use App\Models\Generations\Generation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Builds Pixelforge responses in the shapes the API docs give, so tests exercise the
 * real parsing code rather than hand-made objects.
 */
trait FakesPixelforge
{
    protected const PIXELFORGE_URL = 'pixelforge.promptedit.dev/v1';

    /**
     * The documented GET /v1/pricing body.
     *
     * @return array<string, mixed>
     */
    protected function pricingBody(): array
    {
        return [
            'object' => 'pricing',
            'currency' => 'usd',
            'unit' => 'per_1m_tokens',
            'rates' => [
                'input_text_tokens' => '5.00',
                'input_image_tokens' => '10.00',
                'output_tokens' => '40.00',
                'reasoning_tokens' => '12.50',
            ],
            'tokens' => [
                'characters_per_text_token' => 4,
                'text_token_variance' => 0.15,
                'per_reference_image' => 765,
                'output' => [
                    'low' => ['1024x1024' => 280, '1536x1024' => 420, '1024x1536' => 420],
                    'medium' => ['1024x1024' => 1100, '1536x1024' => 1650, '1024x1536' => 1650],
                    'high' => ['1024x1024' => 4300, '1536x1024' => 6450, '1024x1536' => 6450],
                ],
                'reasoning' => [
                    'low' => ['min' => 0, 'max' => 500],
                    'medium' => ['min' => 200, 'max' => 2000],
                    'high' => ['min' => 1000, 'max' => 8000],
                ],
            ],
        ];
    }

    /**
     * A Pixelforge generation object for one of our generations.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function generationObject(Generation $generation, string $status, array $overrides = []): array
    {
        $object = [
            'id' => $generation->provider_id ?? 'gen_'.Str::lower(Str::random(24)),
            'object' => 'generation',
            'status' => $status,
            'prompt' => $generation->prompt,
            'size' => $generation->size->value,
            'quality' => $generation->quality->value,
            'reference_images' => [],
            'metadata' => ['generation_id' => $generation->uuid],
            'image_url' => null,
            'image_url_expires_at' => null,
            'usage' => null,
            'rates' => $this->pricingBody()['rates'],
            'error' => null,
            'created_at' => '2026-11-04T14:03:11Z',
            'started_at' => $status === 'queued' ? null : '2026-11-04T14:03:13Z',
            'completed_at' => null,
        ];

        if ($status === 'succeeded') {
            $object['usage'] = [
                'input_text_tokens' => 12,
                'input_image_tokens' => 765,
                'output_tokens' => 6450,
                'reasoning_tokens' => 3184,
                'total_tokens' => 10411,
            ];
            $object['image_url'] = 'https://pixelforge.promptedit.dev/images/abc.png?expires=1&signature=x';
            $object['image_url_expires_at'] = '2026-11-05T14:03:29Z';
            $object['completed_at'] = '2026-11-04T14:03:29Z';
        }

        if ($status === 'failed') {
            $object['error'] = ['code' => 'generation_failed', 'message' => 'The model could not produce an image for this prompt.'];
            $object['completed_at'] = '2026-11-04T14:03:29Z';
        }

        return array_replace($object, $overrides);
    }

    /**
     * A Pixelforge error response body.
     *
     * @return array<string, mixed>
     */
    protected function errorBody(string $type, string $code, string $message): array
    {
        return ['error' => ['type' => $type, 'code' => $code, 'message' => $message]];
    }

    /**
     * A GET /v1/usage body. Each day is [generations, input text, input image, output,
     * reasoning tokens, cost_usd]; days not given come back as zeros, as the API does.
     *
     * @param  array<string, array{int, int, int, int, int, string}>  $days
     * @return array<string, mixed>
     */
    protected function usageBody(string $startDate, string $endDate, array $days = []): array
    {
        $data = [];

        for ($date = $startDate; $date <= $endDate; $date = date('Y-m-d', strtotime("{$date} +1 day"))) {
            [$generations, $text, $image, $output, $reasoning, $cost] = $days[$date] ?? [0, 0, 0, 0, 0, '0.000000000'];

            $data[] = [
                'date' => $date,
                'generations' => $generations,
                'input_text_tokens' => $text,
                'input_image_tokens' => $image,
                'output_tokens' => $output,
                'reasoning_tokens' => $reasoning,
                'cost_usd' => $cost,
            ];
        }

        return ['object' => 'usage', 'start_date' => $startDate, 'end_date' => $endDate, 'data' => $data, 'total' => []];
    }

    protected function fakePricing(): void
    {
        Http::fake([self::PIXELFORGE_URL.'/pricing' => Http::response($this->pricingBody())]);
    }
}
