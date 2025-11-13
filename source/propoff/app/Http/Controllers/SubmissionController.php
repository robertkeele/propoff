<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Submission;
use App\Models\UserAnswer;
use App\Models\GroupQuestionAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SubmissionController extends Controller
{
    /**
     * Display a listing of user's submissions.
     */
    public function index()
    {
        $submissions = Submission::with(['game', 'group'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return Inertia::render('Submissions/Index', [
            'submissions' => $submissions,
        ]);
    }

    /**
     * Start a new submission for a game.
     */
    public function start(Request $request, Game $game)
    {
        // Check if game is playable
        if ($game->status !== 'active') {
            return back()->with('error', 'This game is not currently available.');
        }

        if ($game->lock_date && $game->lock_date->isPast()) {
            return back()->with('error', 'This game is locked.');
        }

        $validated = $request->validate([
            'group_id' => 'nullable|exists:groups,id',
        ]);

        // Check if user already has an incomplete submission
        $existingSubmission = Submission::where('game_id', $game->id)
            ->where('user_id', auth()->id())
            ->where('group_id', $validated['group_id'] ?? null)
            ->where('is_complete', false)
            ->first();

        if ($existingSubmission) {
            return redirect()->route('submissions.continue', $existingSubmission);
        }

        // Create new submission
        $submission = Submission::create([
            'game_id' => $game->id,
            'user_id' => auth()->id(),
            'group_id' => $validated['group_id'] ?? null,
            'total_score' => 0,
            'possible_points' => $game->questions()->sum('points'),
            'percentage' => 0,
            'is_complete' => false,
        ]);

        return redirect()->route('submissions.continue', $submission);
    }

    /**
     * Continue working on an incomplete submission.
     */
    public function continue(Submission $submission)
    {
        $this->authorize('update', $submission);

        if ($submission->is_complete) {
            return redirect()->route('submissions.show', $submission);
        }

        $submission->load([
            'game.questions' => function ($query) {
                $query->orderBy('display_order');
            },
            'userAnswers',
        ]);

        return Inertia::render('Submissions/Continue', [
            'submission' => $submission,
        ]);
    }

    /**
     * Save answers for a submission.
     */
    public function saveAnswers(Request $request, Submission $submission)
    {
        $this->authorize('update', $submission);

        if ($submission->is_complete) {
            return back()->with('error', 'This submission is already complete.');
        }

        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer_text' => 'required|string',
        ]);

        DB::transaction(function () use ($submission, $validated) {
            foreach ($validated['answers'] as $answerData) {
                // Get the question to compare with group answer if available
                $question = $submission->game->questions()
                    ->findOrFail($answerData['question_id']);

                $isCorrect = false;
                $pointsEarned = 0;

                // Check if there's a group-specific correct answer
                if ($submission->group_id) {
                    $groupAnswer = GroupQuestionAnswer::where('group_id', $submission->group_id)
                        ->where('question_id', $question->id)
                        ->first();

                    if ($groupAnswer) {
                        $isCorrect = strcasecmp(
                            trim($answerData['answer_text']),
                            trim($groupAnswer->correct_answer)
                        ) === 0;

                        $pointsEarned = $isCorrect ? $question->points : 0;
                    }
                }

                // Update or create the user answer
                UserAnswer::updateOrCreate(
                    [
                        'submission_id' => $submission->id,
                        'question_id' => $answerData['question_id'],
                    ],
                    [
                        'answer_text' => $answerData['answer_text'],
                        'points_earned' => $pointsEarned,
                        'is_correct' => $isCorrect,
                    ]
                );
            }
        });

        return back()->with('success', 'Answers saved!');
    }

    /**
     * Submit/complete a submission.
     */
    public function submit(Submission $submission)
    {
        $this->authorize('update', $submission);

        if ($submission->is_complete) {
            return back()->with('error', 'This submission is already complete.');
        }

        // Calculate total score
        $totalScore = $submission->userAnswers()->sum('points_earned');
        $percentage = $submission->possible_points > 0
            ? ($totalScore / $submission->possible_points) * 100
            : 0;

        $submission->update([
            'total_score' => $totalScore,
            'percentage' => $percentage,
            'is_complete' => true,
            'submitted_at' => now(),
        ]);

        // Update leaderboard
        $this->updateLeaderboard($submission);

        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Submission completed!');
    }

    /**
     * Display the specified submission.
     */
    public function show(Submission $submission)
    {
        $this->authorize('view', $submission);

        $submission->load([
            'game.questions' => function ($query) {
                $query->orderBy('display_order');
            },
            'userAnswers.question',
            'user',
            'group',
        ]);

        return Inertia::render('Submissions/Show', [
            'submission' => $submission,
        ]);
    }

    /**
     * Update the leaderboard for a submission.
     */
    protected function updateLeaderboard(Submission $submission)
    {
        $answeredCount = $submission->userAnswers()->count();

        $leaderboard = \App\Models\Leaderboard::updateOrCreate(
            [
                'game_id' => $submission->game_id,
                'user_id' => $submission->user_id,
                'group_id' => $submission->group_id,
            ],
            [
                'total_score' => $submission->total_score,
                'possible_points' => $submission->possible_points,
                'percentage' => $submission->percentage,
                'answered_count' => $answeredCount,
            ]
        );
    }

    /**
     * Delete a submission (only if incomplete).
     */
    public function destroy(Submission $submission)
    {
        $this->authorize('delete', $submission);

        if ($submission->is_complete) {
            return back()->with('error', 'Cannot delete a completed submission.');
        }

        $submission->delete();

        return redirect()->route('submissions.index')
            ->with('success', 'Submission deleted.');
    }
}
