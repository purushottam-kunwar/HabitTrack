<?php

namespace Tests\Feature;

use App\Models\MoodLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoodLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_cannot_access_mood_endpoints(): void
    {
        $this->getJson('/api/mood/today')->assertUnauthorized();
        $this->postJson('/api/mood')->assertUnauthorized();
        $this->getJson('/api/mood/history')->assertUnauthorized();
    }

    public function test_today_returns_null_when_no_log_exists(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/mood/today')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_store_creates_mood_log_for_today(): void
    {
        $res = $this->actingAs($this->user)->postJson('/api/mood', [
            'mood'         => 4,
            'energy_level' => 3,
            'notes'        => 'Feeling good after lunch',
        ]);

        $res->assertOk()->assertJson(['mood' => 4, 'energy_level' => 3]);

        $this->assertDatabaseHas('mood_logs', [
            'user_id'      => $this->user->id,
            'mood'         => 4,
            'energy_level' => 3,
            'notes'        => 'Feeling good after lunch',
        ]);
    }

    public function test_store_updates_existing_log_for_today(): void
    {
        $this->actingAs($this->user)->postJson('/api/mood', ['mood' => 3, 'energy_level' => 2]);
        $this->actingAs($this->user)->postJson('/api/mood', ['mood' => 5, 'energy_level' => 5]);

        $this->assertDatabaseCount('mood_logs', 1);
        $this->assertDatabaseHas('mood_logs', ['mood' => 5, 'energy_level' => 5]);
    }

    public function test_store_validates_mood_range(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/mood', ['mood' => 0, 'energy_level' => 3])
            ->assertStatus(422)
            ->assertJsonValidationErrors('mood');

        $this->actingAs($this->user)
            ->postJson('/api/mood', ['mood' => 6, 'energy_level' => 3])
            ->assertStatus(422)
            ->assertJsonValidationErrors('mood');
    }

    public function test_store_validates_energy_range(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/mood', ['mood' => 3, 'energy_level' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('energy_level');

        $this->actingAs($this->user)
            ->postJson('/api/mood', ['mood' => 3, 'energy_level' => 6])
            ->assertStatus(422)
            ->assertJsonValidationErrors('energy_level');
    }

    public function test_today_returns_logged_mood(): void
    {
        $this->actingAs($this->user)->postJson('/api/mood', ['mood' => 2, 'energy_level' => 1]);

        $this->actingAs($this->user)
            ->getJson('/api/mood/today')
            ->assertOk()
            ->assertJson(['mood' => 2, 'energy_level' => 1]);
    }

    public function test_history_returns_last_30_days(): void
    {
        // Create 5 mood logs on different days
        for ($i = 0; $i < 5; $i++) {
            MoodLog::create([
                'user_id'      => $this->user->id,
                'log_date'     => now()->subDays($i)->toDateString(),
                'mood'         => rand(1, 5),
                'energy_level' => rand(1, 5),
            ]);
        }

        // Create one older than 30 days (should be excluded)
        MoodLog::create([
            'user_id'      => $this->user->id,
            'log_date'     => now()->subDays(31)->toDateString(),
            'mood'         => 3,
            'energy_level' => 3,
        ]);

        $res = $this->actingAs($this->user)->getJson('/api/mood/history');

        $res->assertOk();
        $this->assertCount(5, $res->json('history'));
        $this->assertNotNull($res->json('avg_mood'));
        $this->assertNotNull($res->json('avg_energy'));
    }

    public function test_history_does_not_leak_other_users_data(): void
    {
        $other = User::factory()->create();
        MoodLog::create([
            'user_id'      => $other->id,
            'log_date'     => today()->toDateString(),
            'mood'         => 5,
            'energy_level' => 5,
        ]);

        $res = $this->actingAs($this->user)->getJson('/api/mood/history');
        $res->assertOk();
        $this->assertCount(0, $res->json('history'));
    }
}
