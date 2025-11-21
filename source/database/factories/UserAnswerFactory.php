<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserAnswer>
 */
class UserAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isCorrect = fake()->boolean(60); // 60% chance of being correct
        $pointsEarned = $isCorrect ? fake()->numberBetween(1, 5) : 0;

        return [
            'submission_id' => \App\Models\Submission::factory(),
            'question_id' => \App\Models\EventQuestion::factory(),
            'group_question_id' => \App\Models\GroupQuestion::factory(),
            'answer_text' => fake()->sentence(5),
            'points_earned' => $pointsEarned,
            'is_correct' => $isCorrect,
        ];
    }
}
