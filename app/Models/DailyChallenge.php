<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'category',
        'xp_reward',
        'daily_target',
        'unit',
    ];

    public function userChallenges(): HasMany
    {
        return $this->hasMany(UserDailyChallenge::class);
    }
}
