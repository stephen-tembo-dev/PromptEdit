<?php

namespace Tests\Unit;

use App\Enums\Generations\GenerationStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GenerationStatusTest extends TestCase
{
    /**
     * @return array<string, array{GenerationStatus, GenerationStatus, bool}>
     */
    public static function transitions(): array
    {
        return [
            'queued to processing' => [GenerationStatus::Queued, GenerationStatus::Processing, true],
            'queued straight to succeeded' => [GenerationStatus::Queued, GenerationStatus::Succeeded, true],
            'queued to failed' => [GenerationStatus::Queued, GenerationStatus::Failed, true],
            'processing to succeeded' => [GenerationStatus::Processing, GenerationStatus::Succeeded, true],
            'processing to failed' => [GenerationStatus::Processing, GenerationStatus::Failed, true],
            'processing repeated' => [GenerationStatus::Processing, GenerationStatus::Processing, false],
            'processing back to queued' => [GenerationStatus::Processing, GenerationStatus::Queued, false],
            'late processing after succeeded' => [GenerationStatus::Succeeded, GenerationStatus::Processing, false],
            'succeeded to failed' => [GenerationStatus::Succeeded, GenerationStatus::Failed, false],
            'failed to succeeded' => [GenerationStatus::Failed, GenerationStatus::Succeeded, false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_status_only_moves_forward_and_final_states_never_change(
        GenerationStatus $from,
        GenerationStatus $to,
        bool $allowed,
    ): void {
        $this->assertSame($allowed, $from->canTransitionTo($to));
    }
}
