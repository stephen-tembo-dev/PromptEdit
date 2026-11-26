<?php

namespace Database\Factories\Generations;

use App\Enums\Generations\GenerationStatus;
use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Models\Billing\RateCard;
use App\Models\Generations\Generation;
use App\Models\User\User;
use Database\Factories\Billing\RateCardFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * States follow the lifecycle: waitingForSlot -> queued -> processing -> succeeded|failed.
 * Token counts in succeeded() match the default medium 1024x1024 request.
 *
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    /**
     * Define the model's default state: created locally, waiting for a free slot.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'prompt' => fake()->sentence(8),
            'size' => ImageSize::Square,
            'quality' => ImageQuality::Medium,
            'status' => GenerationStatus::Queued,
            'estimated_charge_nano' => 72_281_250,
        ];
    }

    public function waitingForSlot(): static
    {
        return $this->state([
            'status' => GenerationStatus::Queued,
            'provider_id' => null,
            'submitted_at' => null,
        ]);
    }

    /**
     * Accepted by Pixelforge, which has locked its rates on the generation.
     */
    public function queued(): static
    {
        return $this->state(fn (): array => [
            'status' => GenerationStatus::Queued,
            'provider_id' => 'gen_'.Str::lower(Str::random(24)),
            'rate_card_id' => RateCard::query()->firstOrCreate(RateCardFactory::PIXELFORGE_RATES)->id,
            'submit_attempts' => 1,
            'submitted_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->queued()->state([
            'status' => GenerationStatus::Processing,
            'started_at' => now(),
        ]);
    }

    public function succeeded(): static
    {
        return $this->processing()->state(fn (): array => [
            'status' => GenerationStatus::Succeeded,
            'input_text_tokens' => 12,
            'input_image_tokens' => 0,
            'output_tokens' => 1_100,
            'reasoning_tokens' => fake()->numberBetween(200, 2_000),
            'image_url' => 'https://pixelforge.promptedit.dev/images/'.Str::lower(Str::random(24)).'.png',
            'image_url_expires_at' => now()->addDay(),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->processing()->state([
            'status' => GenerationStatus::Failed,
            'error_code' => 'generation_failed',
            'error_message' => 'The model could not produce an image for this prompt.',
            'completed_at' => now(),
        ]);
    }
}
