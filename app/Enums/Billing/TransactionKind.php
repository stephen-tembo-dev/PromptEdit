<?php

namespace App\Enums\Billing;

/**
 * The lines on a customer's Reports page: credit purchases plus the three
 * kinds of ledger line. The ledger ones share LedgerEntryType's values, so a
 * ledger row's type can be read straight into this.
 */
enum TransactionKind: string
{
    case Purchase = 'purchase';
    case Hold = 'hold';
    case Release = 'release';
    case Charge = 'charge';

    /**
     * What the line is called on the page.
     */
    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Credits bought',
            self::Hold => 'Held',
            self::Release => 'Hold returned',
            self::Charge => 'Charged',
        };
    }

    /**
     * Plus 1 if the line adds to the balance, minus 1 if it takes away.
     */
    public function sign(): int
    {
        return match ($this) {
            self::Purchase, self::Release => 1,
            self::Hold, self::Charge => -1,
        };
    }
}
