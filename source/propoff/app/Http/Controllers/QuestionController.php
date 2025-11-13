<?php

namespace App\Http\Controllers;

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
        $this->authorize('view', $game);

        $questions = $game->questions()
            ->with('template')
            ->orderBy('display_order')
            ->get();

        return Inertia::render('Questions/Index', [
            'game' => $game,
            'questions' => $questions,
        ]);
    }

    /**
     * Show the form for creating a new question.
     */
    public function create(Game $game)
    {
        $this->authorize('update', $game);

        $templates = QuestionTemplate::where('created_by', auth()->id())
            ->orWhere('is_favorite', true)
            ->get();

        return Inertia::render('Questions/Create', [
            'game' => $game,
            'templates' => $templates,
        ]);
    }

    /**
     * Store a newly created question in storage.
     */
    public function store(Request $request, Game $game)
    {
        $this->authorize('update', $game);

        $validated = $request->validate([
            'template_id' => 'nullable|exists:question_templates,id',
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,true_false,short_answer,number',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'points' => 'required|integer|min:1',
        ]);

        // Get the next display order
        $maxOrder = $game->questions()->max('display_order') ?? 0;

        $question = $game->questions()->create([
            ...$validated,
            'display_order' => $maxOrder + 1,
        ]);

        return redirect()->route('games.questions.index', $game)
            ->with('success', 'Question added successfully!');
    }

    /**
     * Create a question from a template.
     */
    public function createFromTemplate(Game $game, QuestionTemplate $template)
    {
        $this->authorize('update', $game);

        $maxOrder = $game->questions()->max('display_order') ?? 0;

        $question = $game->questions()->create([
            'template_id' => $template->id,
            'question_text' => $template->question_text,
            'question_type' => $template->question_type,
            'options' => $template->default_options,
            'points' => $template->default_points,
            'display_order' => $maxOrder + 1,
        ]);

        return redirect()->route('games.questions.index', $game)
            ->with('success', 'Question created from template!');
    }

    /**
     * Display the specified question.
     */
    public function show(Game $game, Question $question)
    {
        $this->authorize('view', $game);

        $question->load('template', 'userAnswers', 'groupQuestionAnswers');

        return Inertia::render('Questions/Show', [
            'game' => $game,
            'question' => $question,
        ]);
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit(Game $game, Question $question)
    {
        $this->authorize('update', $game);

        return Inertia::render('Questions/Edit', [
            'game' => $game,
            'question' => $question,
        ]);
    }

    /**
     * Update the specified question in storage.
     */
    public function update(Request $request, Game $game, Question $question)
    {
        $this->authorize('update', $game);

        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,true_false,short_answer,number',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'points' => 'required|integer|min:1',
        ]);

        $question->update($validated);

        return redirect()->route('games.questions.index', $game)
            ->with('success', 'Question updated successfully!');
    }

    /**
     * Remove the specified question from storage.
     */
    public function destroy(Game $game, Question $question)
    {
        $this->authorize('update', $game);

        $question->delete();

        // Reorder remaining questions
        $game->questions()
            ->where('display_order', '>', $question->display_order)
            ->decrement('display_order');

        return redirect()->route('games.questions.index', $game)
            ->with('success', 'Question deleted successfully!');
    }

    /**
     * Reorder questions.
     */
    public function reorder(Request $request, Game $game)
    {
        $this->authorize('update', $game);

        $validated = $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'required|exists:questions,id',
            'questions.*.display_order' => 'required|integer|min:1',
        ]);

        foreach ($validated['questions'] as $questionData) {
            Question::where('id', $questionData['id'])
                ->where('game_id', $game->id)
                ->update(['display_order' => $questionData['display_order']]);
        }

        return back()->with('success', 'Questions reordered successfully!');
    }
}
