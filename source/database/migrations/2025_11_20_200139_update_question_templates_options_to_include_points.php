<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing question templates to new format
        $templates = DB::table('question_templates')->whereNotNull('default_options')->get();

        foreach ($templates as $template) {
            $options = json_decode($template->default_options, true);

            // Skip if already in new format
            if (!empty($options) && is_array($options) && isset($options[0]['label'])) {
                continue;
            }

            // Convert old format (array of strings) to new format (array of objects with label/points)
            if (is_array($options)) {
                $newOptions = [];
                foreach ($options as $option) {
                    // If it's a string, convert to object format
                    if (is_string($option)) {
                        $newOptions[] = [
                            'label' => $option,
                            'points' => 0, // Default 0 bonus points
                        ];
                    }
                }

                if (!empty($newOptions)) {
                    DB::table('question_templates')
                        ->where('id', $template->id)
                        ->update(['default_options' => json_encode($newOptions)]);
                }
            }
        }

        // Update table comment to reflect new structure
        Schema::table('question_templates', function (Blueprint $table) {
            $table->json('default_options')
                ->nullable()
                ->comment('For multiple choice: [{"label": "Option A", "points": 0}, ...]')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to old format (array of strings)
        $templates = DB::table('question_templates')->whereNotNull('default_options')->get();

        foreach ($templates as $template) {
            $options = json_decode($template->default_options, true);

            if (is_array($options) && !empty($options) && isset($options[0]['label'])) {
                $oldOptions = array_map(function ($option) {
                    return $option['label'] ?? $option;
                }, $options);

                DB::table('question_templates')
                    ->where('id', $template->id)
                    ->update(['default_options' => json_encode($oldOptions)]);
            }
        }

        Schema::table('question_templates', function (Blueprint $table) {
            $table->json('default_options')
                ->nullable()
                ->comment('Default options for multiple choice')
                ->change();
        });
    }
};
