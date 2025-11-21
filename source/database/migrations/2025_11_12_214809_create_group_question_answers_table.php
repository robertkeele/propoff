<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('group_question_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('group_question_id')->constrained('group_questions')->onDelete('cascade');
            $table->text('correct_answer')->nullable();
            $table->integer('points_awarded')->nullable()->comment('Points awarded for this answer (overrides question.points if set)');
            $table->boolean('is_void')->default(false)->comment('If true, no points awarded for this question in this group');
            $table->timestamps();

            $table->unique(['group_id', 'group_question_id']);
            $table->index('group_id');
            $table->index('group_question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_question_answers');
    }
};
