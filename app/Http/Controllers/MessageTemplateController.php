<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessageTemplateController extends Controller
{
    public function index(): View
    {
        $templates = MessageTemplate::orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        $categories = MessageTemplate::categories();

        return view('message-templates.index', compact('templates', 'categories'));
    }

    public function create(): View
    {
        $categories = MessageTemplate::categories();
        $availableVariables = MessageTemplate::availableVariables();

        return view('message-templates.create', compact('categories', 'availableVariables'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'slug'       => 'nullable|string|max:255|unique:message_templates,slug',
            'body'       => 'required|string|max:1600',
            'category'   => ['required', 'string', Rule::in(array_keys(MessageTemplate::categories()))],
            'is_active'  => 'boolean',
            'sort_order' => 'integer|min:0|max:9999',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Auto-detect variables used in the body
        $template = new MessageTemplate($validated);
        $validated['variables'] = $template->extractPlaceholders();

        MessageTemplate::create($validated);

        return redirect()->route('message-templates.index')
            ->with('success', 'Template "' . $validated['name'] . '" created.');
    }

    public function show(MessageTemplate $messageTemplate): View
    {
        $availableVariables = MessageTemplate::availableVariables();
        $placeholders = $messageTemplate->extractPlaceholders();

        // Build a preview with sample data
        $sampleVars = [];
        foreach ($placeholders as $key) {
            $sampleVars[$key] = isset($availableVariables[$key])
                ? '[' . $availableVariables[$key]['label'] . ']'
                : '[' . $key . ']';
        }
        $preview = $messageTemplate->render($sampleVars);

        return view('message-templates.show', compact('messageTemplate', 'availableVariables', 'placeholders', 'preview'));
    }

    public function edit(MessageTemplate $messageTemplate): View
    {
        $categories = MessageTemplate::categories();
        $availableVariables = MessageTemplate::availableVariables();

        return view('message-templates.edit', compact('messageTemplate', 'categories', 'availableVariables'));
    }

    public function update(Request $request, MessageTemplate $messageTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'slug'       => ['required', 'string', 'max:255', Rule::unique('message_templates', 'slug')->ignore($messageTemplate->id)],
            'body'       => 'required|string|max:1600',
            'category'   => ['required', 'string', Rule::in(array_keys(MessageTemplate::categories()))],
            'is_active'  => 'boolean',
            'sort_order' => 'integer|min:0|max:9999',
        ]);

        // Auto-detect variables
        $temp = new MessageTemplate(['body' => $validated['body']]);
        $validated['variables'] = $temp->extractPlaceholders();

        $messageTemplate->update($validated);

        return redirect()->route('message-templates.index')
            ->with('success', 'Template "' . $validated['name'] . '" updated.');
    }

    public function destroy(MessageTemplate $messageTemplate): RedirectResponse
    {
        $name = $messageTemplate->name;
        $messageTemplate->delete();

        return redirect()->route('message-templates.index')
            ->with('success', 'Template "' . $name . '" deleted.');
    }

    /**
     * AJAX: preview rendered text with user-supplied variable values.
     */
    public function preview(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'body'      => 'required|string|max:1600',
            'variables' => 'nullable|array',
        ]);

        $template = new MessageTemplate(['body' => $request->input('body')]);
        $rendered = $template->render($request->input('variables', []));

        return response()->json([
            'rendered'     => $rendered,
            'char_count'   => mb_strlen($rendered),
            'sms_segments' => (int) ceil(mb_strlen($rendered) / 160),
            'placeholders' => $template->extractPlaceholders(),
        ]);
    }
}
