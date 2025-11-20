<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Group;
use App\Models\GroupQuestionAnswer;
use App\Models\Question;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserAnswer;

class SubmissionService
{
    /**
     * Create a new submission for a user.
     */
    public function createSubmission(Game $game, User $user, Group $group): Submission
    {
        $possiblePoints = $game->questions()->sum('points');

        return Submission::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'group_id' => $group->id,
            'total_score' => 0,
            'possible_points' => $possiblePoints,
            'percentage' => 0,
            'is_complete' => false,
            'submitted_at' => null,
        ]);
    }

    /**
     * Save or update user answers for a submission.
     */
    public function saveAnswers(Submission $submission, array $answers): void
    {
        foreach ($answers as $answerData) {
            UserAnswer::updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'question_id' => $answerData['question_id'],
                ],
                [
                    'answer_text' => $answerData['answer_text'],
                    'points_earned' => 0, // Will be calculated during grading
                    'is_correct' => false, // Will be determined during grading
                ]
            );
        }
    }

    /**
     * Mark submission as complete.
     */
    public function completeSubmission(Submission $submission): void
    {
        $submission->update([
            'is_complete' => true,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Grade a submission based on group-specific correct answers.
     */
    public function gradeSubmission(Submission $submission): void
    {
        $userAnswers = $submission->userAnswers()->with('question')->get();
        $totalScore = 0;
        $possiblePoints = 0;

        foreach ($userAnswers as $userAnswer) {
            $question = $userAnswer->question;

            // Get group-specific correct answer
            $groupAnswer = GroupQuestionAnswer::where('question_id', $question->id)
                ->where('group_id', $submission->group_id)
                ->first();

            // Skip if question is voided for this group
            if ($groupAnswer && $groupAnswer->is_void) {
                $userAnswer->update([
                    'points_earned' => 0,
                    'is_correct' => false,
                ]);
                continue;
            }

            // Check if answer is correct
            $isCorrect = false;
            $pointsEarned = 0;

            if ($groupAnswer && $this->compareAnswers($userAnswer->answer_text, $groupAnswer->correct_answer, $question->question_type)) {
                $isCorrect = true;
                // Use group-specific points if set
                if ($groupAnswer->points_awarded !== null) {
                    $pointsEarned = $groupAnswer->points_awarded;
                } else {
                    // Calculate base + bonus for the chosen option
                    $pointsEarned = $this->calculatePointsForAnswer(
                        $question,
                        $userAnswer->answer_text
                    );
                }
                $totalScore += $pointsEarned;
            }

            // Calculate possible points (max achievable for this question)
            if ($groupAnswer && $groupAnswer->points_awarded !== null) {
                $questionMaxPoints = $groupAnswer->points_awarded;
            } else {
                $questionMaxPoints = $this->calculateMaxPointsForQuestion($question);
            }
            $possiblePoints += $questionMaxPoints;

            $userAnswer->update([
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
    }

    /**
     * Compare user answer with correct answer based on question type.
     */
    protected function compareAnswers(string $userAnswer, string $correctAnswer, string $questionType): bool
    {
        // Trim and normalize
        $userAnswer = trim($userAnswer);
        $correctAnswer = trim($correctAnswer);

        switch ($questionType) {
            case 'multiple_choice':
            case 'yes_no':
                // Case-insensitive comparison
                return strcasecmp($userAnswer, $correctAnswer) === 0;

            case 'numeric':
                // Numeric comparison with tolerance
                $userNum = (float) $userAnswer;
                $correctNum = (float) $correctAnswer;
                $tolerance = 0.01; // Allow small rounding errors
                return abs($userNum - $correctNum) <= $tolerance;

            case 'text':
                // Case-insensitive and trimmed comparison
                return strcasecmp($userAnswer, $correctAnswer) === 0;

            default:
                return $userAnswer === $correctAnswer;
        }
    }

    /**
     * Calculate points for a specific answer (base + option bonus).
     */
    protected function calculatePointsForAnswer(Question $question, string $answerText): int
    {
        $basePoints = $question->points;
        $bonusPoints = 0;

        // For multiple choice, check if answer has bonus points
        if ($question->question_type === 'multiple_choice' && $question->options) {
            $options = is_string($question->options) ? json_decode($question->options, true) : $question->options;

            if (is_array($options)) {
                foreach ($options as $option) {
                    // Handle new format: {label: "Yes", points: 2}
                    if (is_array($option) && isset($option['label'])) {
                        if (strcasecmp(trim($option['label']), trim($answerText)) === 0) {
                            $bonusPoints = $option['points'] ?? 0;
                            break;
                        }
                    }
                }
            }
        }

        return $basePoints + $bonusPoints;
    }

    /**
     * Calculate maximum possible points for a question.
     */
    protected function calculateMaxPointsForQuestion(Question $question): int
    {
        $basePoints = $question->points;
        $maxBonus = 0;

        // For multiple choice, find the highest bonus
        if ($question->question_type === 'multiple_choice' && $question->options) {
            $options = is_string($question->options) ? json_decode($question->options, true) : $question->options;

            if (is_array($options)) {
                foreach ($options as $option) {
                    // Handle new format: {label: "Yes", points: 2}
                    if (is_array($option) && isset($option['points'])) {
                        $maxBonus = max($maxBonus, $option['points'] ?? 0);
                    }
                }
            }
        }

        return $basePoints + $maxBonus;
    }

    /**
     * Get submission statistics for a user.
     */
    public function getUserSubmissionStats(User $user): array
    {
        return [
            'total_submissions' => Submission::where('user_id', $user->id)->count(),
            'completed_submissions' => Submission::where('user_id', $user->id)->where('is_complete', true)->count(),
            'average_score' => Submission::where('user_id', $user->id)
                ->where('is_complete', true)
                ->avg('percentage') ?? 0,
            'best_score' => Submission::where('user_id', $user->id)
                ->where('is_complete', true)
                ->max('percentage') ?? 0,
            'total_points' => Submission::where('user_id', $user->id)
                ->where('is_complete', true)
                ->sum('total_score'),
        ];
    }

    /**
     * Get all submissions for a game.
     */
    public function getGameSubmissions(Game $game, bool $completedOnly = true)
    {
        $query = Submission::where('game_id', $game->id)
            ->with(['user', 'group']);

        if ($completedOnly) {
            $query->where('is_complete', true);
        }

        return $query->orderBy('submitted_at', 'desc')->get();
    }

    /**
     * Check if submission can be edited.
     */
    public function canEditSubmission(Submission $submission): bool
    {
        // Check if game is past lock date
        if ($submission->game->lock_date && now()->isAfter($submission->game->lock_date)) {
            return false;
        }

        // Check if game status allows editing
        if (in_array($submission->game->status, ['completed', 'in_progress'])) {
            return false;
        }

        return true;
    }
}
