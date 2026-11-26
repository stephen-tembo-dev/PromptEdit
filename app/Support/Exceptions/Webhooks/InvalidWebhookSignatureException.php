<?php

namespace App\Support\Exceptions\Webhooks;

use RuntimeException;

/**
 * Thrown when a webhook's signature is missing, too old or doesn't match.
 */
class InvalidWebhookSignatureException extends RuntimeException {}
