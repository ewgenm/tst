<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#8B5CF6');
            $table->string('icon')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'custom'])->default('daily');
            $table->json('target_days')->nullable(); // [0,1,2,3,4,5,6] where 0=Sunday
            $table->integer('current_streak')->default(0);
            $table->integer('best_streak')->default(0);
            $table->timestamp('last_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('habit_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained()->cascadeOnDelete();
            $table->date('completed_date');
            $table->timestamps();
            $table->unique(['habit_id', 'completed_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_completions');
        Schema::dropIfExists('habits');
    }
};
