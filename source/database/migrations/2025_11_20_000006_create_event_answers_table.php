<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('event_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_question_id')->constrained('event_questions')->onDelete('cascade');
            $table->text('correct_answer');
            $table->boolean('is_void')->default(false);
            $table->timestamp('set_at')->nullable();
            $table->foreignId('set_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['event_id', 'event_question_id']);
            $table->index('event_id');
            $table->index('event_question_id');
            $table->index('is_void');
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_answers');
    }
};
