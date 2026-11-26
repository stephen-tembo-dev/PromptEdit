<?php

namespace App\Http\Requests\Generations;

use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checks the data for the live price while the user fills in the form. We only
 * need what changes the price. Reference links are just counted, not checked,
 * because the user might still be typing them.
 */
class QuoteGenerationRequest extends FormRequest
{
    /**
     * Anyone signed in can get a price.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The rules for the form fields.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // No prompt, no price: an estimate for nothing would look like a real one.
            'prompt' => ['required', 'string', 'max:'.StoreGenerationRequest::MAX_PROMPT_CHARACTERS],
            'size' => ['required', Rule::enum(ImageSize::class)],
            'quality' => ['required', Rule::enum(ImageQuality::class)],
            'reference_image_count' => ['required', 'integer', 'min:0', 'max:'.StoreGenerationRequest::MAX_REFERENCE_IMAGES],
        ];
    }
}
