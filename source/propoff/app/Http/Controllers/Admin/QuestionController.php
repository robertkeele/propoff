<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Question;
use App\Models\QuestionTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionController extends Controller
{
    /**
     * Display a listing of questions for a game.
     */
    public function index(Game $game)
    {
        $questions = $game->questions()
            ->orderBy('display_order')
            ->get();

        return Inertia::render('Admin/Questions/Index', [
            'game' => $game,
            'questions' => $questions,
        ]);
    }

    /**
     * Show the form for creating a new question.
     */
    public function create(Game $game)
    {
        // Get all templates for selection
        $templates = QuestionTemplate::orderBy('name')->get();

        // Get next display order
        $nextOrder = $game->questions()->max('display_order') + 1;

        return Inertia::render('Admin/Questions/Create', [
            'game' => $game,
            'templates' => $templates,
            'nextOrder' => $nextOrder,
        ]);
    }

    /**
     * Store a newly created question in storage.
     */
    public function store(Request $request, Game $game)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,yes_no,numeric,text',
            'options' => 'nullable|array',
            'points' => 'required|integer|min:1',
            'display_order' => 'required|integer|min:0',
            'template_id' => 'nullable|exists:question_templates,id',
        ]);

        $question = $game->questions()->create($validated);

        return redirect()->route('admin.games.questions.index', $game)
            ->with('success', 'Question added successfully!');
    }

    /**
     * Create question from template.
     */
    public function createFromTemplate(Request $request, Game $game, QuestionTemplate $template)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'options' => 'nullable|array',
            'points' => 'nullable|integer|min:1',
        ]);

        // Get next display order
        $nextOrder = $game->questions()->max('display_order') + 1;

        $question = $game->questions()->create([
            'question_text' => $validated['question_text'],
            'question_type' => $template->question_type,
            'options' => $validated['options'] ?? $template->options,
            'points' => $validated['points'] ?? $template->default_points ?? 10,
            'display_order' => $nextOrder,
            'template_id' => $template->id,
        ]);

        return redirect()->route('admin.games.questions.index', $game)
            ->with('success', 'Question created from template successfully!');
    }

    /**
     * Display the specified question.
     */
    public function show(Game $game, Question $question)
    {
        $question->load('template');

        return Inertia::render('Admin/Questions/Show', [
            'game' => $game,
            'question' => $question,
        ]);
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit(Game $game, Question $question)
    {
        $question->load('template');

        return Inertia::render('Admin/Questions/Edit', [
            'game' => $game,
            'question' => $question,
        ]);
    }

    /**
     * Update the specified question in storage.
     */
    public function update(Request $request, Game $game, Question $question)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,yes_no,numeric,text',
            'options' => 'nullable|array',
            'points' => 'required|integer|min:1',
            'display_order' => 'required|integer|min:0',
        ]);

        $question->update($validated);

        return redirect()->route('admin.games.questions.index', $game)
            ->with('success', 'Question updated successfully!');
    }

    /**
     * Remove the specified question from storage.
     */
    public function destroy(Game $game, Question $question)
    {
        $question->delete();

        // Reorder remaining questions
        $this->reorderQuestionsAfterDelete($game, $question->display_order);

        return redirect()->route('admin.games.questions.index', $game)
            ->with('success', 'Question deleted successfully!');
    }

    /**
     * Reorder questions (drag and drop).
     */
    public function reorder(Request $request, Game $game)
    {
        $validated = $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'required|exists:questions,id',
            'questions.*.display_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['questions'] as $questionData) {
            Question::where('id', $questionData['id'])
                ->update(['display_order' => $questionData['display_order']]);
        }

        return back()->with('success', 'Questions reordered successfully!');
    }

    /**
     * Duplicate a question.
     */
    public function duplicate(Game $game, Question $question)
    {
        $newQuestion = $question->replicate();
        $newQuestion->display_order = $game->questions()->max('display_order') + 1;
        $newQuestion->save();

        return back()->with('success', 'Question duplicated successfully!');
    }

    /**
     * Bulk import questions from another game.
     */
    public function bulkImport(Request $request, Game $targetGame)
    {
        $validated = $request->validate([
            'source_game_id' => 'required|exists:games,id',
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:questions,id',
        ]);

        $sourceQuestions = Question::whereIn('id', $validated['question_ids'])
            ->orderBy('display_order')
            ->get();

        $nextOrder = $targetGame->questions()->max('display_order') + 1;

        foreach ($sourceQuestions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->game_id = $targetGame->id;
            $newQuestion->display_order = $nextOrder++;
            $newQuestion->save();
        }

        return redirect()->route('admin.games.questions.index', $targetGame)
            ->with('success', count($sourceQuestions) . ' questions imported successfully!');
    }

    /**
     * Reorder questions after deletion.
     */
    protected function reorderQuestionsAfterDelete(Game $game, int $deletedOrder)
    {
        $game->questions()
            ->where('display_order', '>', $deletedOrder)
            ->decrement('display_order');
    }
}
