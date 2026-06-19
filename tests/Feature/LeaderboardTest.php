<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserXp;
use App\Models\UserStreak;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // --- Global Leaderboard ---

    public function test_unauthenticated_cannot_access_leaderboard(): void
    {
        $this->getJson('/api/leaderboard')->assertUnauthorized();
    }

    public function test_global_leaderboard_returns_all_users(): void
    {
        User::factory()->count(3)->create();

        $res = $this->actingAs($this->user)->getJson('/api/leaderboard?filter=global');

        $res->assertOk();
        $this->assertCount(4, $res->json()); // 3 + self
    }

    public function test_global_leaderboard_is_sorted_by_xp_descending(): void
    {
        $highXpUser = User::factory()->create(['name' => 'TopPlayer']);
        UserXp::factory()->withXp(5000)->create(['user_id' => $highXpUser->id]);
        UserXp::factory()->withXp(100)->create(['user_id' => $this->user->id]);

        $res = $this->actingAs($this->user)->getJson('/api/leaderboard?filter=global');
        $res->assertOk();

        $rankings = $res->json();
        $this->assertEquals($highXpUser->id, $rankings[0]['id']);
    }

    public function test_leaderboard_marks_current_user_as_me(): void
    {
        $res = $this->actingAs($this->user)->getJson('/api/leaderboard?filter=global');

        $me = collect($res->json())->firstWhere('is_me', true);
        $this->assertNotNull($me);
        $this->assertEquals($this->user->id, $me['id']);
    }

    // --- Friends Leaderboard ---

    public function test_friends_leaderboard_includes_only_followed_users_and_self(): void
    {
        $friend  = User::factory()->create();
        $stranger = User::factory()->create();
        $this->user->following()->attach($friend->id);

        $res = $this->actingAs($this->user)->getJson('/api/leaderboard?filter=friends');

        $res->assertOk();
        $ids = collect($res->json())->pluck('id')->toArray();
        $this->assertContains($this->user->id, $ids);
        $this->assertContains($friend->id, $ids);
        $this->assertNotContains($stranger->id, $ids);
    }

    public function test_friends_leaderboard_empty_when_following_nobody(): void
    {
        $res = $this->actingAs($this->user)->getJson('/api/leaderboard?filter=friends');
        $res->assertOk();
        $this->assertCount(1, $res->json()); // only self
    }

    // --- User Search ---

    public function test_search_finds_user_by_name(): void
    {
        User::factory()->create(['name' => 'Priya Sharma']);

        $res = $this->actingAs($this->user)->getJson('/api/users/search?q=Priya');

        $res->assertOk();
        $this->assertCount(1, $res->json());
        $this->assertEquals('Priya Sharma', $res->json()[0]['name']);
    }

    public function test_search_requires_minimum_two_characters(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/users/search?q=A')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_search_does_not_return_current_user(): void
    {
        $res = $this->actingAs($this->user)->getJson('/api/users/search?q=' . urlencode($this->user->name));

        $ids = collect($res->json())->pluck('id')->toArray();
        $this->assertNotContains($this->user->id, $ids);
    }

    // --- Follow / Unfollow ---

    public function test_follow_creates_relationship(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->user)
            ->postJson("/api/follow/{$target->id}")
            ->assertOk()
            ->assertJson(['following' => true]);

        $this->assertTrue($this->user->following()->where('users.id', $target->id)->exists());
    }

    public function test_unfollow_removes_relationship(): void
    {
        $target = User::factory()->create();
        $this->user->following()->attach($target->id);

        $this->actingAs($this->user)
            ->deleteJson("/api/follow/{$target->id}")
            ->assertOk()
            ->assertJson(['following' => false]);

        $this->assertFalse($this->user->following()->where('users.id', $target->id)->exists());
    }

    public function test_cannot_follow_yourself(): void
    {
        $this->actingAs($this->user)
            ->postJson("/api/follow/{$this->user->id}")
            ->assertStatus(422);
    }

    public function test_following_same_user_twice_does_not_duplicate(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->user)->postJson("/api/follow/{$target->id}");
        $this->actingAs($this->user)->postJson("/api/follow/{$target->id}");

        $this->assertDatabaseCount('user_follows', 1);
    }

    public function test_is_following_flag_is_correct_in_leaderboard(): void
    {
        $friend = User::factory()->create();
        $this->user->following()->attach($friend->id);

        $res = $this->actingAs($this->user)->getJson('/api/leaderboard?filter=global');

        $friendEntry = collect($res->json())->firstWhere('id', $friend->id);
        $this->assertTrue($friendEntry['is_following']);
    }
}
