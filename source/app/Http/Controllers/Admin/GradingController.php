<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupQuestionAnswer;
use App\Models\Question;
use App\Services\SubmissionService;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GradingController extends Controller
{
    protected $submissionService;
    protected $leaderboardService;

    public function __construct(SubmissionService $submissionService, LeaderboardService $leaderboardService)
    {
        $this->submissionService = $submissionService;
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Display grading interface for a game.
     */
    public function index(Game $game)
    {
        // Get all questions for this game
        $questions = $game->questions()
            ->orderBy('display_order')
            ->get();

        // Get all groups (admins need to set answers before anyone submits)
        $groups = Group::withCount('users')->orderBy('name')->get();

        // Get all group question answers for this game
        $groupAnswers = GroupQuestionAnswer::whereIn('question_id', $questions->pluck('id'))
            ->with(['group', 'question'])
            ->get()
            ->groupBy('group_id');

        return Inertia::render('Admin/Grading/Index', [
            'game' => $game,
            'questions' => $questions,
            'groups' => $groups,
            'groupAnswers' => $groupAnswers,
        ]);
    }

    /**
     * Set group-specific correct answer for a question.
     */
    public function setAnswer(Request $request, Game $game, Question $question)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'correct_answer' => 'required|string',
            'points_awarded' => 'nullable|integer|min:0',
            'is_void' => 'nullable|boolean',
        ]);

        GroupQuestionAnswer::updateOrCreate(
            [
                'group_id' => $validated['group_id'],
                'question_id' => $question->id,
            ],
            [
                'correct_answer' => $validated['correct_answer'],
                'points_awarded' => $validated['points_awarded'] ?? null,
                'is_void' => $validated['is_void'] ?? false,
            ]
        );

        return back()->with('success', 'Answer set successfully!');
    }

    /**
     * Bulk set answers for all questions in a group.
     */
    public function bulkSetAnswers(Request $request, Game $game, Group $group)
    {
        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.correct_answer' => 'required|string',
            'answers.*.points_awarded' => 'nullable|integer|min:0',
            'answers.*.is_void' => 'nullable|boolean',
        ]);

        foreach ($validated['answers'] as $answerData) {
            GroupQuestionAnswer::updateOrCreate(
                [
                    'group_id' => $group->id,
                    'question_id' => $answerData['question_id'],
                ],
                [
                    'correct_answer' => $answerData['correct_answer'],
                    'points_awarded' => $answerData['points_awarded'] ?? null,
                    'is_void' => $answerData['is_void'] ?? false,
                ]
            );
        }

        return back()->with('success', 'Answers set successfully for group!');
    }

    /**
     * Mark/unmark a question as void for a specific group.
     */
    public function toggleVoid(Request $request, Game $game, Question $question, Group $group)
    {
        $answer = GroupQuestionAnswer::firstOrCreate(
            [
                'group_id' => $group->id,
                'question_id' => $question->id,
            ],
            [
                'correct_answer' => '',
                'is_void' => false,
            ]
        );

        $answer->update(['is_void' => !$answer->is_void]);

        $status = $answer->is_void ? 'voided' : 'unvoided';
        return back()->with('success', "Question {$status} successfully!");
    }

    /**
     * Trigger score calculation for all submissions in the game.
     */
    public function calculateScores(Game $game)
    {
        $submissions = $game->submissions()->where('is_complete', true)->get();
        $gradedCount = 0;

        foreach ($submissions as $submission) {
            $this->submissionService->gradeSubmission($submission);
            $gradedCount++;
        }

        // Update leaderboards
        $this->leaderboardService->recalculateGameLeaderboards($game);

        return back()->with('success', "Calculated scores for {$gradedCount} submissions and updated leaderboards!");
    }

    /**
     * Export game results to CSV.
     */
    public function exportCSV(Game $game)
    {
        $submissions = $game->submissions()
            ->with(['user', 'group', 'userAnswers.question'])
            ->where('is_complete', true)
            ->get();

        $filename = "game_{$game->id}_results_" . now()->format('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($submissions, $game) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Submission ID',
                'User Name',
                'User Email',
                'Group Name',
                'Total Score',
                'Possible Points',
                'Percentage',
                'Submitted At',
                'Questions Answered',
            ]);

            // Data rows
            foreach ($submissions as $submission) {
                fputcsv($file, [
                    $submission->id,
                    $submission->user->name,
                    $submission->user->email,
                    $submission->group->name,
                    $submission->total_score,
                    $submission->possible_points,
                    round($submission->percentage, 2) . '%',
                    $submission->submitted_at->format('Y-m-d H:i:s'),
                    $submission->userAnswers->count(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export detailed answers to CSV.
     */
    public function exportDetailedCSV(Game $game, Group $group = null)
    {
        $query = $game->submissions()
            ->with(['user', 'group', 'userAnswers.question', 'userAnswers.question.groupQuestionAnswers'])
            ->where('is_complete', true);

        if ($group) {
            $query->where('group_id', $group->id);
        }

        $submissions = $query->get();

        $filename = "game_{$game->id}_detailed_" . ($group ? "group_{$group->id}_" : '') . now()->format('Y-m-d_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($submissions) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Submission ID',
                'User Name',
                'Group Name',
                'Question #',
                'Question Text',
                'User Answer',
                'Correct Answer',
                'Is Correct',
                'Points Earned',
                'Max Points',
                'Is Void',
            ]);

            // Data rows
            foreach ($submissions as $submission) {
                foreach ($submission->userAnswers as $userAnswer) {
                    $question = $userAnswer->question;

                    // Get group-specific correct answer
                    $groupAnswer = $question->groupQuestionAnswers
                        ->where('group_id', $submission->group_id)
                        ->first();

                    // Use group-specific points if set, otherwise default question points
                    $maxPoints = ($groupAnswer && $groupAnswer->points_awarded !== null)
                        ? $groupAnswer->points_awarded
                        : $question->points;

                    fputcsv($file, [
                        $submission->id,
                        $submission->user->name,
                        $submission->group->name,
                        $question->display_order,
                        $question->question_text,
                        $userAnswer->answer_text,
                        $groupAnswer->correct_answer ?? 'Not Set',
                        $userAnswer->is_correct ? 'Yes' : 'No',
                        $userAnswer->points_earned,
                        $maxPoints,
                        $groupAnswer && $groupAnswer->is_void ? 'Yes' : 'No',
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * View grading summary for a specific group.
     */
    public function groupSummary(Game $game, Group $group)
    {
        $questions = $game->questions()->orderBy('display_order')->get();

        // Get group-specific answers
        $groupAnswers = GroupQuestionAnswer::where('group_id', $group->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->get()
            ->keyBy('question_id');

        // Get submission statistics for this group
        $submissions = $game->submissions()
            ->where('group_id', $group->id)
            ->where('is_complete', true)
            ->get();

        $stats = [
            'total_submissions' => $submissions->count(),
            'average_score' => $submissions->avg('percentage'),
            'highest_score' => $submissions->max('percentage'),
            'lowest_score' => $submissions->min('percentage'),
        ];

        return Inertia::render('Admin/Grading/GroupSummary', [
            'game' => $game,
            'group' => $group,
            'questions' => $questions,
            'groupAnswers' => $groupAnswers,
            'stats' => $stats,
        ]);
    }
}
