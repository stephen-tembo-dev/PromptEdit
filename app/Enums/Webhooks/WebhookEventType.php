<?php

namespace App\Enums\Webhooks;

/**
 * The kinds of webhook message Pixelforge can send us.
 */
enum WebhookEventType: string
{
    case GenerationProcessing = 'generation.processing';
    case GenerationSucceeded = 'generation.succeeded';
    case GenerationFailed = 'generation.failed';

    /** Sent by the "Send test event" button on Pixelforge's dashboard. It has no picture in it. */
    case Ping = 'ping';

    /**
     * True if this kind of message has a picture in it for us to apply.
     */
    public function carriesGeneration(): bool
    {
        return $this !== self::Ping;
    }
}
