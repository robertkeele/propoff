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
            'name' => 'required|string|max:255',
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,yes_no,numeric,text',
            'options' => 'nullable|array',
            'variables' => 'nullable|array',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'default_points' => 'nullable|integer|min:1',
        ]);

        $template = QuestionTemplate::create($validated);

        return redirect()->route('admin.templates.index')
            ->with('success', 'Question template created successfully!');
    }

    /**
     * Display the specified template.
     */
    public function show(QuestionTemplate $template)
    {
        return Inertia::render('Admin/QuestionTemplates/Show', [
            'template' => $template,
        ]);
    }

    /**
     * Show the form for editing the specified template.
     */
    public function edit(QuestionTemplate $template)
    {
        // Get existing categories for suggestions
        $categories = QuestionTemplate::distinct('category')
            ->pluck('category')
            ->filter()
            ->values();

        return Inertia::render('Admin/QuestionTemplates/Edit', [
            'template' => $template,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified template in storage.
     */
    public function update(Request $request, QuestionTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,yes_no,numeric,text',
            'options' => 'nullable|array',
            'variables' => 'nullable|array',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'default_points' => 'nullable|integer|min:1',
        ]);

        $template->update($validated);

        return redirect()->route('admin.templates.show', $template)
            ->with('success', 'Question template updated successfully!');
    }

    /**
     * Remove the specified template from storage.
     */
    public function destroy(QuestionTemplate $template)
    {
        $templateName = $template->name;
        $template->delete();

        return redirect()->route('admin.templates.index')
            ->with('success', "Template '{$templateName}' deleted successfully!");
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(QuestionTemplate $template)
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->save();

        return redirect()->route('admin.templates.edit', $newTemplate)
            ->with('success', 'Template duplicated successfully!');
    }

    /**
     * Preview template with variable substitution.
     */
    public function preview(Request $request, QuestionTemplate $template)
    {
        $variables = $request->input('variables', []);

        $questionText = $template->question_text;
        $options = $template->options ?? [];

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
