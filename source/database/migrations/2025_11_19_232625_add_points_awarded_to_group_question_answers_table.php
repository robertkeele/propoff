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
        Schema::table('group_question_answers', function (Blueprint $table) {
            $table->integer('points_awarded')->nullable()->after('correct_answer')
                ->comment('Points awarded for this answer (overrides question.points if set)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_question_answers', function (Blueprint $table) {
            $table->dropColumn('points_awarded');
        });
    }
};
