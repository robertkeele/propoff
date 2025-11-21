<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventQuestion;
use App\Models\QuestionTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventQuestionController extends Controller
{
    /**
     * Display a listing of questions for an event.
     */
    public function index(Event $event)
    {
        $eventQuestions = $event->eventQuestions()
            ->orderBy('order')
            ->get();

        return Inertia::render('Admin/EventQuestions/Index', [
            'event' => $event,
            'eventQuestions' => $eventQuestions,
        ]);
    }

    /**
     * Show the form for creating a new question.
     */
    public function create(Event $event)
    {
        // Get available templates for this event's category
        $availableTemplates = $event->availableTemplates();

        // Get current questions
        $currentQuestions = $event->eventQuestions()->orderBy('order')->get();

        // Get next order
        $nextOrder = $event->eventQuestions()->max('order') + 1 ?? 1;

        return Inertia::render('Admin/EventQuestions/Create', [
            'event' => $event,
            'availableTemplates' => $availableTemplates,
            'currentQuestions' => $currentQuestions,
            'nextOrder' => $nextOrder,
        ]);
    }

    /**
     * Store a newly created question in storage.
     */
    public function store(Request $request, Event $event)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,yes_no,numeric,text',
            'options' => 'nullable|array',
            'points' => 'required|integer|min:1',
            'order' => 'required|integer|min:0',
            'template_id' => 'nullable|exists:question_templates,id',
        ]);

        $eventQuestion = $event->eventQuestions()->create($validated);

        // Create group questions for all groups in this event
        foreach ($event->groups as $group) {
            \App\Models\GroupQuestion::create([
                'group_id' => $group->id,
                'event_question_id' => $eventQuestion->id,
                'question_text' => $eventQuestion->question_text,
                'question_type' => $eventQuestion->question_type,
                'options' => $eventQuestion->options,
                'points' => $eventQuestion->points,
                'order' => $eventQuestion->order,
                'is_active' => true,
                'is_custom' => false,
            ]);
        }

        return redirect()->route('admin.events.event-questions.index', $event)
            ->with('success', 'Question added successfully!');
    }

    /**
     * Create question from template with variable substitution.
     */
    public function createFromTemplate(Request $request, Event $event, QuestionTemplate $template)
    {
        $validated = $request->validate([
            'variable_values' => 'nullable|array',
            'order' => 'required|integer|min:0',
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
                return [
                    'label' => $this->substituteVariables($option['label'], $variables),
                    'points' => $option['points'] ?? 0,
                ];
            }, $template->default_options);
        }

        // Create the question
        $eventQuestion = $event->eventQuestions()->create([
            'template_id' => $template->id,
            'question_text' => $questionText,
            'question_type' => $template->question_type,
            'options' => $options,
            'points' => $validated['points'] ?? $template->default_points,
            'order' => $validated['order'],
        ]);

        // Create group questions for all groups in this event
        foreach ($event->groups as $group) {
            \App\Models\GroupQuestion::create([
                'group_id' => $group->id,
                'event_question_id' => $eventQuestion->id,
                'question_text' => $eventQuestion->question_text,
                'question_type' => $eventQuestion->question_type,
                'options' => $eventQuestion->options,
                'points' => $eventQuestion->points,
                'order' => $eventQuestion->order,
                'is_active' => true,
                'is_custom' => false,
            ]);
        }

        return redirect()->route('admin.events.event-questions.index', $event)
            ->with('success', 'Question created from template successfully!');
    }

    /**
     * Display the specified question.
     */
    public function show(Event $event, EventQuestion $eventQuestion)
    {
        $eventQuestion->load('template', 'groupQuestions');

        return Inertia::render('Admin/EventQuestions/Show', [
            'event' => $event,
            'eventQuestion' => $eventQuestion,
        ]);
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit(Event $event, EventQuestion $eventQuestion)
    {
        $eventQuestion->load('template')->loadCount('groupQuestions');

        return Inertia::render('Admin/EventQuestions/Edit', [
            'event' => $event,
            'eventQuestion' => $eventQuestion,
        ]);
    }

    /**
     * Update the specified question in storage.
     */
    public function update(Request $request, Event $event, EventQuestion $eventQuestion)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,yes_no,numeric,text',
            'options' => 'nullable|array',
            'points' => 'required|integer|min:1',
            'order' => 'required|integer|min:0',
        ]);

        $eventQuestion->update($validated);

        // Update all associated group questions
        $eventQuestion->groupQuestions()->where('is_custom', false)->update([
            'question_text' => $validated['question_text'],
            'question_type' => $validated['question_type'],
            'options' => $validated['options'] ?? null,
            'points' => $validated['points'],
            'order' => $validated['order'],
        ]);

        return redirect()->route('admin.events.event-questions.index', $event)
            ->with('success', 'Question updated successfully!');
    }

    /**
     * Remove the specified question from storage.
     */
    public function destroy(Event $event, EventQuestion $eventQuestion)
    {
        $eventQuestion->delete();

        // Reorder remaining questions
        $this->reorderQuestionsAfterDelete($event, $eventQuestion->order);

        return redirect()->route('admin.events.event-questions.index', $event)
            ->with('success', 'Question deleted successfully!');
    }

    /**
     * Reorder questions (drag and drop).
     */
    public function reorder(Request $request, Event $event)
    {
        $validated = $request->validate([
            'event_questions' => 'required|array',
            'event_questions.*.id' => 'required|exists:event_questions,id',
            'event_questions.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['event_questions'] as $questionData) {
            EventQuestion::where('id', $questionData['id'])
                ->update(['order' => $questionData['order']]);

            // Update corresponding group questions
            \App\Models\GroupQuestion::where('event_question_id', $questionData['id'])
                ->where('is_custom', false)
                ->update(['order' => $questionData['order']]);
        }

        return back()->with('success', 'Questions reordered successfully!');
    }

    /**
     * Duplicate a question.
     */
    public function duplicate(Event $event, EventQuestion $eventQuestion)
    {
        $newEventQuestion = $eventQuestion->replicate();
        $newEventQuestion->order = $event->eventQuestions()->max('order') + 1;
        $newEventQuestion->save();

        // Create group questions for all groups
        foreach ($event->groups as $group) {
            \App\Models\GroupQuestion::create([
                'group_id' => $group->id,
                'event_question_id' => $newEventQuestion->id,
                'question_text' => $newEventQuestion->question_text,
                'question_type' => $newEventQuestion->question_type,
                'options' => $newEventQuestion->options,
                'points' => $newEventQuestion->points,
                'order' => $newEventQuestion->order,
                'is_active' => true,
                'is_custom' => false,
            ]);
        }

        return back()->with('success', 'Question duplicated successfully!');
    }

    /**
     * Bulk import questions from another event.
     */
    public function bulkImport(Request $request, Event $targetEvent)
    {
        $validated = $request->validate([
            'source_event_id' => 'required|exists:events,id',
            'event_question_ids' => 'required|array',
            'event_question_ids.*' => 'exists:event_questions,id',
        ]);

        $sourceQuestions = EventQuestion::whereIn('id', $validated['event_question_ids'])
            ->orderBy('order')
            ->get();

        $nextOrder = $targetEvent->eventQuestions()->max('order') + 1;

        foreach ($sourceQuestions as $question) {
            $newEventQuestion = $question->replicate();
            $newEventQuestion->event_id = $targetEvent->id;
            $newEventQuestion->order = $nextOrder++;
            $newEventQuestion->save();

            // Create group questions for all groups in target event
            foreach ($targetEvent->groups as $group) {
                \App\Models\GroupQuestion::create([
                    'group_id' => $group->id,
                    'event_question_id' => $newEventQuestion->id,
                    'question_text' => $newEventQuestion->question_text,
                    'question_type' => $newEventQuestion->question_type,
                    'options' => $newEventQuestion->options,
                    'points' => $newEventQuestion->points,
                    'order' => $newEventQuestion->order,
                    'is_active' => true,
                    'is_custom' => false,
                ]);
            }
        }

        return redirect()->route('admin.events.event-questions.index', $targetEvent)
            ->with('success', count($sourceQuestions) . ' questions imported successfully!');
    }

    /**
     * Create multiple questions from templates at once.
     */
    public function bulkCreateFromTemplates(Request $request, Event $event)
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
                    return [
                        'label' => $this->substituteVariables($option['label'], $vars),
                        'points' => $option['points'] ?? 0,
                    ];
                }, $template->default_options);
            }

            // Create question
            $eventQuestion = $event->eventQuestions()->create([
                'template_id' => $template->id,
                'question_text' => $questionText,
                'question_type' => $template->question_type,
                'options' => $options,
                'points' => $template->default_points,
                'order' => $startingOrder + $index,
            ]);

            // Create group questions for all groups
            foreach ($event->groups as $group) {
                \App\Models\GroupQuestion::create([
                    'group_id' => $group->id,
                    'event_question_id' => $eventQuestion->id,
                    'question_text' => $eventQuestion->question_text,
                    'question_type' => $eventQuestion->question_type,
                    'options' => $eventQuestion->options,
                    'points' => $eventQuestion->points,
                    'order' => $eventQuestion->order,
                    'is_active' => true,
                    'is_custom' => false,
                ]);
            }
        }

        return redirect()->route('admin.events.event-questions.index', $event)
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
    protected function reorderQuestionsAfterDelete(Event $event, int $deletedOrder)
    {
        $event->eventQuestions()
            ->where('order', '>', $deletedOrder)
            ->decrement('order');
    }
}
