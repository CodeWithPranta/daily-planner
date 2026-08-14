<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('in_progress');
            $table->timestamps();

            $table->index(['task_id', 'created_at']);
        });

        Schema::create('timer_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('total_seconds')->default(0);
            $table->string('status')->default('running');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['task_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timer_sessions');
        Schema::dropIfExists('task_updates');
    }
};
