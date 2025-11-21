<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('group_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_question_id')->nullable()->constrained('event_questions')->onDelete('set null');
            $table->text('question_text');
            $table->enum('question_type', ['multiple_choice', 'yes_no', 'numeric', 'text']);
            $table->json('options')->nullable();
            $table->integer('points')->default(1);
            $table->integer('display_order');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false);
            $table->timestamps();

            $table->index('group_id');
            $table->index('event_question_id');
            $table->index('is_active');
            $table->index('display_order');
        });

        // Migrate existing data: Create group questions from event questions for all groups
        $groups = DB::table('groups')->whereNotNull('event_id')->get();
        foreach ($groups as $group) {
            $eventQuestions = DB::table('event_questions')
                ->where('event_id', $group->event_id)
                ->get();

            foreach ($eventQuestions as $eq) {
                DB::table('group_questions')->insert([
                    'group_id' => $group->id,
                    'event_question_id' => $eq->id,
                    'question_text' => $eq->question_text,
                    'question_type' => $eq->question_type,
                    'options' => $eq->options,
                    'points' => $eq->points,
                    'display_order' => $eq->display_order,
                    'is_active' => true,
                    'is_custom' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('group_questions');
    }
};
