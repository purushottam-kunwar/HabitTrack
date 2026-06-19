<?php

namespace Tests\Feature;

use App\Models\FoodItem;
use App\Models\HabitLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_cannot_access_budget_endpoints(): void
    {
        $this->getJson('/api/budget')->assertUnauthorized();
        $this->postJson('/api/budget')->assertUnauthorized();
    }

    public function test_index_returns_null_budget_and_zero_spent_by_default(): void
    {
        $res = $this->actingAs($this->user)->getJson('/api/budget');

        $res->assertOk()->assertJson([
            'daily_budget' => null,
            'today_spent'  => 0,
            'percent'      => null,
            'remaining'    => null,
        ]);
    }

    public function test_update_sets_daily_budget(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/budget', ['daily_budget' => 200])
            ->assertOk()
            ->assertJson(['daily_budget' => 200.0]);

        $this->assertDatabaseHas('users', [
            'id'           => $this->user->id,
            'daily_budget' => 200.0,
        ]);
    }

    public function test_update_validates_minimum_value(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/budget', ['daily_budget' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('daily_budget');
    }

    public function test_update_validates_required_field(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/budget', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('daily_budget');
    }

    public function test_index_calculates_today_spent(): void
    {
        $food = FoodItem::factory()->create(['calories' => 300]);
        HabitLog::factory()->today()->forUser($this->user)->create([
            'food_item_id' => $food->id,
            'amount_spent' => 150.00,
        ]);
        HabitLog::factory()->today()->forUser($this->user)->create([
            'food_item_id' => $food->id,
            'amount_spent' => 80.00,
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/budget')
            ->assertOk()
            ->assertJson(['today_spent' => 230.0]);
    }

    public function test_percent_is_calculated_when_budget_is_set(): void
    {
        $this->actingAs($this->user)->postJson('/api/budget', ['daily_budget' => 200]);

        $food = FoodItem::factory()->create();
        HabitLog::factory()->today()->forUser($this->user)->create([
            'food_item_id' => $food->id,
            'amount_spent' => 100.0,
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/budget')
            ->assertOk()
            ->assertJson(['percent' => 50]);
    }

    public function test_remaining_is_negative_when_over_budget(): void
    {
        $this->actingAs($this->user)->postJson('/api/budget', ['daily_budget' => 100]);

        $food = FoodItem::factory()->create();
        HabitLog::factory()->today()->forUser($this->user)->create([
            'food_item_id' => $food->id,
            'amount_spent' => 150.0,
        ]);

        $res = $this->actingAs($this->user)->getJson('/api/budget');
        $res->assertOk();
        $this->assertLessThan(0, $res->json('remaining'));
    }

    public function test_index_returns_last_7_days(): void
    {
        $res = $this->actingAs($this->user)->getJson('/api/budget');

        $res->assertOk();
        $this->assertCount(7, $res->json('last_7_days'));
    }

    public function test_only_includes_logged_in_users_spending(): void
    {
        $other = User::factory()->create();
        $food  = FoodItem::factory()->create();

        HabitLog::factory()->today()->create([
            'user_id'      => $other->id,
            'food_item_id' => $food->id,
            'amount_spent' => 500.0,
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/budget')
            ->assertOk()
            ->assertJson(['today_spent' => 0]);
    }
}
