<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WeightLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_cannot_access_weight_endpoints(): void
    {
        $this->getJson('/api/weight')->assertUnauthorized();
        $this->postJson('/api/weight')->assertUnauthorized();
    }

    public function test_recent_returns_empty_when_no_logs(): void
    {
        $res = $this->actingAs($this->user)->getJson('/api/weight');

        $res->assertOk()->assertJson([
            'logs'           => [],
            'current_weight' => null,
            'change_30d'     => null,
        ]);
    }

    public function test_store_creates_weight_log(): void
    {
        $res = $this->actingAs($this->user)->postJson('/api/weight', ['weight_kg' => 72.5]);

        $res->assertOk();
        $this->assertDatabaseHas('weight_logs', [
            'user_id'   => $this->user->id,
            'weight_kg' => 72.5,
        ]);
    }

    public function test_store_requires_weight(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/weight', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('weight_kg');
    }

    public function test_store_validates_minimum_weight(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/weight', ['weight_kg' => 10])
            ->assertStatus(422)
            ->assertJsonValidationErrors('weight_kg');
    }

    public function test_store_validates_maximum_weight(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/weight', ['weight_kg' => 350])
            ->assertStatus(422)
            ->assertJsonValidationErrors('weight_kg');
    }

    public function test_recent_returns_current_weight(): void
    {
        $this->actingAs($this->user)->postJson('/api/weight', ['weight_kg' => 75.0]);

        $this->actingAs($this->user)
            ->getJson('/api/weight')
            ->assertOk()
            ->assertJson(['current_weight' => '75.00']);
    }

    public function test_30_day_change_is_calculated(): void
    {
        // Old weight 31 days ago
        WeightLog::create([
            'user_id'   => $this->user->id,
            'log_date'  => now()->subDays(31),
            'weight_kg' => 80.0,
        ]);

        // Current weight today
        $this->actingAs($this->user)->postJson('/api/weight', ['weight_kg' => 75.0]);

        $res = $this->actingAs($this->user)->getJson('/api/weight');
        $res->assertOk();

        // change_30d should be negative (lost weight)
        $this->assertLessThan(0, $res->json('change_30d'));
    }

    public function test_store_updates_existing_log_for_same_day(): void
    {
        $this->actingAs($this->user)->postJson('/api/weight', ['weight_kg' => 72.0]);
        $this->actingAs($this->user)->postJson('/api/weight', ['weight_kg' => 73.0]);

        $this->assertDatabaseCount('weight_logs', 1);
        $this->assertDatabaseHas('weight_logs', ['weight_kg' => 73.0]);
    }
}
