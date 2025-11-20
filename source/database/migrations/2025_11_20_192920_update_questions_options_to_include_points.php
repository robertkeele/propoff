<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Convert options from: ["Option A", "Option B"]
     * To: [{"label": "Option A", "points": 1}, {"label": "Option B", "points": 1}]
     */
    public function up(): void
    {
        // Update existing questions to new format
        $questions = DB::table('questions')
            ->whereNotNull('options')
            ->get();

        foreach ($questions as $question) {
            $options = json_decode($question->options, true);

            // Skip if already in new format (has objects with 'label' key)
            if (!empty($options) && is_array($options) && isset($options[0]['label'])) {
                continue;
            }

            // Convert old format to new format
            if (is_array($options)) {
                $newOptions = [];
                foreach ($options as $option) {
                    $newOptions[] = [
                        'label' => $option,
                        'points' => 1, // Default 1 point per option
                    ];
                }

                DB::table('questions')
                    ->where('id', $question->id)
                    ->update(['options' => json_encode($newOptions)]);
            }
        }

        // Update column comment
        Schema::table('questions', function (Blueprint $table) {
            $table->json('options')->nullable()->comment('For multiple choice: [{"label": "Option A", "points": 1}, ...]')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Convert back from: [{"label": "Option A", "points": 1}]
     * To: ["Option A", "Option B"]
     */
    public function down(): void
    {
        // Convert back to old format
        $questions = DB::table('questions')
            ->whereNotNull('options')
            ->get();

        foreach ($questions as $question) {
            $options = json_decode($question->options, true);

            // Skip if already in old format (simple strings)
            if (!empty($options) && is_array($options) && !isset($options[0]['label'])) {
                continue;
            }

            // Convert new format back to old format
            if (is_array($options)) {
                $oldOptions = [];
                foreach ($options as $option) {
                    $oldOptions[] = $option['label'] ?? $option;
                }

                DB::table('questions')
                    ->where('id', $question->id)
                    ->update(['options' => json_encode($oldOptions)]);
            }
        }

        // Revert column comment
        Schema::table('questions', function (Blueprint $table) {
            $table->json('options')->nullable()->comment('For multiple choice: ["Option A", "Option B", ...]')->change();
        });
    }
};
