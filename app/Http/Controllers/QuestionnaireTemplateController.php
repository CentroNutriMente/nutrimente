<?php

namespace App\Http\Controllers;

use App\Models\QuestionnaireTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionnaireTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $templates = QuestionnaireTemplate::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Questionnaires/Templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Questionnaires/Templates/Form', [
            'template' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                        => 'required|string|max:255',
            'description'                 => 'nullable|string|max:1000',
            'questions'                   => 'required|array',
            'questions.*.text'            => 'required|string',
            'questions.*.type'            => 'required|in:scale,yesno,text',
            'questions.*.options'         => 'nullable|array',
            'questions.*.options.*.label' => 'nullable|string',
            'questions.*.options.*.value' => 'nullable|integer',
        ]);

        $questions = collect($validated['questions'])->values()->map(fn ($q, $i) => array_merge($q, [
            'id' => 'q' . ($i + 1),
        ]))->all();

        QuestionnaireTemplate::create([
            'user_id'     => $request->user()->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'questions'   => $questions,
        ]);

        return redirect()->route('questionnaire-templates.index')
            ->with('success', 'Modello creato.');
    }

    public function edit(Request $request, QuestionnaireTemplate $questionnaireTemplate): Response
    {
        abort_if($questionnaireTemplate->user_id !== $request->user()->id, 403);

        return Inertia::render('Questionnaires/Templates/Form', [
            'template' => $questionnaireTemplate,
        ]);
    }

    public function update(Request $request, QuestionnaireTemplate $questionnaireTemplate): RedirectResponse
    {
        abort_if($questionnaireTemplate->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name'                        => 'required|string|max:255',
            'description'                 => 'nullable|string|max:1000',
            'questions'                   => 'required|array',
            'questions.*.text'            => 'required|string',
            'questions.*.type'            => 'required|in:scale,yesno,text',
            'questions.*.options'         => 'nullable|array',
            'questions.*.options.*.label' => 'nullable|string',
            'questions.*.options.*.value' => 'nullable|integer',
        ]);

        $questions = collect($validated['questions'])->values()->map(fn ($q, $i) => array_merge($q, [
            'id' => 'q' . ($i + 1),
        ]))->all();

        $questionnaireTemplate->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'questions'   => $questions,
        ]);

        return redirect()->route('questionnaire-templates.index')
            ->with('success', 'Modello aggiornato.');
    }

    public function destroy(Request $request, QuestionnaireTemplate $questionnaireTemplate): RedirectResponse
    {
        abort_if($questionnaireTemplate->user_id !== $request->user()->id, 403);

        $questionnaireTemplate->delete();

        return redirect()->route('questionnaire-templates.index')
            ->with('success', 'Modello eliminato.');
    }
}
