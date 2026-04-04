<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * @see ТЗ №0 раздел 11 - Tasks migration
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo'); // enum через check constraint
            $table->string('priority')->default('medium'); // enum через check constraint
            $table->timestamp('due_at')->nullable()->comment('UTC');
            $table->integer('position')->default(0);
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_rule')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['project_id', 'status', 'position']);
            $table->index(['assignee_id', 'status']);
            $table->index(['due_at']);
        });

        // Check constraints для enum-полей
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_status_check CHECK (status IN ('todo', 'in_progress', 'review', 'done'))");
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_priority_check CHECK (priority IN ('low', 'medium', 'high', 'urgent'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
