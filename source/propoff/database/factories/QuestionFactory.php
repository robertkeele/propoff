<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $questionType = fake()->randomElement(['multiple_choice', 'yes_no', 'numeric', 'text']);
        $options = null;

        if ($questionType === 'multiple_choice') {
            $options = ['Option A', 'Option B', 'Option C', 'Option D'];
        }

        return [
            'game_id' => \App\Models\Game::factory(),
            'template_id' => fake()->optional()->randomElement(\App\Models\QuestionTemplate::pluck('id')->toArray()),
            'question_text' => fake()->sentence() . '?',
            'question_type' => $questionType,
            'options' => $options,
            'points' => fake()->numberBetween(1, 5),
            'display_order' => fake()->numberBetween(1, 50),
        ];
    }
}
