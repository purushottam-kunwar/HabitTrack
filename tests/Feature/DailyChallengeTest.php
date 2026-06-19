<?php

namespace Tests\Feature;

use App\Models\DailyChallenge;
use App\Models\FoodItem;
use App\Models\HabitLog;
use App\Models\User;
use App\Models\WaterLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyChallengeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function createChallenge(array $attrs = []): DailyChallenge
    {
        return DailyChallenge::create(array_merge([
            'slug'         => 'drink-water',
            'name'         => 'Drink 8 Glasses',
            'description'  => 'Drink 8 glasses of water today',
            'category'     => 'hydration',
            'xp_reward'    => 20,
            'daily_target' => 8,
            'unit'         => 'glasses',
            'is_active'    => true,
        ], $attrs));
    }

    public function test_unauthenticated_cannot_access_challenges(): void
    {
        $this->getJson('/api/challenges/today')->assertUnauthorized();
    }

    public function test_returns_empty_when_no_challenges_exist(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/challenges/today')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_returns_all_active_challenges(): void
    {
        $this->createChallenge(['slug' => 'drink-water', 'name' => 'Drink Water']);
        $this->createChallenge(['slug' => 'eat-fruits', 'name' => 'Eat Fruits']);

        $res = $this->actingAs($this->user)->getJson('/api/challenges/today');

        $res->assertOk();
        $this->assertCount(2, $res->json());
    }

    public function test_water_challenge_completed_when_8_glasses_logged(): void
    {
        $this->createChallenge(['slug' => 'drink-water']);

        WaterLog::create([
            'user_id'     => $this->user->id,
            'log_date'    => today(),
            'glass_count' => 8,
            'amount_ml'   => 2000,
        ]);

        $res = $this->actingAs($this->user)->getJson('/api/challenges/today');

        $challenge = collect($res->json())->firstWhere('slug', 'drink-water');
        $this->assertTrue($challenge['completed']);
        $this->assertEquals(8, $challenge['current_progress']);
    }

    public function test_water_challenge_not_completed_with_fewer_glasses(): void
    {
        $this->createChallenge(['slug' => 'drink-water']);

        WaterLog::create([
            'user_id'     => $this->user->id,
            'log_date'    => today(),
            'glass_count' => 5,
            'amount_ml'   => 1250,
        ]);

        $res = $this->actingAs($this->user)->getJson('/api/challenges/today');

        $challenge = collect($res->json())->firstWhere('slug', 'drink-water');
        $this->assertFalse($challenge['completed']);
    }

    public function test_budget_challenge_completed_within_limit(): void
    {
        $this->createChallenge([
            'slug'         => 'budget-day',
            'name'         => 'Budget Day',
            'daily_target' => 300,
            'unit'         => '₹',
        ]);

        $food = FoodItem::factory()->create();
        HabitLog::factory()->today()->forUser($this->user)->create([
            'food_item_id' => $food->id,
            'amount_spent' => 150.0,
        ]);

        $res = $this->actingAs($this->user)->getJson('/api/challenges/today');

        $challenge = collect($res->json())->firstWhere('slug', 'budget-day');
        $this->assertTrue($challenge['completed']);
    }

    public function test_fruits_challenge_completed_with_two_servings(): void
    {
        $this->createChallenge([
            'slug'         => 'eat-fruits',
            'name'         => 'Eat Fruits',
            'daily_target' => 2,
            'unit'         => 'servings',
        ]);

        $apple = FoodItem::factory()->create(['name' => 'Apple', 'category' => 'healthy']);
        HabitLog::factory()->today()->forUser($this->user)->create([
            'food_item_id' => $apple->id,
            'quantity'     => 2,
        ]);

        $res = $this->actingAs($this->user)->getJson('/api/challenges/today');

        $challenge = collect($res->json())->firstWhere('slug', 'eat-fruits');
        $this->assertTrue($challenge['completed']);
    }

    public function test_xp_awarded_on_first_challenge_completion(): void
    {
        $this->createChallenge(['slug' => 'drink-water', 'xp_reward' => 20]);

        WaterLog::create([
            'user_id'     => $this->user->id,
            'log_date'    => today(),
            'glass_count' => 8,
            'amount_ml'   => 2000,
        ]);

        $this->actingAs($this->user)->getJson('/api/challenges/today');

        $this->assertDatabaseHas('user_xp', [
            'user_id'  => $this->user->id,
            'total_xp' => 20,
        ]);
    }

    public function test_xp_not_awarded_twice_for_same_challenge(): void
    {
        $this->createChallenge(['slug' => 'drink-water', 'xp_reward' => 20]);

        WaterLog::create([
            'user_id'     => $this->user->id,
            'log_date'    => today(),
            'glass_count' => 8,
            'amount_ml'   => 2000,
        ]);

        $this->actingAs($this->user)->getJson('/api/challenges/today');
        $this->actingAs($this->user)->getJson('/api/challenges/today');

        $this->assertDatabaseHas('user_xp', [
            'user_id'  => $this->user->id,
            'total_xp' => 20, // still 20, not 40
        ]);
    }
}
