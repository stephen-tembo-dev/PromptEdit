<?php

namespace App\Enums\Billing;

/**
 * How one day of our numbers compares with what Pixelforge charged us.
 */
enum ReconciliationStatus: string
{
    /** The picture count, the token counts and the cost all match. */
    case Matched = 'matched';

    /** A past day that doesn't match. One side has something the other doesn't. */
    case Mismatched = 'mismatched';

    /** Today doesn't match yet, but pictures still finishing may not have reached us. */
    case Pending = 'pending';

    /** We couldn't load Pixelforge's numbers, so there's nothing to compare. */
    case Unavailable = 'unavailable';
}
