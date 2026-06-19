<?php

namespace Tests\Feature;

use Anthropic\Client as AnthropicClient;
use App\Models\FoodItem;
use App\Models\HabitLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCoachTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_cannot_access_ai_coach(): void
    {
        $this->getJson('/api/ai-coach/daily')->assertUnauthorized();
    }

    public function test_returns_no_data_message_when_no_food_logs_today(): void
    {
        // No food logs — endpoint should short-circuit and NOT call the Anthropic API
        $res = $this->actingAs($this->user)->getJson('/api/ai-coach/daily');

        $res->assertOk()->assertJson(['has_data' => false]);
        $this->assertStringContainsString("haven't logged", $res->json('suggestion'));
    }

    public function test_returns_coaching_suggestion_with_mocked_client(): void
    {
        // Build a lightweight anonymous-class fake of the Anthropic SDK
        $block           = new \stdClass();
        $block->type     = 'text';
        $block->text     = 'Great work logging a healthy meal today! Keep it up.';
        $fakeResponse    = new \stdClass();
        $fakeResponse->content = [$block];

        $fakeMessages = new class($fakeResponse) {
            public function __construct(private object $response) {}
            public function create(mixed ...$_): object { return $this->response; }
        };

        $fakeClient = new class($fakeMessages) {
            public function __construct(public object $messages) {}
        };

        $this->instance(AnthropicClient::class, $fakeClient);

        // Create a food log so the controller proceeds to call the API
        $food = FoodItem::factory()->healthy()->create(['name' => 'Salad', 'calories' => 120]);
        HabitLog::factory()->today()->forUser($this->user)->create([
            'food_item_id' => $food->id,
            'amount_spent' => 80.0,
        ]);

        $res = $this->actingAs($this->user)->getJson('/api/ai-coach/daily');

        $res->assertOk()
            ->assertJson([
                'has_data'   => true,
                'suggestion' => 'Great work logging a healthy meal today! Keep it up.',
            ]);
    }

    public function test_returns_fallback_when_api_throws(): void
    {
        $fakeMessages = new class {
            public function create(mixed ...$_): never
            {
                throw new \RuntimeException('API unavailable');
            }
        };

        $fakeClient = new class($fakeMessages) {
            public function __construct(public object $messages) {}
        };

        $this->instance(AnthropicClient::class, $fakeClient);

        $food = FoodItem::factory()->create();
        HabitLog::factory()->today()->forUser($this->user)->create([
            'food_item_id' => $food->id,
            'amount_spent' => 50.0,
        ]);

        $res = $this->actingAs($this->user)->getJson('/api/ai-coach/daily');

        // Should return a 500 with a friendly fallback message, not crash
        $res->assertStatus(500);
        $this->assertNotEmpty($res->json('suggestion'));
    }
}
