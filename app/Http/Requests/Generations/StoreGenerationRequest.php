<?php

namespace App\Http\Requests\Generations;

use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checks the Generate form before a picture is requested.
 */
class StoreGenerationRequest extends FormRequest
{
    /** Pixelforge allows up to four reference images. Each one costs 765 tokens. */
    public const MAX_REFERENCE_IMAGES = 4;

    /** The longest prompt Pixelforge accepts, in characters. */
    public const MAX_PROMPT_CHARACTERS = 4000;

    /**
     * Anyone signed in can make pictures.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Remove any empty reference link boxes before checking the form.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference_images' => array_values(array_filter(
                (array) $this->input('reference_images', []),
                fn (mixed $url): bool => is_string($url) && trim($url) !== '',
            )),
        ]);
    }

    /**
     * The rules for the form fields.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:'.self::MAX_PROMPT_CHARACTERS],
            'size' => ['required', Rule::enum(ImageSize::class)],
            'quality' => ['required', Rule::enum(ImageQuality::class)],
            'reference_images' => ['array', 'max:'.self::MAX_REFERENCE_IMAGES],
            'reference_images.*' => ['required', 'string', 'url:http,https', 'max:2048', 'distinct'],
        ];
    }

    /**
     * Friendlier error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference_images.max' => 'You can add at most :max reference images.',
            'reference_images.*.url' => 'Reference images must be http or https links.',
            'reference_images.*.distinct' => 'This reference image is listed twice.',
        ];
    }

    /**
     * The reference links the user entered, after checking.
     *
     * @return list<string>
     */
    public function referenceImages(): array
    {
        return $this->validated('reference_images', []);
    }
}
