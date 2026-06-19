<?php

namespace Tests\Unit;

use App\Models\UserXp;
use Tests\TestCase;

class UserXpTest extends TestCase
{
    public function test_level_starts_at_1_for_zero_xp(): void
    {
        $this->assertEquals(1, UserXp::levelFromXp(0));
    }

    public function test_level_increases_every_500_xp(): void
    {
        $this->assertEquals(1, UserXp::levelFromXp(499));
        $this->assertEquals(2, UserXp::levelFromXp(500));
        $this->assertEquals(2, UserXp::levelFromXp(999));
        $this->assertEquals(3, UserXp::levelFromXp(1000));
        $this->assertEquals(11, UserXp::levelFromXp(5000));
    }

    public function test_xp_in_level_wraps_at_500(): void
    {
        $this->assertEquals(0, UserXp::xpInLevelFromTotal(0));
        $this->assertEquals(100, UserXp::xpInLevelFromTotal(100));
        $this->assertEquals(0, UserXp::xpInLevelFromTotal(500));
        $this->assertEquals(250, UserXp::xpInLevelFromTotal(750));
        $this->assertEquals(1, UserXp::xpInLevelFromTotal(1001));
    }

    public function test_level_and_xp_in_level_are_consistent(): void
    {
        foreach ([0, 100, 499, 500, 1000, 2345, 5000] as $total) {
            $level      = UserXp::levelFromXp($total);
            $xpInLevel  = UserXp::xpInLevelFromTotal($total);
            $reconstructed = (($level - 1) * 500) + $xpInLevel;
            $this->assertEquals($total, $reconstructed, "Mismatch for total XP = {$total}");
        }
    }
}
