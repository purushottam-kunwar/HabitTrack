<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoodLog extends Model
{
    protected $fillable = ['user_id', 'log_date', 'mood', 'energy_level', 'notes'];

    protected $casts = [
        'log_date' => 'date',
        'mood' => 'integer',
        'energy_level' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
