<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\Webhooks\ProcessWebhookEvent;
use App\Services\Webhooks\WebhookService;
use App\Support\Exceptions\Webhooks\InvalidWebhookSignatureException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;
use JsonException;

/**
 * Where Pixelforge sends its webhook messages.
 *
 * Pixelforge wants an answer within 10 seconds, so we keep this quick: check
 * the signature, save the message, and leave the real work to a queued job.
 * A duplicate gets "200 OK" but isn't queued again.
 */
class PixelforgeWebhookController extends Controller
{
    /**
     * Check, save and queue one message.
     */
    public function __invoke(Request $request, WebhookService $webhooks): Response
    {
        $payload = $request->getContent();

        try {
            $webhooks->verifySignature($request->header('Pixelforge-Signature'), $payload);
        } catch (InvalidWebhookSignatureException $exception) {
            return response($exception->getMessage(), 400);
        }

        try {
            $event = $webhooks->receive($payload);
        } catch (InvalidArgumentException|JsonException) {
            return response('The webhook payload is malformed.', 400);
        }

        if ($event !== null) {
            ProcessWebhookEvent::dispatch($event);
        }

        return response('', 200);
    }
}
