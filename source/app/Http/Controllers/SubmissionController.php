<?php

namespace App\Http\Controllers;

use App\Models\Event;
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
        $submissions = Submission::with(['event', 'group'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return Inertia::render('Submissions/Index', [
            'submissions' => $submissions,
        ]);
    }

    /**
     * Start a new submission for an event.
     */
    public function start(Request $request, Event $event)
    {
        // Check if event is playable
        if ($event->status !== 'open') {
            return back()->with('error', 'This event is not currently available.');
        }

        if ($event->lock_date && $event->lock_date->isPast()) {
            return back()->with('error', 'This event is locked.');
        }

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
        ]);

        // Check if user is a member of the selected group
        $group = \App\Models\Group::findOrFail($validated['group_id']);
        if (!auth()->user()->groups()->where('groups.id', $group->id)->exists()) {
            return back()->with('error', 'You are not a member of the selected group.');
        }

        // Check if group belongs to this event
        if ($group->event_id !== $event->id) {
            return back()->with('error', 'This group is not participating in this event.');
        }

        // Check if user already has an incomplete submission
        $existingSubmission = Submission::where('event_id', $event->id)
            ->where('user_id', auth()->id())
            ->where('group_id', $validated['group_id'])
            ->where('is_complete', false)
            ->first();

        if ($existingSubmission) {
            return redirect()->route('submissions.continue', $existingSubmission);
        }

        // Calculate possible points from group's active questions
        $possiblePoints = $group->groupQuestions()
            ->where('is_active', true)
            ->sum('points');

        // Create new submission
        $submission = Submission::create([
            'event_id' => $event->id,
            'user_id' => auth()->id(),
            'group_id' => $validated['group_id'],
            'total_score' => 0,
            'possible_points' => $possiblePoints,
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
            'event',
            'group.groupQuestions' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('display_order');
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
            'answers.*.group_question_id' => 'required|exists:group_questions,id',
            'answers.*.answer_text' => 'required|string',
        ]);

        DB::transaction(function () use ($submission, $validated) {
            foreach ($validated['answers'] as $answerData) {
                // Get the group question
                $groupQuestion = $submission->group->groupQuestions()
                    ->findOrFail($answerData['group_question_id']);

                $isCorrect = false;
                $pointsEarned = 0;

                // Check if there's a correct answer set by the captain/admin
                $groupAnswer = GroupQuestionAnswer::where('group_id', $submission->group_id)
                    ->where('group_question_id', $groupQuestion->id)
                    ->first();

                if ($groupAnswer) {
                    $isCorrect = strcasecmp(
                        trim($answerData['answer_text']),
                        trim($groupAnswer->correct_answer)
                    ) === 0;

                    $pointsEarned = $isCorrect ? $groupQuestion->points : 0;
                }

                // Update or create the user answer
                UserAnswer::updateOrCreate(
                    [
                        'submission_id' => $submission->id,
                        'group_question_id' => $answerData['group_question_id'],
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
            'event',
            'group.groupQuestions' => function ($query) {
                $query->orderBy('display_order');
            },
            'userAnswers.groupQuestion',
            'user',
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

        // Calculate rank within the group for this event
        $rank = \App\Models\Leaderboard::where('event_id', $submission->event_id)
            ->where('group_id', $submission->group_id)
            ->where(function ($query) use ($submission) {
                $query->where('total_score', '>', $submission->total_score)
                    ->orWhere(function ($q) use ($submission) {
                        $q->where('total_score', '=', $submission->total_score)
                            ->where('percentage', '>', $submission->percentage);
                    });
            })
            ->count() + 1;

        $leaderboard = \App\Models\Leaderboard::updateOrCreate(
            [
                'event_id' => $submission->event_id,
                'user_id' => $submission->user_id,
                'group_id' => $submission->group_id,
            ],
            [
                'rank' => $rank,
                'total_score' => $submission->total_score,
                'possible_points' => $submission->possible_points,
                'percentage' => $submission->percentage,
                'answered_count' => $answeredCount,
            ]
        );

        // Recalculate ranks for all entries in this group/event
        $this->recalculateRanks($submission->event_id, $submission->group_id);
    }

    /**
     * Recalculate ranks for all users in a group for an event.
     */
    protected function recalculateRanks($eventId, $groupId)
    {
        $leaderboards = \App\Models\Leaderboard::where('event_id', $eventId)
            ->where('group_id', $groupId)
            ->orderBy('total_score', 'desc')
            ->orderBy('percentage', 'desc')
            ->get();

        $currentRank = 1;
        foreach ($leaderboards as $leaderboard) {
            $leaderboard->update(['rank' => $currentRank]);
            $currentRank++;
        }
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

    /**
     * Show submission confirmation page with personal link
     */
    public function confirmation(Submission $submission)
    {
        // Verify user owns this submission
        if ($submission->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to submission.');
        }

        $submission->load(['event', 'group', 'user']);

        // Get personal link for guests
        $personalLink = null;
        if (auth()->user()->isGuest() && auth()->user()->guest_token) {
            $personalLink = route('guest.results', auth()->user()->guest_token);
        }

        return Inertia::render('Submissions/Confirmation', [
            'submission' => [
                'id' => $submission->id,
                'event_id' => $submission->event_id,
                'total_score' => $submission->total_score,
                'possible_points' => $submission->possible_points,
                'percentage' => $submission->percentage,
                'submitted_at' => $submission->submitted_at,
            ],
            'event' => [
                'id' => $submission->event->id,
                'name' => $submission->event->name,
            ],
            'group' => [
                'id' => $submission->group->id,
                'name' => $submission->group->name,
            ],
            'personalLink' => $personalLink,
        ]);
    }
}
