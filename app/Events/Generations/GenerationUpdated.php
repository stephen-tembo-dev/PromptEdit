<?php

namespace App\Events\Generations;

use App\Models\Generations\Generation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the owner's open pages that one of their pictures changed (it got a
 * slot, started, succeeded or failed).
 *
 * The message is only a nudge. The page fetches the picture again rather than
 * trusting what's in the message, so a message lost while the connection was
 * down costs nothing: the page catches up when it reconnects.
 */
class GenerationUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Generation $generation) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('users.'.$this->generation->user_id);
    }

    public function broadcastAs(): string
    {
        return 'generation.updated';
    }

    /**
     * @return array{id: string, status: string}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->generation->uuid,
            'status' => $this->generation->status->value,
        ];
    }
}
