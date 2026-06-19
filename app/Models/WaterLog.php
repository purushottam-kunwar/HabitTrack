<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'log_date',
        'amount_ml',
        'glass_count',
    ];

    protected $casts = [
        'log_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function dailyTarget()
    {
        return 2000;
    }
}
