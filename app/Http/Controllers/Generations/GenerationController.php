<?php

namespace App\Http\Controllers\Generations;

use App\Enums\Generations\ImageQuality;
use App\Enums\Generations\ImageSize;
use App\Enums\User\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Generations\StoreGenerationRequest;
use App\Http\Resources\Generations\GenerationResource;
use App\Jobs\Generations\DispatchWaitingGenerations;
use App\Models\Generations\Generation;
use App\Repositories\Contracts\Generations\GenerationRepositoryInterface;
use App\Services\Generations\GenerationService;
use App\Support\Exceptions\Billing\InsufficientCreditsException;
use App\Support\Exceptions\Pixelforge\PixelforgeException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The pages for making pictures and seeing your history.
 */
class GenerationController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(
        private GenerationService $generationService,
        private GenerationRepositoryInterface $generations,
    ) {}

    /**
     * The history page. While any picture on it is unfinished, the page keeps
     * refreshing itself.
     */
    public function index(Request $request): Response
    {
        $page = $this->generations->paginateForUser($request->user(), self::PER_PAGE);

        // Check this before wrapping the page in the resource, because the
        // resource swaps the models out.
        $hasUnfinished = collect($page->items())->contains(fn (Generation $generation): bool => ! $generation->isFinished());

        return Inertia::render('generations/Index', [
            'generations' => GenerationResource::collection($page),
            'hasUnfinished' => $hasUnfinished,
        ]);
    }

    /**
     * The Generate form, with the sizes, qualities and limits to choose from.
     */
    public function create(): Response
    {
        return Inertia::render('generations/Create', [
            'sizes' => array_map(fn (ImageSize $size): array => [
                'value' => $size->value,
                'label' => $size->label(),
            ], ImageSize::cases()),
            'qualities' => array_map(fn (ImageQuality $quality): array => [
                'value' => $quality->value,
                'label' => $quality->label(),
            ], ImageQuality::cases()),
            'maxReferenceImages' => StoreGenerationRequest::MAX_REFERENCE_IMAGES,
            'maxPromptCharacters' => StoreGenerationRequest::MAX_PROMPT_CHARACTERS,
        ]);
    }

    /**
     * Save the request and take the deposit, then try to send it straight away
     * if a slot is free.
     */
    public function store(StoreGenerationRequest $request): RedirectResponse
    {
        try {
            $this->generationService->create(
                $request->user(),
                $request->validated('prompt'),
                ImageSize::from($request->validated('size')),
                ImageQuality::from($request->validated('quality')),
                $request->referenceImages(),
            );
        } catch (InsufficientCreditsException $exception) {
            return back()->withErrors(['credits' => $exception->getMessage()])->withInput();
        } catch (PixelforgeException) {
            return back()->withErrors(['pricing' => 'Prices could not be loaded from Pixelforge, so nothing was charged. Please try again in a moment.'])->withInput();
        }

        DispatchWaitingGenerations::dispatch();

        // Someone allowed to generate but not to see history stays on the form.
        $destination = $request->user()->can(PermissionName::ViewHistory->value) ? 'generations.index' : 'generations.create';

        return to_route($destination)->with('notify', [
            'type' => 'success',
            'content' => 'Generation queued. It will start as soon as a slot is free.',
        ]);
    }

    /**
     * Show one picture. Users can only see their own.
     */
    public function show(Generation $generation): Response
    {
        Gate::authorize('view', $generation);

        return Inertia::render('generations/Show', [
            'generation' => (new GenerationResource($generation->load(['referenceImages', 'ledgerEntries'])))->resolve(),
        ]);
    }
}
