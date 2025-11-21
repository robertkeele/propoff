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
        Schema::create('question_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('question_text');
            $table->enum('question_type', ['multiple_choice', 'yes_no', 'numeric', 'text']);
            $table->string('category', 100)->nullable();
            $table->integer('default_points')->default(1);
            $table->json('variables')->nullable()->comment('Array of variable names like ["team1", "player1"]');
            $table->json('default_options')->nullable()->comment('For multiple choice: [{"label": "Option A", "points": 0}, ...]');
            $table->boolean('is_favorite')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('category');
            $table->index('is_favorite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_templates');
    }
};
