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
        // Get available templates for this game's category
        $availableTemplates = $game->availableTemplates();

        // Get current questions
        $currentQuestions = $game->questions()->orderBy('display_order')->get();

        // Get next display order
        $nextOrder = $game->questions()->max('display_order') + 1 ?? 1;

        return Inertia::render('Admin/Questions/Create', [
            'game' => $game,
            'availableTemplates' => $availableTemplates,
            'currentQuestions' => $currentQuestions,
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
     * Create question from template with variable substitution.
     */
    public function createFromTemplate(Request $request, Game $game, QuestionTemplate $template)
    {
        $validated = $request->validate([
            'variable_values' => 'nullable|array',
            'display_order' => 'required|integer|min:0',
            'points' => 'nullable|integer|min:1',
        ]);

        $variables = $validated['variable_values'] ?? [];

        // Substitute variables in question text
        $questionText = $this->substituteVariables(
            $template->question_text,
            $variables
        );

        // Prepare options if multiple choice
        $options = null;
        if ($template->question_type === 'multiple_choice' && $template->default_options) {
            $options = array_map(function ($option) use ($variables) {
                // Handle new format: {label: "...", points: 0}
                if (is_array($option) && isset($option['label'])) {
                    return [
                        'label' => $this->substituteVariables($option['label'], $variables),
                        'points' => $option['points'] ?? 0,
                    ];
                }
                // Handle old format (string) for backward compatibility
                return $this->substituteVariables($option, $variables);
            }, $template->default_options);
        }

        // Create the question
        $question = $game->questions()->create([
            'template_id' => $template->id,
            'question_text' => $questionText,
            'question_type' => $template->question_type,
            'options' => $options,
            'points' => $validated['points'] ?? $template->default_points,
            'display_order' => $validated['display_order'],
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
        $question->load('template')->loadCount('userAnswers');

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
     * Create multiple questions from templates at once.
     */
    public function bulkCreateFromTemplates(Request $request, Game $game)
    {
        $validated = $request->validate([
            'templates' => 'required|array|min:1',
            'templates.*' => 'integer|exists:question_templates,id',
            'variable_values' => 'nullable|array',
            'starting_order' => 'required|integer|min:0',
        ]);

        $startingOrder = $validated['starting_order'];
        $templateIds = $validated['templates'];
        $variableValues = $validated['variable_values'] ?? [];

        foreach ($templateIds as $index => $templateId) {
            $template = QuestionTemplate::find($templateId);

            if (!$template) {
                continue;  // Skip if template not found
            }

            // Get variables for this specific template if provided
            $vars = $variableValues[$templateId] ?? [];

            // Substitute variables
            $questionText = $this->substituteVariables($template->question_text, $vars);

            $options = null;
            if ($template->question_type === 'multiple_choice' && $template->default_options) {
                $options = array_map(function ($option) use ($vars) {
                    // Handle new format: {label: "...", points: 0}
                    if (is_array($option) && isset($option['label'])) {
                        return [
                            'label' => $this->substituteVariables($option['label'], $vars),
                            'points' => $option['points'] ?? 0,
                        ];
                    }
                    // Handle old format (string) for backward compatibility
                    return $this->substituteVariables($option, $vars);
                }, $template->default_options);
            }

            // Create question
            $game->questions()->create([
                'template_id' => $template->id,
                'question_text' => $questionText,
                'question_type' => $template->question_type,
                'options' => $options,
                'points' => $template->default_points,
                'display_order' => $startingOrder + $index,
            ]);
        }

        return redirect()->route('admin.games.questions.index', $game)
            ->with('success', count($templateIds) . ' questions created from templates!');
    }

    /**
     * Replace {variable} with actual values.
     */
    private function substituteVariables(string $text, array $variables): string
    {
        foreach ($variables as $name => $value) {
            $text = str_replace("{{$name}}", $value, $text);
        }
        return $text;
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
