<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodItem extends Model
{
    protected $fillable = ['name', 'category', 'type', 'calories', 'unit'];

    public function habitLogs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }
}
