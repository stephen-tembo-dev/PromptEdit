<?php

namespace App\Services\Billing;

use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Models\Generations\Generation;
use App\Services\Pixelforge\PixelforgeService;
use App\Support\Data\Billing\Quote;
use App\Support\Data\Billing\TokenRates;
use App\Support\Data\Billing\TokenUsage;
use App\Support\Data\Pixelforge\Pricing;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Cache;
use LogicException;

/**
 * Every price calculation in the app lives here, in integer nanodollars.
 *
 *  - provider cost = sum of tokens x rate per million, for each token type
 *  - user charge   = ceil(provider cost x (100 + markup) / 100)
 */
class PricingService
{
    private const PRICING_CACHE_KEY = 'pixelforge.pricing';

    /** Serve cached rates for an hour, then refresh in the background for up to a day. */
    private const PRICING_CACHE_TTL = [3600, 86400];

    /**
     * Extra text tokens held on top of Pixelforge's documented +15%, in basis points
     * of the estimate (1,000 = 10 percentage points). Text is the only part of the
     * price Pixelforge counts loosely, so it is the only part given a margin.
     */
    public const HOLD_TEXT_MARGIN_BASIS_POINTS = 1_000;

    public function __construct(
        private PixelforgeService $pixelforge,
        #[Config('services.pixelforge.markup_percent')] private int $markupPercent,
    ) {}

    /**
     * Current rates and token rules from /v1/pricing, cached rather than hardcoded.
     */
    public function pricing(): Pricing
    {
        return Pricing::fromApi(
            Cache::flexible(self::PRICING_CACHE_KEY, self::PRICING_CACHE_TTL, fn (): array => $this->pixelforge->fetchPricing()),
        );
    }

    /**
     * "Estimated $X, at most $Y" for a request that has not been sent yet, plus the
     * amount to hold.
     *
     * The hold adds a margin to the text tokens only: reference image, output and
     * reasoning tokens are already exact upper bounds, but a prompt's token count
     * depends on how Pixelforge counts characters and rounds, and the documented
     * +15% leaves no room for either. The margin is 10 more percentage points, and
     * always at least one token; even the longest prompt holds only ~$0.0006 more.
     */
    public function quote(string $prompt, ImageSize $size, ImageQuality $quality, int $referenceImageCount): Quote
    {
        $pricing = $this->pricing();

        $estimatedTextTokens = self::divideRoundingUp(mb_strlen($prompt), $pricing->charactersPerTextToken);
        $maxTextTokens = self::divideRoundingUp(
            $estimatedTextTokens * (10_000 + $pricing->textTokenVarianceBasisPoints),
            10_000,
        );
        $holdTextTokens = max(
            $maxTextTokens + 1,
            self::divideRoundingUp(
                $estimatedTextTokens * (10_000 + $pricing->textTokenVarianceBasisPoints + self::HOLD_TEXT_MARGIN_BASIS_POINTS),
                10_000,
            ),
        );
        $imageTokens = $referenceImageCount * $pricing->tokensPerReferenceImage;
        $outputTokens = $pricing->outputTokens($size, $quality);
        $reasoning = $pricing->reasoningTokenRange($quality);

        $estimatedCost = $this->providerCostNano($pricing->rates, new TokenUsage(
            $estimatedTextTokens,
            $imageTokens,
            $outputTokens,
            intdiv($reasoning['min'] + $reasoning['max'], 2),
        ));

        $maxCost = $this->providerCostNano($pricing->rates, new TokenUsage(
            $maxTextTokens,
            $imageTokens,
            $outputTokens,
            $reasoning['max'],
        ));

        $holdCost = $this->providerCostNano($pricing->rates, new TokenUsage(
            $holdTextTokens,
            $imageTokens,
            $outputTokens,
            $reasoning['max'],
        ));

        return new Quote(
            estimatedProviderCostNano: $estimatedCost,
            maxProviderCostNano: $maxCost,
            estimatedChargeNano: $this->chargeNano($estimatedCost),
            maxChargeNano: $this->chargeNano($maxCost),
            holdChargeNano: $this->chargeNano($holdCost),
        );
    }

    /**
     * What Pixelforge charges for this usage. Rates have at most two decimals per
     * million tokens, so the division is exact and the result matches /v1/usage;
     * should a rate ever be finer, the cost rounds up rather than under-charging.
     */
    public function providerCostNano(TokenRates $rates, TokenUsage $usage): int
    {
        $nanoTimesMillion = $usage->inputTextTokens * $rates->inputTextNanoPerMillion
            + $usage->inputImageTokens * $rates->inputImageNanoPerMillion
            + $usage->outputTokens * $rates->outputNanoPerMillion
            + $usage->reasoningTokens * $rates->reasoningNanoPerMillion;

        return self::divideRoundingUp($nanoTimesMillion, 1_000_000);
    }

    /**
     * The actual provider cost of a succeeded generation, at the rates Pixelforge
     * locked when it created it (not today's pricing).
     */
    public function providerCostForGeneration(Generation $generation): int
    {
        $usage = TokenUsage::fromGeneration($generation)
            ?? throw new LogicException("Generation {$generation->uuid} has no recorded token usage.");

        $rateCard = $generation->rateCard
            ?? throw new LogicException("Generation {$generation->uuid} has no locked rate card.");

        return $this->providerCostNano(TokenRates::fromRateCard($rateCard), $usage);
    }

    /**
     * The user's price: cost plus markup, rounded up by less than one nanodollar.
     * Never rounded to cents: a $0.0016 image charged as $0.01 would be a 525% markup.
     */
    public function chargeNano(int $providerCostNano): int
    {
        return self::divideRoundingUp($providerCostNano * (100 + $this->markupPercent), 100);
    }

    private static function divideRoundingUp(int $dividend, int $divisor): int
    {
        return intdiv($dividend + $divisor - 1, $divisor);
    }
}
