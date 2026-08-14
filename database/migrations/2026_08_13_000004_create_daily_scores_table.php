<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('score', 5, 2)->default(0);
            $table->unsignedInteger('total_tasks')->default(0);
            $table->unsignedInteger('completed_tasks')->default(0);
            $table->unsignedInteger('in_progress_tasks')->default(0);
            $table->unsignedInteger('pending_tasks')->default(0);
            $table->unsignedInteger('skipped_tasks')->default(0);
            $table->unsignedInteger('partial_tasks')->default(0);
            $table->unsignedInteger('total_planned_minutes')->default(0);
            $table->unsignedInteger('total_actual_minutes')->default(0);
            $table->text('category_breakdown')->nullable();
            $table->text('reflection')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_scores');
    }
};
