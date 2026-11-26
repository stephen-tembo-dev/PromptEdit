<?php

namespace App\Support\Data\Billing;

use App\Models\Generations\Generation;

/**
 * How many tokens of each kind a picture used, from Pixelforge or from our estimate.
 */
final readonly class TokenUsage
{
    public function __construct(
        public int $inputTextTokens,
        public int $inputImageTokens,
        public int $outputTokens,
        public int $reasoningTokens,
    ) {}

    /**
     * Read the token counts from Pixelforge's answer.
     *
     * @param  array{input_text_tokens: int, input_image_tokens: int, output_tokens: int, reasoning_tokens: int}  $usage
     */
    public static function fromApi(array $usage): self
    {
        return new self(
            (int) $usage['input_text_tokens'],
            (int) $usage['input_image_tokens'],
            (int) $usage['output_tokens'],
            (int) $usage['reasoning_tokens'],
        );
    }

    /**
     * All counts at zero.
     */
    public static function none(): self
    {
        return new self(0, 0, 0, 0);
    }

    /**
     * Add two sets of token counts together.
     */
    public function plus(self $other): self
    {
        return new self(
            $this->inputTextTokens + $other->inputTextTokens,
            $this->inputImageTokens + $other->inputImageTokens,
            $this->outputTokens + $other->outputTokens,
            $this->reasoningTokens + $other->reasoningTokens,
        );
    }

    /**
     * True if every count is the same.
     */
    public function equals(self $other): bool
    {
        return $this == $other;
    }

    /**
     * The token counts saved on a picture, or null if it hasn't succeeded yet.
     */
    public static function fromGeneration(Generation $generation): ?self
    {
        if ($generation->input_text_tokens === null
            || $generation->input_image_tokens === null
            || $generation->output_tokens === null
            || $generation->reasoning_tokens === null) {
            return null;
        }

        return new self(
            $generation->input_text_tokens,
            $generation->input_image_tokens,
            $generation->output_tokens,
            $generation->reasoning_tokens,
        );
    }
}
