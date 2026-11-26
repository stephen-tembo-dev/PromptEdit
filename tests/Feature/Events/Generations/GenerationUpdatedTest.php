<?php

namespace Tests\Feature\Events\Generations;

use App\Events\Generations\GenerationUpdated;
use App\Models\Generations\Generation;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class GenerationUpdatedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The test suite broadcasts to "null", which lets anyone join any
        // channel. Switch to Reverb and register the channels again so the
        // real rules are checked.
        config(['broadcasting.connections.reverb' => [
            'driver' => 'reverb',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'app_id' => 'test-app',
            'options' => ['host' => 'localhost', 'port' => 8080, 'scheme' => 'http', 'useTLS' => false],
        ]]);
        Broadcast::setDefaultDriver('reverb');

        require base_path('routes/channels.php');
    }

    public function test_it_tells_only_the_owner_which_picture_changed(): void
    {
        $generation = Generation::factory()->processing()->create();
        $event = new GenerationUpdated($generation);

        $this->assertSame('private-users.'.$generation->user_id, $event->broadcastOn()->name);
        $this->assertSame(['id' => $generation->uuid, 'status' => 'processing'], $event->broadcastWith());
    }

    public function test_users_can_listen_to_their_own_channel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/broadcasting/auth', ['channel_name' => 'private-users.'.$user->id, 'socket_id' => '1234.5678'])
            ->assertOk();
    }

    public function test_users_cannot_listen_to_someone_elses_channel(): void
    {
        $user = User::factory()->create();
        $someoneElse = User::factory()->create();

        $this->actingAs($user)
            ->post('/broadcasting/auth', ['channel_name' => 'private-users.'.$someoneElse->id, 'socket_id' => '1234.5678'])
            ->assertForbidden();
    }
}
