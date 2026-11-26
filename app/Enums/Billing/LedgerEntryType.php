<?php

namespace App\Enums\Billing;

/**
 * The kinds of money line a picture can have.
 * Amounts are always saved as positive numbers. The type decides whether the
 * line adds to the balance or takes away from it.
 */
enum LedgerEntryType: string
{
    /** The deposit: the most the picture could cost, taken when it's requested. */
    case Hold = 'hold';

    /** Gives the deposit back once the picture is finished, whatever happened. */
    case Release = 'release';

    /** The real price the user pays for a successful picture. */
    case Charge = 'charge';

    /**
     * Plus 1 if this line adds money back to the balance, minus 1 if it takes money away.
     */
    public function sign(): int
    {
        return $this === self::Release ? 1 : -1;
    }
}
