<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserXpLog extends Model
{
    use HasFactory;

    protected $table = 'user_xp_logs';

    protected $fillable = [
        'user_id',
        'xp_amount',
        'reason',
        'log_date',
    ];

    protected $casts = [
        'log_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
