<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAnswer;
use App\Models\EventQuestion;
use App\Models\Group;
use App\Models\GroupQuestion;
use App\Models\GroupQuestionAnswer;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserAnswer;

class SubmissionService
{
    /**
     * Create a new submission for a user.
     */
    public function createSubmission(Event $event, User $user, Group $group): Submission
    {
        // Calculate possible points from group's active questions
        $possiblePoints = $group->groupQuestions()
            ->active()
            ->sum('points');

        return Submission::create([
            'event_id' => $event->id,
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
                    'group_question_id' => $answerData['group_question_id'],
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
     * Grade a submission based on group's grading source (captain or admin).
     *
     * DUAL GRADING SYSTEM:
     * - If group uses 'captain' grading: Uses group_question_answers table
     * - If group uses 'admin' grading: Uses event_answers table
     */
    public function gradeSubmission(Submission $submission): void
    {
        $group = $submission->group;
        $userAnswers = $submission->userAnswers()->with('groupQuestion')->get();
        $totalScore = 0;
        $possiblePoints = 0;

        foreach ($userAnswers as $userAnswer) {
            $groupQuestion = $userAnswer->groupQuestion;

            if (!$groupQuestion) {
                // Skip if group question not found
                continue;
            }

            // Get correct answer based on grading source
            $correctAnswer = null;
            $isVoid = false;
            $pointsAwarded = null;

            if ($group->grading_source === 'captain') {
                // CAPTAIN GRADING: Use group_question_answers
                $groupAnswer = GroupQuestionAnswer::where('group_id', $group->id)
                    ->where('group_question_id', $groupQuestion->id)
                    ->first();

                if ($groupAnswer) {
                    $correctAnswer = $groupAnswer->correct_answer;
                    $isVoid = $groupAnswer->is_void;
                    $pointsAwarded = $groupAnswer->points_awarded;
                }
            } else {
                // ADMIN GRADING: Use event_answers
                if ($groupQuestion->event_question_id) {
                    $eventAnswer = EventAnswer::where('event_id', $submission->event_id)
                        ->where('event_question_id', $groupQuestion->event_question_id)
                        ->first();

                    if ($eventAnswer) {
                        $correctAnswer = $eventAnswer->correct_answer;
                        $isVoid = $eventAnswer->is_void;
                        // Admin answers don't have custom points_awarded
                    }
                }
            }

            // Skip if question is voided
            if ($isVoid) {
                $userAnswer->update([
                    'points_earned' => 0,
                    'is_correct' => false,
                ]);
                continue;
            }

            // Skip if no correct answer is set yet
            if (!$correctAnswer) {
                $userAnswer->update([
                    'points_earned' => 0,
                    'is_correct' => false,
                ]);
                // Still count towards possible points
                $possiblePoints += $this->calculateMaxPointsForQuestion($groupQuestion);
                continue;
            }

            // Check if answer is correct
            $isCorrect = false;
            $pointsEarned = 0;

            if ($this->compareAnswers($userAnswer->answer_text, $correctAnswer, $groupQuestion->question_type)) {
                $isCorrect = true;

                // Determine points earned
                if ($pointsAwarded !== null) {
                    // Use custom points if set
                    $pointsEarned = $pointsAwarded;
                } else {
                    // Calculate base + bonus for the chosen option
                    $pointsEarned = $this->calculatePointsForAnswer(
                        $groupQuestion,
                        $userAnswer->answer_text
                    );
                }
                $totalScore += $pointsEarned;
            }

            // Calculate possible points (max achievable for this question)
            if ($pointsAwarded !== null) {
                $questionMaxPoints = $pointsAwarded;
            } else {
                $questionMaxPoints = $this->calculateMaxPointsForQuestion($groupQuestion);
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
     * Recalculate score for a submission (alias for gradeSubmission).
     * Used when answers are updated after submission.
     */
    public function calculateScore(Submission $submission): void
    {
        $this->gradeSubmission($submission);
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
    protected function calculatePointsForAnswer(GroupQuestion $groupQuestion, string $answerText): int
    {
        $basePoints = $groupQuestion->points;
        $bonusPoints = 0;

        // For multiple choice, check if answer has bonus points
        if ($groupQuestion->question_type === 'multiple_choice' && $groupQuestion->options) {
            $options = is_string($groupQuestion->options) ? json_decode($groupQuestion->options, true) : $groupQuestion->options;

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
    protected function calculateMaxPointsForQuestion(GroupQuestion $groupQuestion): int
    {
        $basePoints = $groupQuestion->points;
        $maxBonus = 0;

        // For multiple choice, find the highest bonus
        if ($groupQuestion->question_type === 'multiple_choice' && $groupQuestion->options) {
            $options = is_string($groupQuestion->options) ? json_decode($groupQuestion->options, true) : $groupQuestion->options;

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
     * Get all submissions for an event.
     */
    public function getEventSubmissions(Event $event, bool $completedOnly = true)
    {
        $query = Submission::where('event_id', $event->id)
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
        // Check if event is past lock date
        if ($submission->event->lock_date && now()->isAfter($submission->event->lock_date)) {
            return false;
        }

        // Check if event status allows editing
        if (in_array($submission->event->status, ['completed', 'in_progress'])) {
            return false;
        }

        return true;
    }
}
