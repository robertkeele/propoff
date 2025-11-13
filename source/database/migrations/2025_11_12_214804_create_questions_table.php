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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('question_templates')->onDelete('set null');
            $table->text('question_text');
            $table->enum('question_type', ['multiple_choice', 'yes_no', 'numeric', 'text']);
            $table->json('options')->nullable()->comment('For multiple choice: ["Option A", "Option B", ...]');
            $table->integer('points')->default(1);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index('game_id');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
