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
        $templates = QuestionnaireTemplate::orderBy('name')->get();

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
            'name'                          => 'required|string|max:255',
            'description'                   => 'nullable|string|max:1000',
            'questions'                     => 'required|array',
            'questions.*.text'              => 'required|string',
            'questions.*.section_id'        => 'nullable|string',
            'questions.*.answers'           => 'required|array',
            'questions.*.answers.*.label'   => 'required|string',
            'questions.*.answers.*.score'   => 'required|numeric',
            'scoring'                       => 'nullable|array',
            'scoring.sections'              => 'nullable|array',
            'scoring.sections.*.id'         => 'required_with:scoring.sections|string',
            'scoring.sections.*.name'       => 'required_with:scoring.sections|string',
            'scoring.sections.*.operation'  => 'required_with:scoring.sections|in:sum,average',
            'scoring.sections.*.multiplier' => 'nullable|numeric',
            'scoring.sections.*.divisor'    => 'nullable|numeric|min:0.01',
            'scoring.base'                  => 'nullable|in:sum,average',
            'scoring.divisor'               => 'nullable|numeric|min:0.01',
            'scoring.multiplier'            => 'nullable|numeric',
            'scoring.formula'               => 'nullable|string',
            'scoring.thresholds'            => 'nullable|array',
            'scoring.thresholds.*.min'      => 'required_with:scoring.thresholds|numeric',
            'scoring.thresholds.*.max'      => 'required_with:scoring.thresholds|numeric',
            'scoring.thresholds.*.label'    => 'required_with:scoring.thresholds|string',
            'scoring.thresholds.*.color'    => 'required_with:scoring.thresholds|string',
        ]);

        $questions = $this->buildQuestions($validated['questions']);

        QuestionnaireTemplate::create([
            'user_id'     => $request->user()->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'questions'   => $questions,
            'scoring'     => $validated['scoring'] ?? null,
        ]);

        return redirect()->route('questionnaire-templates.index')
            ->with('success', 'Modello creato.');
    }

    public function edit(Request $request, QuestionnaireTemplate $questionnaireTemplate): Response
    {
        return Inertia::render('Questionnaires/Templates/Form', [
            'template' => $questionnaireTemplate,
        ]);
    }

    public function update(Request $request, QuestionnaireTemplate $questionnaireTemplate): RedirectResponse
    {

        $validated = $request->validate([
            'name'                          => 'required|string|max:255',
            'description'                   => 'nullable|string|max:1000',
            'questions'                     => 'required|array',
            'questions.*.text'              => 'required|string',
            'questions.*.section_id'        => 'nullable|string',
            'questions.*.answers'           => 'required|array',
            'questions.*.answers.*.label'   => 'required|string',
            'questions.*.answers.*.score'   => 'required|numeric',
            'scoring'                       => 'nullable|array',
            'scoring.sections'              => 'nullable|array',
            'scoring.sections.*.id'         => 'required_with:scoring.sections|string',
            'scoring.sections.*.name'       => 'required_with:scoring.sections|string',
            'scoring.sections.*.operation'  => 'required_with:scoring.sections|in:sum,average',
            'scoring.sections.*.multiplier' => 'nullable|numeric',
            'scoring.sections.*.divisor'    => 'nullable|numeric|min:0.01',
            'scoring.base'                  => 'nullable|in:sum,average',
            'scoring.divisor'               => 'nullable|numeric|min:0.01',
            'scoring.multiplier'            => 'nullable|numeric',
            'scoring.formula'               => 'nullable|string',
            'scoring.thresholds'            => 'nullable|array',
            'scoring.thresholds.*.min'      => 'required_with:scoring.thresholds|numeric',
            'scoring.thresholds.*.max'      => 'required_with:scoring.thresholds|numeric',
            'scoring.thresholds.*.label'    => 'required_with:scoring.thresholds|string',
            'scoring.thresholds.*.color'    => 'required_with:scoring.thresholds|string',
        ]);

        $questions = $this->buildQuestions($validated['questions']);

        $questionnaireTemplate->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'questions'   => $questions,
            'scoring'     => $validated['scoring'] ?? null,
        ]);

        return redirect()->route('questionnaire-templates.index')
            ->with('success', 'Modello aggiornato.');
    }

    public function destroy(QuestionnaireTemplate $questionnaireTemplate): RedirectResponse
    {
        $questionnaireTemplate->delete();

        return redirect()->route('questionnaire-templates.index')
            ->with('success', 'Modello eliminato.');
    }

    private function buildQuestions(array $raw): array
    {
        return collect($raw)->values()->map(fn ($q, $i) => [
            'id'         => 'q' . ($i + 1),
            'section_id' => $q['section_id'] ?? null,
            'text'       => $q['text'],
            'answers'    => collect($q['answers'])->values()->map(fn ($a, $ai) => [
                'id'    => 'a' . ($ai + 1),
                'label' => $a['label'],
                'score' => (int) $a['score'],
            ])->all(),
        ])->all();
    }
}
