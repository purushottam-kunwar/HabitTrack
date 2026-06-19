<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserXp extends Model
{
    use HasFactory;

    protected $table = 'user_xp';

    protected $fillable = [
        'user_id',
        'total_xp',
        'current_level',
        'xp_in_level',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function levelFromXp($total_xp)
    {
        return intval($total_xp / 500) + 1;
    }

    public static function xpInLevelFromTotal($total_xp)
    {
        return $total_xp % 500;
    }
}
