<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDailyChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'daily_challenge_id',
        'challenge_date',
        'current_progress',
        'completed',
        'completed_at',
    ];

    protected $casts = [
        'challenge_date' => 'date',
        'completed_at'   => 'datetime',
        'completed'      => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(DailyChallenge::class);
    }
}
