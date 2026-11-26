<?php

namespace App\Support\Money;

use InvalidArgumentException;

/**
 * Helpers for turning dollars into nanodollars and back.
 * 1 dollar = 1,000,000,000 nanodollars.
 *
 * Pixelforge sends money as text like "0.373875000". We read it as text, not
 * as a decimal number, so nothing gets lost. We only round in format(), when
 * showing an amount to a person.
 */
final class NanoDollars
{
    public const PER_DOLLAR = 1_000_000_000;

    /**
     * Turn a dollar amount written as text, like "12.50" or "0.373875000", into nanodollars.
     */
    public static function fromDecimalString(string $usd): int
    {
        if (preg_match('/^(\d+)(?:\.(\d{1,9}))?$/', $usd, $matches) !== 1) {
            throw new InvalidArgumentException("[{$usd}] is not a USD amount with at most 9 decimal places.");
        }

        return (int) $matches[1] * self::PER_DOLLAR + (int) str_pad($matches[2] ?? '', 9, '0');
    }

    /**
     * Turn nanodollars back into exact dollars with 9 decimal places, like Pixelforge's usage report.
     */
    public static function toDecimalString(int $nano): string
    {
        $absolute = abs($nano);

        return sprintf(
            '%s%d.%09d',
            $nano < 0 ? '-' : '',
            intdiv($absolute, self::PER_DOLLAR),
            $absolute % self::PER_DOLLAR,
        );
    }

    /**
     * A dollar amount for people to read, rounded to the nearest. For example,
     * 57,656,250 nanodollars shows as "$0.0577".
     */
    public static function format(int $nano, int $decimals = 4): string
    {
        if ($decimals < 1 || $decimals > 9) {
            throw new InvalidArgumentException('Decimals must be between 1 and 9.');
        }

        $unit = 10 ** (9 - $decimals);
        $scale = 10 ** $decimals;
        $rounded = intdiv(abs($nano) + intdiv($unit, 2), $unit);

        return sprintf(
            "%s\$%d.%0{$decimals}d",
            $nano < 0 ? '-' : '',
            intdiv($rounded, $scale),
            $rounded % $scale,
        );
    }
}
