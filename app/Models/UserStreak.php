<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStreak extends Model
{
    use HasFactory;

    protected $table = 'user_streaks';

    protected $fillable = [
        'user_id',
        'logging_streak',
        'logging_streak_best',
        'healthy_streak',
        'healthy_streak_best',
        'consistency_score',
        'last_logged_date',
    ];

    protected $casts = [
        'last_logged_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
