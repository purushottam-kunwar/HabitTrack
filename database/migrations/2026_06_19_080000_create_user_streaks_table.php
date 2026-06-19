<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('logging_streak')->default(0);
            $table->unsignedInteger('logging_streak_best')->default(0);
            $table->unsignedInteger('healthy_streak')->default(0);
            $table->unsignedInteger('healthy_streak_best')->default(0);
            $table->unsignedInteger('consistency_score')->default(0);
            $table->timestamp('last_logged_date')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_streaks');
    }
};
