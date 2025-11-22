<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Captain\SetAnswerRequest;
use App\Models\Group;
use App\Models\GroupQuestion;
use App\Models\GroupQuestionAnswer;
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
     * Display the grading interface for the group.
     */
    public function index(Request $request, Group $group)
    {
        // Get all group questions with their answers
        $questions = $group->groupQuestions()
            ->active()
            ->with('groupQuestionAnswer')
            ->orderBy('display_order')
            ->get()
            ->map(function ($question) {
                $answer = $question->groupQuestionAnswer;

                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'options' => $question->options,
                    'points' => $question->points,
                    'order' => $question->display_order,
                    'is_custom' => $question->is_custom,
                    'answer' => $answer ? [
                        'id' => $answer->id,
                        'correct_answer' => $answer->correct_answer,
                        'points_awarded' => $answer->points_awarded,
                        'is_void' => $answer->is_void,
                    ] : null,
                ];
            });

        return Inertia::render('Captain/Grading/Index', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'grading_source' => $group->grading_source,
                'event' => [
                    'id' => $group->event->id,
                    'name' => $group->event->name,
                    'status' => $group->event->status,
                ],
            ],
            'questions' => $questions,
        ]);
    }

    /**
     * Set the correct answer for a specific question.
     */
    public function setAnswer(SetAnswerRequest $request, Group $group, GroupQuestion $groupQuestion)
    {
        // Ensure question belongs to this group
        if ($groupQuestion->group_id !== $group->id) {
            abort(404);
        }

        // Check if group uses captain grading
        if ($group->grading_source !== 'captain') {
            return back()->with('error', 'This group uses admin grading. Cannot set captain answers.');
        }

        // Create or update the answer
        GroupQuestionAnswer::updateOrCreate(
            [
                'group_id' => $group->id,
                'group_question_id' => $groupQuestion->id,
            ],
            [
                'question_id' => $groupQuestion->event_question_id, // Can be null for custom questions
                'correct_answer' => $request->correct_answer,
                'points_awarded' => $request->points_awarded,
                'is_void' => $request->is_void ?? false,
            ]
        );

        // Recalculate scores for all submissions in this group
        $submissions = $group->submissions()->where('is_complete', true)->get();
        foreach ($submissions as $submission) {
            $this->submissionService->calculateScore($submission);
        }

        // Update leaderboard
        $this->leaderboardService->updateLeaderboard($group->event, $group);

        return back()->with('success', 'Answer set successfully! Scores recalculated.');
    }

    /**
     * Bulk set answers for all questions.
     */
    public function bulkSetAnswers(Request $request, Group $group)
    {
        // Check if group uses captain grading
        if ($group->grading_source !== 'captain') {
            return back()->with('error', 'This group uses admin grading. Cannot set captain answers.');
        }

        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.group_question_id' => 'required|exists:group_questions,id',
            'answers.*.correct_answer' => 'required|string',
            'answers.*.points_awarded' => 'nullable|integer|min:0',
            'answers.*.is_void' => 'nullable|boolean',
        ]);

        foreach ($validated['answers'] as $answerData) {
            $groupQuestion = GroupQuestion::find($answerData['group_question_id']);

            // Ensure question belongs to this group
            if ($groupQuestion->group_id !== $group->id) {
                continue;
            }

            GroupQuestionAnswer::updateOrCreate(
                [
                    'group_id' => $group->id,
                    'group_question_id' => $groupQuestion->id,
                ],
                [
                    'question_id' => $groupQuestion->event_question_id,
                    'correct_answer' => $answerData['correct_answer'],
                    'points_awarded' => $answerData['points_awarded'] ?? null,
                    'is_void' => $answerData['is_void'] ?? false,
                ]
            );
        }

        // Recalculate scores for all submissions in this group
        $submissions = $group->submissions()->where('is_complete', true)->get();
        foreach ($submissions as $submission) {
            $this->submissionService->calculateScore($submission);
        }

        // Update leaderboard
        $this->leaderboardService->updateLeaderboard($group->event, $group);

        return back()->with('success', 'All answers set successfully! Scores recalculated.');
    }

    /**
     * Toggle void status for a question.
     */
    public function toggleVoid(Request $request, Group $group, GroupQuestion $groupQuestion)
    {
        // Ensure question belongs to this group
        if ($groupQuestion->group_id !== $group->id) {
            abort(404);
        }

        // Check if group uses captain grading
        if ($group->grading_source !== 'captain') {
            return back()->with('error', 'This group uses admin grading. Cannot modify captain answers.');
        }

        $answer = GroupQuestionAnswer::where('group_id', $group->id)
            ->where('group_question_id', $groupQuestion->id)
            ->first();

        if (!$answer) {
            return back()->with('error', 'No answer set for this question yet.');
        }

        $answer->update(['is_void' => !$answer->is_void]);

        // Recalculate scores
        $submissions = $group->submissions()->where('is_complete', true)->get();
        foreach ($submissions as $submission) {
            $this->submissionService->calculateScore($submission);
        }

        // Update leaderboard
        $this->leaderboardService->updateLeaderboard($group->event, $group);

        $status = $answer->is_void ? 'voided' : 'unvoided';

        return back()->with('success', "Question {$status} successfully! Scores recalculated.");
    }
}
