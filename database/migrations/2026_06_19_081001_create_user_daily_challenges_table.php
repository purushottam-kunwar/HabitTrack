<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_daily_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('daily_challenge_id')->constrained('daily_challenges')->cascadeOnDelete();
            $table->date('challenge_date');
            $table->unsignedInteger('current_progress')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'daily_challenge_id', 'challenge_date'], 'udc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_daily_challenges');
    }
};
