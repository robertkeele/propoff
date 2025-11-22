<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Captain\StoreGroupQuestionRequest;
use App\Http\Requests\Captain\UpdateGroupQuestionRequest;
use App\Models\Group;
use App\Models\GroupQuestion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupQuestionController extends Controller
{
    /**
     * Display a listing of questions for the group.
     */
    public function index(Request $request, Group $group)
    {
        $questions = $group->groupQuestions()
            ->with('eventQuestion')
            ->orderBy('display_order')
            ->get()
            ->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'options' => $question->options,
                    'points' => $question->points,
                    'display_order' => $question->display_order,
                    'is_active' => $question->is_active,
                    'is_custom' => $question->is_custom,
                    'event_question_id' => $question->event_question_id,
                    'has_answer' => $question->groupQuestionAnswer()->exists(),
                ];
            });

        return Inertia::render('Captain/Questions/Index', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'event' => $group->event ? [
                    'id' => $group->event->id,
                    'name' => $group->event->name,
                ] : null,
            ],
            'questions' => $questions,
        ]);
    }

    /**
     * Show the form for creating a new custom question.
     */
    public function create(Request $request, Group $group)
    {
        $nextOrder = $group->groupQuestions()->max('display_order') + 1 ?? 1;

        return Inertia::render('Captain/Questions/Create', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'event' => $group->event ? [
                    'id' => $group->event->id,
                    'name' => $group->event->name,
                ] : null,
            ],
            'currentQuestionCount' => $group->groupQuestions()->count(),
        ]);
    }

    /**
     * Store a newly created custom question.
     */
    public function store(StoreGroupQuestionRequest $request, Group $group)
    {
        $groupQuestion = GroupQuestion::create([
            'group_id' => $group->id,
            'event_question_id' => null, // Custom question, not linked to event question
            'question_text' => $request->question_text,
            'question_type' => $request->question_type,
            'options' => $request->options,
            'points' => $request->points,
            'display_order' => $request->display_order ?? ($group->groupQuestions()->max('display_order') + 1 ?? 1),
            'is_active' => true,
            'is_custom' => true, // Mark as custom
        ]);

        return redirect()->route('captain.groups.questions.index', $group)
            ->with('success', 'Custom question added successfully!');
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit(Request $request, Group $group, GroupQuestion $groupQuestion)
    {
        // Ensure question belongs to this group
        if ($groupQuestion->group_id !== $group->id) {
            abort(404);
        }

        return Inertia::render('Captain/Questions/Edit', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'event' => $group->event ? [
                    'id' => $group->event->id,
                    'name' => $group->event->name,
                ] : null,
            ],
            'question' => [
                'id' => $groupQuestion->id,
                'question_text' => $groupQuestion->question_text,
                'question_type' => $groupQuestion->question_type,
                'options' => $groupQuestion->options,
                'points' => $groupQuestion->points,
                'display_order' => $groupQuestion->display_order,
                'is_active' => $groupQuestion->is_active,
                'is_custom' => $groupQuestion->is_custom,
            ],
        ]);
    }

    /**
     * Update the specified question.
     */
    public function update(UpdateGroupQuestionRequest $request, Group $group, GroupQuestion $groupQuestion)
    {
        // Ensure question belongs to this group
        if ($groupQuestion->group_id !== $group->id) {
            abort(404);
        }

        $groupQuestion->update([
            'question_text' => $request->question_text,
            'question_type' => $request->question_type,
            'options' => $request->options,
            'points' => $request->points,
            'display_order' => $request->display_order ?? $groupQuestion->display_order,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('captain.groups.questions.index', $group)
            ->with('success', 'Question updated successfully!');
    }

    /**
     * Remove the specified question (or deactivate it).
     */
    public function destroy(Request $request, Group $group, GroupQuestion $groupQuestion)
    {
        // Ensure question belongs to this group
        if ($groupQuestion->group_id !== $group->id) {
            abort(404);
        }

        // Check if question has user answers
        if ($groupQuestion->userAnswers()->exists()) {
            // Deactivate instead of delete if there are user answers
            $groupQuestion->update(['is_active' => false]);
            return back()->with('success', 'Question deactivated (has user answers).');
        }

        // Delete if no user answers
        $groupQuestion->delete();

        // Reorder remaining questions
        $group->groupQuestions()
            ->where('display_order', '>', $groupQuestion->display_order)
            ->decrement('display_order');

        return redirect()->route('captain.groups.questions.index', $group)
            ->with('success', 'Question deleted successfully!');
    }

    /**
     * Toggle question active status.
     */
    public function toggleActive(Request $request, Group $group, GroupQuestion $groupQuestion)
    {
        // Ensure question belongs to this group
        if ($groupQuestion->group_id !== $group->id) {
            abort(404);
        }

        $groupQuestion->update([
            'is_active' => !$groupQuestion->is_active,
        ]);

        $status = $groupQuestion->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Question {$status} successfully!");
    }

    /**
     * Reorder questions.
     */
    public function reorder(Request $request, Group $group)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|exists:group_questions,id',
        ]);

        // Update display_order for each question based on array position
        foreach ($validated['order'] as $index => $questionId) {
            GroupQuestion::where('id', $questionId)
                ->where('group_id', $group->id)
                ->update(['display_order' => $index + 1]);
        }

        return back()->with('success', 'Questions reordered successfully!');
    }

    /**
     * Duplicate a question.
     */
    public function duplicate(Request $request, Group $group, GroupQuestion $groupQuestion)
    {
        // Ensure question belongs to this group
        if ($groupQuestion->group_id !== $group->id) {
            abort(404);
        }

        $newQuestion = $groupQuestion->replicate();
        $newQuestion->display_order = $group->groupQuestions()->max('display_order') + 1;
        $newQuestion->is_custom = true; // Duplicated questions become custom
        $newQuestion->event_question_id = null; // Unlink from event question
        $newQuestion->save();

        return back()->with('success', 'Question duplicated successfully!');
    }
}
