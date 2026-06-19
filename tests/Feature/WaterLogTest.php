<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaterLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaterLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_cannot_access_water_endpoints(): void
    {
        $this->getJson('/api/water/today')->assertUnauthorized();
        $this->postJson('/api/water/add')->assertUnauthorized();
        $this->deleteJson('/api/water/remove')->assertUnauthorized();
    }

    public function test_today_creates_water_log_when_none_exists(): void
    {
        $res = $this->actingAs($this->user)->getJson('/api/water/today');

        $res->assertOk()->assertJson([
            'glass_count'    => 0,
            'amount_ml'      => 0,
            'target_glasses' => 8,
            'target_ml'      => 2000,
            'percent'        => 0,
        ]);

        $this->assertDatabaseHas('water_logs', [
            'user_id'     => $this->user->id,
            'glass_count' => 0,
        ]);
    }

    public function test_today_returns_existing_log(): void
    {
        WaterLog::create([
            'user_id'     => $this->user->id,
            'log_date'    => today(),
            'glass_count' => 4,
            'amount_ml'   => 1000,
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/water/today')
            ->assertOk()
            ->assertJson(['glass_count' => 4, 'amount_ml' => 1000]);
    }

    public function test_add_glass_increments_count_and_ml(): void
    {
        $this->actingAs($this->user)->getJson('/api/water/today');

        $res = $this->actingAs($this->user)->postJson('/api/water/add');

        $res->assertOk()->assertJson([
            'glass_count' => 1,
            'amount_ml'   => 250,
        ]);
    }

    public function test_multiple_add_glass_calls_accumulate(): void
    {
        $this->actingAs($this->user)->getJson('/api/water/today');

        $this->actingAs($this->user)->postJson('/api/water/add');
        $this->actingAs($this->user)->postJson('/api/water/add');
        $res = $this->actingAs($this->user)->postJson('/api/water/add');

        $res->assertOk()->assertJson(['glass_count' => 3, 'amount_ml' => 750]);
    }

    public function test_remove_glass_decrements_count(): void
    {
        $this->actingAs($this->user)->getJson('/api/water/today');
        $this->actingAs($this->user)->postJson('/api/water/add');
        $this->actingAs($this->user)->postJson('/api/water/add');

        $res = $this->actingAs($this->user)->deleteJson('/api/water/remove');

        $res->assertOk()->assertJson(['glass_count' => 1, 'amount_ml' => 250]);
    }

    public function test_remove_glass_does_not_go_below_zero(): void
    {
        $this->actingAs($this->user)->getJson('/api/water/today');

        $res = $this->actingAs($this->user)->deleteJson('/api/water/remove');

        $res->assertOk()->assertJson(['glass_count' => 0, 'amount_ml' => 0]);
    }

    public function test_percent_is_calculated_correctly(): void
    {
        $this->actingAs($this->user)->getJson('/api/water/today');

        // 4 glasses = 50%
        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($this->user)->postJson('/api/water/add');
        }

        $this->actingAs($this->user)
            ->getJson('/api/water/today')
            ->assertOk()
            ->assertJson(['percent' => 50]);
    }
}
