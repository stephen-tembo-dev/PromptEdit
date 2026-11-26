<?php

namespace Tests\Feature\Services\Billing;

use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Models\Billing\RateCard;
use App\Models\Generations\Generation;
use App\Services\Billing\PricingService;
use App\Support\Data\Billing\TokenRates;
use App\Support\Data\Billing\TokenUsage;
use Database\Factories\Billing\RateCardFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\FakesPixelforge;

class PricingServiceTest extends TestCase
{
    use FakesPixelforge, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakePricing();
    }

    public function test_the_docs_worked_example_has_a_maximum_of_0_373875_dollars(): void
    {
        $quote = app(PricingService::class)->quote(str_repeat('a', 400), ImageSize::Landscape, ImageQuality::High, 2);

        $this->assertSame(373_875_000, $quote->maxProviderCostNano);
        $this->assertSame(467_343_750, $quote->maxChargeNano);
    }

    public function test_the_hold_adds_a_margin_to_the_text_tokens_only(): void
    {
        // 100 estimated text tokens: the maximum allows 115, the hold 125 (+10 x $5 per 1M).
        $quote = app(PricingService::class)->quote(str_repeat('a', 400), ImageSize::Landscape, ImageQuality::High, 2);

        $this->assertSame(app(PricingService::class)->chargeNano(373_875_000 + 10 * 5_000), $quote->holdChargeNano);
        $this->assertSame(467_406_250, $quote->holdChargeNano);
    }

    public function test_the_hold_always_covers_at_least_one_more_text_token_than_the_maximum(): void
    {
        // "a dot": 2 estimated tokens; +15% and +25% both round up to 3, so the floor of max + 1 applies.
        $pricing = app(PricingService::class);
        $quote = $pricing->quote('a dot', ImageSize::Square, ImageQuality::Low, 0);

        $this->assertSame($pricing->chargeNano($quote->maxProviderCostNano + 5_000), $quote->holdChargeNano);
    }

    public function test_the_worst_possible_request_is_bounded(): void
    {
        $quote = app(PricingService::class)->quote(str_repeat('a', 4000), ImageSize::Landscape, ImageQuality::High, 4);

        $this->assertSame(394_350_000, $quote->maxProviderCostNano);
        $this->assertSame(492_937_500, $quote->maxChargeNano);
        $this->assertSame(493_562_500, $quote->holdChargeNano, 'The margin on the longest prompt is $0.000625.');
    }

    public function test_the_estimate_uses_the_midpoint_of_the_reasoning_range(): void
    {
        // 100 text tokens, 1,100 output tokens, (200 + 2,000) / 2 = 1,100 reasoning tokens.
        $quote = app(PricingService::class)->quote(str_repeat('a', 400), ImageSize::Square, ImageQuality::Medium, 0);

        $this->assertSame(100 * 5_000 + 1_100 * 40_000 + 1_100 * 12_500, $quote->estimatedProviderCostNano);
        $this->assertSame(115 * 5_000 + 1_100 * 40_000 + 2_000 * 12_500, $quote->maxProviderCostNano);
    }

    public function test_prompts_are_measured_in_characters_not_bytes(): void
    {
        $pricing = app(PricingService::class);

        // Eight characters but sixteen bytes: two text tokens, not four.
        $accented = $pricing->quote(str_repeat('é', 8), ImageSize::Square, ImageQuality::Low, 0);
        $plain = $pricing->quote(str_repeat('e', 8), ImageSize::Square, ImageQuality::Low, 0);

        $this->assertSame($plain->maxProviderCostNano, $accented->maxProviderCostNano);
    }

    public function test_the_markup_rounds_up_by_less_than_one_nanodollar(): void
    {
        $pricing = app(PricingService::class);

        $this->assertSame(5, $pricing->chargeNano(4));
        $this->assertSame(2, $pricing->chargeNano(1));
        $this->assertSame(2_000_000, $pricing->chargeNano(1_600_000));
        $this->assertSame(0, $pricing->chargeNano(0));
    }

    public function test_provider_cost_matches_the_usage_report_example_exactly(): void
    {
        $cost = app(PricingService::class)->providerCostNano(
            TokenRates::fromDecimalStrings($this->pricingBody()['rates']),
            new TokenUsage(41, 0, 3300, 2950),
        );

        $this->assertSame(169_080_000, $cost);
    }

    public function test_a_generation_is_costed_at_its_locked_rates_not_todays_pricing(): void
    {
        $oldRates = RateCard::query()->create(['output_nano_per_million' => 30_000_000_000] + RateCardFactory::PIXELFORGE_RATES);
        $generation = Generation::factory()->succeeded()->create([
            'rate_card_id' => $oldRates->id,
            'input_text_tokens' => 0,
            'input_image_tokens' => 0,
            'output_tokens' => 1_000,
            'reasoning_tokens' => 0,
        ]);

        $this->assertSame(30_000_000, app(PricingService::class)->providerCostForGeneration($generation));
    }

    public function test_pricing_is_fetched_once_and_then_served_from_the_cache(): void
    {
        $pricing = app(PricingService::class);

        $pricing->quote('one', ImageSize::Square, ImageQuality::Low, 0);
        $pricing->quote('two', ImageSize::Square, ImageQuality::Low, 0);

        Http::assertSentCount(1);
    }
}
