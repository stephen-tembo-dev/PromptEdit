<?php

namespace App\Http\Controllers\Generations;

use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Http\Controllers\Controller;
use App\Http\Requests\Generations\QuoteGenerationRequest;
use App\Services\Billing\BalanceService;
use App\Services\Billing\PricingService;
use App\Support\Exceptions\Pixelforge\PixelforgeException;
use App\Support\Money\NanoDollars;
use Illuminate\Http\JsonResponse;

/**
 * Gives the Generate form its live "Estimated $X, at most $Y" while the user
 * types. All the price sums happen here on the server. The page just shows
 * what we send back.
 */
class GenerationQuoteController extends Controller
{
    /**
     * Work out the prices for what's in the form, and whether the user can afford it.
     */
    public function __invoke(QuoteGenerationRequest $request, PricingService $pricing, BalanceService $balance): JsonResponse
    {
        try {
            $quote = $pricing->quote(
                (string) $request->validated('prompt', ''),
                ImageSize::from($request->validated('size')),
                ImageQuality::from($request->validated('quality')),
                (int) $request->validated('reference_image_count'),
            );
        } catch (PixelforgeException) {
            return response()->json(['message' => 'Prices are unavailable right now.'], 503);
        }

        $balanceNano = $balance->balanceNano($request->user());

        return response()->json([
            'estimated_charge' => NanoDollars::format($quote->estimatedChargeNano),
            'max_charge' => NanoDollars::format($quote->maxChargeNano),
            'held_charge' => NanoDollars::format($quote->holdChargeNano),
            'balance' => NanoDollars::format($balanceNano),
            'can_afford' => $balanceNano >= $quote->holdChargeNano,
        ]);
    }
}
