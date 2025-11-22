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

        // Create captain users for testing
        $captain1 = \App\Models\User::factory()->create([
            'name' => 'Captain One',
            'email' => 'captain1@propoff.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'user',
        ]);

        $captain2 = \App\Models\User::factory()->create([
            'name' => 'Captain Two',
            'email' => 'captain2@propoff.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'user',
        ]);

        // Create regular test user
        /*$testUser = \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@propoff.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'user',
        ]);*/

        // Create additional users
        /*$users = \App\Models\User::factory(2)->create();
        $allUsers = $users->push($admin, $captain1, $captain2, $testUser);

        // Create question templates
        $templates = \App\Models\QuestionTemplate::factory(1)->create([
            'created_by' => $admin->id,
        ]);*/

        // Create events FIRST (before groups, since groups need event_id)
        $events = \App\Models\Event::factory(1)->create([
            'created_by' => $admin->id,
        ]);

        // Create groups (first 2 with captains, rest by admin) - WITH event_id
        $groups = collect();
        for ($i = 0; $i < 2; $i++) {
            $createdBy = $admin->id;
            if ($i === 0) {
                $createdBy = $captain1->id;
            } elseif ($i === 1) {
                $createdBy = $captain2->id;
            }

            $groups->push(\App\Models\Group::factory()->create([
                'created_by' => $createdBy,
                'event_id' => $events[$i % $events->count()]->id,
            ]));
        }

        // Attach users to groups
        foreach ($groups as $index => $group) {
            // For first two groups, attach captain as captain
            if ($index === 0) {
                $group->users()->attach($captain1->id, [
                    'joined_at' => now()->subDays(rand(1, 30)),
                    'is_captain' => true,
                ]);
            } elseif ($index === 1) {
                $group->users()->attach($captain2->id, [
                    'joined_at' => now()->subDays(rand(1, 30)),
                    'is_captain' => true,
                ]);
            }

            // Attach random users to all groups
            /*$randomUsers = $allUsers->random(rand(1, 2));
            foreach ($randomUsers as $user) {
                // Skip if user is already attached to this group
                if ($group->users()->where('user_id', $user->id)->exists()) {
                    continue;
                }
                $group->users()->attach($user->id, [
                    'joined_at' => now()->subDays(rand(1, 30)),
                    'is_captain' => false,
                ]);
            }*/
        }

        /*
        // Create event questions for each event
        foreach ($events as $event) {
            $eventQuestions = \App\Models\EventQuestion::factory(1)->create([
                'event_id' => $event->id,
                'template_id' => $templates->random()->id,
            ]);

            // Create group questions from event questions for each group in this event
            $eventGroups = $groups->where('event_id', $event->id);
            foreach ($eventGroups as $group) {
                foreach ($eventQuestions as $index => $eventQuestion) {
                    \App\Models\GroupQuestion::create([
                        'group_id' => $group->id,
                        'event_question_id' => $eventQuestion->id,
                        'question_text' => $eventQuestion->question_text,
                        'question_type' => $eventQuestion->question_type,
                        'options' => $eventQuestion->options,
                        'points' => $eventQuestion->points,
                        'display_order' => $index + 1,
                        'is_active' => true,
                        'is_custom' => false,
                    ]);
                }
            }

            // Create captain-set answers for group questions
            foreach ($eventGroups as $group) {
                $groupQuestions = \App\Models\GroupQuestion::where('group_id', $group->id)->get();

                foreach ($groupQuestions as $groupQuestion) {
                    // Generate type-appropriate correct answer
                    $correctAnswer = null;
                    switch ($groupQuestion->question_type) {
                        case 'multiple_choice':
                            $correctAnswer = fake()->randomElement($groupQuestion->options ?? ['Option A', 'Option B', 'Option C', 'Option D']);
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
                        'group_question_id' => $groupQuestion->id,
                        'correct_answer' => $correctAnswer,
                        'is_void' => fake()->boolean(5), // 5% chance of being voided for this group
                    ]);
                }
            }

            // Create submissions for each event from random users in event groups
            foreach ($eventGroups as $group) {
                $groupUsers = $group->users;
                if ($groupUsers->count() === 0) continue;

                $numSubmissions = min(rand(2, 5), $groupUsers->count());
                foreach ($groupUsers->random($numSubmissions) as $user) {
                    $submission = \App\Models\Submission::create([
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'group_id' => $group->id,
                        'total_score' => 0,
                        'possible_points' => 0,
                        'percentage' => 0,
                        'is_complete' => fake()->boolean(70),
                        'submitted_at' => fake()->boolean(70) ? now()->subDays(rand(1, 10)) : null,
                    ]);

                    // Create user answers for each group question
                    $totalScore = 0;
                    $possiblePoints = 0;
                    $groupQuestions = \App\Models\GroupQuestion::where('group_id', $group->id)
                        ->where('is_active', true)
                        ->get();

                    foreach ($groupQuestions as $groupQuestion) {
                        // Get the captain-set correct answer for this group question
                        $groupAnswer = \App\Models\GroupQuestionAnswer::where('group_id', $group->id)
                            ->where('group_question_id', $groupQuestion->id)
                            ->first();

                        // If question is voided for this group, skip scoring
                        if ($groupAnswer && $groupAnswer->is_void) {
                            \App\Models\UserAnswer::create([
                                'submission_id' => $submission->id,
                                'group_question_id' => $groupQuestion->id,
                                'answer_text' => fake()->sentence(5),
                                'points_earned' => 0,
                                'is_correct' => false,
                            ]);
                            continue;
                        }

                        // Generate user answer and check if correct (60% chance)
                        $isCorrect = fake()->boolean(60);
                        $pointsEarned = $isCorrect ? $groupQuestion->points : 0;
                        $totalScore += $pointsEarned;
                        $possiblePoints += $groupQuestion->points;

                        \App\Models\UserAnswer::create([
                            'submission_id' => $submission->id,
                            'group_question_id' => $groupQuestion->id,
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
                        'event_id' => $event->id,
                        'group_id' => $group->id,
                        'user_id' => $user->id,
                        'rank' => 0, // Will be calculated later
                        'total_score' => $totalScore,
                        'possible_points' => $possiblePoints,
                        'percentage' => $percentage,
                        'answered_count' => $groupQuestions->count(),
                    ]);
                }

                // Update ranks for this event/group combination
                $leaderboardEntries = \App\Models\Leaderboard::where('event_id', $event->id)
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
            $globalLeaderboard = \App\Models\Leaderboard::where('event_id', $event->id)
                ->whereNotNull('group_id')
                ->get()
                ->groupBy('user_id')
                ->map(function ($entries) use ($event) {
                    $totalScore = $entries->sum('total_score');
                    $possiblePoints = $entries->sum('possible_points');
                    $percentage = $possiblePoints > 0 ? round(($totalScore / $possiblePoints) * 100, 2) : 0;

                    return \App\Models\Leaderboard::create([
                        'event_id' => $event->id,
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
            $globalEntries = \App\Models\Leaderboard::where('event_id', $event->id)
                ->whereNull('group_id')
                ->orderByDesc('percentage')
                ->orderByDesc('total_score')
                ->get();

            $rank = 1;
            foreach ($globalEntries as $entry) {
                $entry->update(['rank' => $rank++]);
            }
        }*/
    }
}
