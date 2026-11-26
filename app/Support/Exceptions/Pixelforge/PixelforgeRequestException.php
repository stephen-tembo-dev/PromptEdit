<?php

namespace App\Support\Exceptions\Pixelforge;

use Illuminate\Http\Client\Response;

/**
 * Thrown when Pixelforge answers with an error. errorCode is Pixelforge's own
 * code for it, like concurrency_limit_exceeded or content_policy_violation.
 */
class PixelforgeRequestException extends PixelforgeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message, $status);
    }

    /**
     * Build the exception from Pixelforge's error answer.
     */
    public static function fromResponse(Response $response): self
    {
        $error = $response->json('error');
        $retryAfter = $response->header('Retry-After');

        return new self(
            status: $response->status(),
            errorCode: (string) ($error['code'] ?? 'http_'.$response->status()),
            message: (string) ($error['message'] ?? "Pixelforge responded with HTTP {$response->status()}."),
            retryAfterSeconds: is_numeric($retryAfter) ? (int) $retryAfter : null,
        );
    }

    /**
     * True for a 5xx error, meaning the problem was on Pixelforge's side.
     */
    public function isServerError(): bool
    {
        return $this->status >= 500;
    }
}
