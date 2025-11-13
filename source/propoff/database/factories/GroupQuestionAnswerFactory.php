<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupQuestionAnswer>
 */
class GroupQuestionAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => \App\Models\Group::factory(),
            'question_id' => \App\Models\Question::factory(),
            'correct_answer' => fake()->sentence(5),
            'is_void' => fake()->boolean(5), // 5% chance of being voided
        ];
    }
}
