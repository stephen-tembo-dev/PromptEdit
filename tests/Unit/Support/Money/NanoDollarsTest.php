<?php

namespace Tests\Unit\Support\Money;

use App\Support\Money\NanoDollars;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class NanoDollarsTest extends TestCase
{
    #[TestWith(['12.50', 12_500_000_000])]
    #[TestWith(['0.373875000', 373_875_000])]
    #[TestWith(['0.000000001', 1])]
    #[TestWith(['5', 5_000_000_000])]
    public function test_decimal_strings_parse_to_exact_nanodollars(string $usd, int $nano): void
    {
        $this->assertSame($nano, NanoDollars::fromDecimalString($usd));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidAmounts(): array
    {
        return [
            'ten decimals' => ['0.1234567891'],
            'negative' => ['-1.00'],
            'exponent' => ['1e-9'],
            'empty' => [''],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_amounts_that_cannot_be_exact_are_rejected(string $usd): void
    {
        $this->expectException(InvalidArgumentException::class);

        NanoDollars::fromDecimalString($usd);
    }

    public function test_amounts_print_with_nine_decimals_like_the_usage_report(): void
    {
        $this->assertSame('0.169080000', NanoDollars::toDecimalString(169_080_000));
        $this->assertSame('12.500000000', NanoDollars::toDecimalString(12_500_000_000));
    }

    #[TestWith([57_656_250, '$0.0577'])]
    #[TestWith([1_600_000, '$0.0016'])]
    #[TestWith([49_999, '$0.0000'])]
    #[TestWith([50_000, '$0.0001'])]
    #[TestWith([467_343_750, '$0.4673'])]
    public function test_display_rounds_half_up_to_four_decimals_not_to_cents(int $nano, string $shown): void
    {
        $this->assertSame($shown, NanoDollars::format($nano));
    }
}
