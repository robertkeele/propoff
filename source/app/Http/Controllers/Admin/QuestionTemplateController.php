<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionTemplateController extends Controller
{
    /**
     * Display a listing of question templates.
     */
    public function index(Request $request)
    {
        $query = QuestionTemplate::query();

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('question_text', 'like', "%{$request->search}%");
            });
        }

        $templates = $query->latest()->paginate(20);

        // Get all unique categories
        $categories = QuestionTemplate::distinct('category')
            ->pluck('category')
            ->filter()
            ->values();

        return Inertia::render('Admin/QuestionTemplates/Index', [
            'templates' => $templates,
            'categories' => $categories,
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create()
    {
        // Get existing categories for suggestions
        $categories = QuestionTemplate::distinct('category')
            ->pluck('category')
            ->filter()
            ->values();

        return Inertia::render('Admin/QuestionTemplates/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created template in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,yes_no,numeric,text',
            'default_options' => 'nullable|array',
            'variables' => 'nullable|array',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'default_points' => 'nullable|integer|min:1',
        ]);

        $template = QuestionTemplate::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.question-templates.index')
            ->with('success', 'Question template created successfully!');
    }

    /**
     * Display the specified template.
     */
    public function show(QuestionTemplate $question_template)
    {
        return Inertia::render('Admin/QuestionTemplates/Show', [
            'template' => $question_template,
        ]);
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(QuestionTemplate $question_template)
    {
        // Get existing categories for suggestions
        $categories = QuestionTemplate::distinct('category')
            ->pluck('category')
            ->filter()
            ->values();

        return Inertia::render('Admin/QuestionTemplates/Edit', [
            'template' => $question_template,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified template in storage.
     */
    public function update(Request $request, QuestionTemplate $question_template)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,yes_no,numeric,text',
            'default_options' => 'nullable|array',
            'variables' => 'nullable|array',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'default_points' => 'nullable|integer|min:1',
        ]);

        $question_template->update($validated);

        return redirect()->route('admin.question-templates.index')
            ->with('success', 'Question template updated successfully!');
    }

    /**
     * Remove the specified template from storage.
     */
    public function destroy(QuestionTemplate $question_template)
    {
        $templateTitle = $question_template->title;
        $question_template->delete();

        return redirect()->route('admin.question-templates.index')
            ->with('success', "Template '{$templateTitle}' deleted successfully!");
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(QuestionTemplate $template)
    {
        $newTemplate = $template->replicate();
        $newTemplate->title = $template->title . ' (Copy)';
        $newTemplate->save();

        return redirect()->route('admin.question-templates.edit', $newTemplate)
            ->with('success', 'Template duplicated successfully!');
    }

    /**
     * Preview template with variable substitution.
     */
    public function preview(Request $request, QuestionTemplate $template)
    {
        $variables = $request->input('variables', []);

        $questionText = $template->question_text;
        $options = $template->default_options ?? [];

        // Substitute variables
        foreach ($variables as $key => $value) {
            $questionText = str_replace("{{$key}}", $value, $questionText);

            // Substitute in options if they exist
            if (is_array($options)) {
                foreach ($options as &$option) {
                    $option = str_replace("{{$key}}", $value, $option);
                }
            }
        }

        return response()->json([
            'question_text' => $questionText,
            'options' => $options,
        ]);
    }
}
