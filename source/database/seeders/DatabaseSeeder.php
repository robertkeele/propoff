<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@propoff.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create regular test user
        $testUser = \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@propoff.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'user',
        ]);

        // Create additional users
        $users = \App\Models\User::factory(20)->create();
        $allUsers = $users->push($admin, $testUser);

        // Create groups
        $groups = \App\Models\Group::factory(5)->create([
            'created_by' => $admin->id,
        ]);

        // Attach users to groups
        foreach ($groups as $group) {
            $randomUsers = $allUsers->random(rand(3, 10));
            foreach ($randomUsers as $user) {
                $group->users()->attach($user->id, [
                    'joined_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        // Create question templates
        $templates = \App\Models\QuestionTemplate::factory(15)->create([
            'created_by' => $admin->id,
        ]);

        // Create games
        $games = \App\Models\Game::factory(3)->create([
            'created_by' => $admin->id,
        ]);

        // Create questions for each game
        foreach ($games as $game) {
            $questions = \App\Models\Question::factory(10)->create([
                'game_id' => $game->id,
                'template_id' => $templates->random()->id,
            ]);

            // Create group-specific answers for each question
            foreach ($questions as $question) {
                foreach ($groups as $group) {
                    // Generate type-appropriate correct answer
                    $correctAnswer = null;
                    switch ($question->question_type) {
                        case 'multiple_choice':
                            $correctAnswer = fake()->randomElement($question->options ?? ['Option A', 'Option B', 'Option C', 'Option D']);
                            break;
                        case 'yes_no':
                            $correctAnswer = fake()->randomElement(['Yes', 'No']);
                            break;
                        case 'numeric':
                            $correctAnswer = (string) fake()->numberBetween(0, 100);
                            break;
                        case 'text':
                            $correctAnswer = fake()->sentence(5);
                            break;
                    }

                    \App\Models\GroupQuestionAnswer::create([
                        'group_id' => $group->id,
                        'question_id' => $question->id,
                        'correct_answer' => $correctAnswer,
                        'is_void' => fake()->boolean(5), // 5% chance of being voided for this group
                    ]);
                }
            }

            // Create submissions for each game from random users in random groups
            foreach ($groups as $group) {
                $groupUsers = $group->users;
                $numSubmissions = min(rand(2, 5), $groupUsers->count());
                foreach ($groupUsers->random($numSubmissions) as $user) {
                    $submission = \App\Models\Submission::create([
                        'game_id' => $game->id,
                        'user_id' => $user->id,
                        'group_id' => $group->id,
                        'total_score' => 0,
                        'possible_points' => 0,
                        'percentage' => 0,
                        'is_complete' => fake()->boolean(70),
                        'submitted_at' => fake()->boolean(70) ? now()->subDays(rand(1, 10)) : null,
                    ]);

                    // Create user answers for each question
                    $totalScore = 0;
                    $possiblePoints = 0;
                    foreach ($questions as $question) {
                        // Get the group-specific correct answer
                        $groupAnswer = \App\Models\GroupQuestionAnswer::where('group_id', $group->id)
                            ->where('question_id', $question->id)
                            ->first();

                        // If question is voided for this group, skip scoring
                        if ($groupAnswer && $groupAnswer->is_void) {
                            \App\Models\UserAnswer::create([
                                'submission_id' => $submission->id,
                                'question_id' => $question->id,
                                'answer_text' => fake()->sentence(5),
                                'points_earned' => 0,
                                'is_correct' => false,
                            ]);
                            continue;
                        }

                        // Generate user answer and check if correct (60% chance)
                        $isCorrect = fake()->boolean(60);
                        $pointsEarned = $isCorrect ? $question->points : 0;
                        $totalScore += $pointsEarned;
                        $possiblePoints += $question->points;

                        \App\Models\UserAnswer::create([
                            'submission_id' => $submission->id,
                            'question_id' => $question->id,
                            'answer_text' => $isCorrect && $groupAnswer ? $groupAnswer->correct_answer : fake()->sentence(5),
                            'points_earned' => $pointsEarned,
                            'is_correct' => $isCorrect,
                        ]);
                    }

                    // Update submission totals
                    $percentage = $possiblePoints > 0 ? round(($totalScore / $possiblePoints) * 100, 2) : 0;
                    $submission->update([
                        'total_score' => $totalScore,
                        'possible_points' => $possiblePoints,
                        'percentage' => $percentage,
                    ]);

                    // Create leaderboard entry for group
                    \App\Models\Leaderboard::create([
                        'game_id' => $game->id,
                        'group_id' => $group->id,
                        'user_id' => $user->id,
                        'rank' => 0, // Will be calculated later
                        'total_score' => $totalScore,
                        'possible_points' => $possiblePoints,
                        'percentage' => $percentage,
                        'answered_count' => $questions->count(),
                    ]);
                }

                // Update ranks for this game/group combination
                $leaderboardEntries = \App\Models\Leaderboard::where('game_id', $game->id)
                    ->where('group_id', $group->id)
                    ->orderByDesc('percentage')
                    ->orderByDesc('total_score')
                    ->get();

                $rank = 1;
                foreach ($leaderboardEntries as $entry) {
                    $entry->update(['rank' => $rank++]);
                }
            }

            // Create global leaderboard entries (group_id = null)
            $globalLeaderboard = \App\Models\Leaderboard::where('game_id', $game->id)
                ->whereNotNull('group_id')
                ->get()
                ->groupBy('user_id')
                ->map(function ($entries) use ($game) {
                    $totalScore = $entries->sum('total_score');
                    $possiblePoints = $entries->sum('possible_points');
                    $percentage = $possiblePoints > 0 ? round(($totalScore / $possiblePoints) * 100, 2) : 0;

                    return \App\Models\Leaderboard::create([
                        'game_id' => $game->id,
                        'group_id' => null, // Global leaderboard
                        'user_id' => $entries->first()->user_id,
                        'rank' => 0,
                        'total_score' => $totalScore,
                        'possible_points' => $possiblePoints,
                        'percentage' => $percentage,
                        'answered_count' => $entries->sum('answered_count'),
                    ]);
                });

            // Update global ranks
            $globalEntries = \App\Models\Leaderboard::where('game_id', $game->id)
                ->whereNull('group_id')
                ->orderByDesc('percentage')
                ->orderByDesc('total_score')
                ->get();

            $rank = 1;
            foreach ($globalEntries as $entry) {
                $entry->update(['rank' => $rank++]);
            }
        }
    }
}
