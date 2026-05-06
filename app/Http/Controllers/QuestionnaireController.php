<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Questionnaire;
use App\Models\QuestionnaireTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionnaireController extends Controller
{
    public function create(Request $request): Response
    {
        $request->validate(['patient_id' => 'required|exists:patients,id']);

        $userId   = $request->user()->id;
        $patient  = Patient::findOrFail($request->patient_id);

        $templates = QuestionnaireTemplate::where('user_id', $userId)
            ->orderBy('name')
            ->get();

        $reports = $patient->reports()
            ->where('user_id', $userId)
            ->orderByDesc('report_date')
            ->get(['id', 'title', 'report_date']);

        $selectedTemplate = null;
        if ($request->template_id) {
            $selectedTemplate = $templates->firstWhere('id', (int) $request->template_id);
        }
        if (!$selectedTemplate && $templates->isNotEmpty()) {
            $selectedTemplate = $templates->first();
        }

        return Inertia::render('Questionnaires/Create', [
            'templates'          => $templates,
            'patient'            => $patient,
            'reports'            => $reports,
            'selectedTemplateId' => $selectedTemplate?->id,
            'selectedReportId'   => $request->report_id ? (int) $request->report_id : null,
            'questionnaire'      => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id'                => 'required|exists:patients,id',
            'questionnaire_template_id' => 'required|exists:questionnaire_templates,id',
            'report_id'                 => 'nullable|exists:reports,id',
            'filled_at'                 => 'required|date',
            'answers'                   => 'required|array',
            'notes'                     => 'nullable|string',
        ]);

        $totalScore = collect($validated['answers'])
            ->sum(fn ($a) => is_numeric($a['value'] ?? null) ? (int) $a['value'] : 0);

        $questionnaire = Questionnaire::create([
            'user_id'                   => $request->user()->id,
            'patient_id'                => $validated['patient_id'],
            'questionnaire_template_id' => $validated['questionnaire_template_id'],
            'report_id'                 => $validated['report_id'] ?? null,
            'filled_at'                 => $validated['filled_at'],
            'answers'                   => $validated['answers'],
            'total_score'               => $totalScore,
            'notes'                     => $validated['notes'] ?? null,
        ]);

        return redirect()->route('questionnaires.show', $questionnaire)
            ->with('success', 'Questionario salvato.');
    }

    public function show(Request $request, Questionnaire $questionnaire): Response
    {
        abort_if($questionnaire->user_id !== $request->user()->id, 403);

        $questionnaire->load(['template', 'patient', 'report', 'user']);

        return Inertia::render('Questionnaires/Show', [
            'questionnaire' => $questionnaire,
        ]);
    }

    public function edit(Request $request, Questionnaire $questionnaire): Response
    {
        abort_if($questionnaire->user_id !== $request->user()->id, 403);

        $questionnaire->load(['template', 'report']);

        $userId    = $request->user()->id;
        $templates = QuestionnaireTemplate::where('user_id', $userId)->orderBy('name')->get();

        $reports = $questionnaire->patient->reports()
            ->where('user_id', $userId)
            ->orderByDesc('report_date')
            ->get(['id', 'title', 'report_date']);

        return Inertia::render('Questionnaires/Create', [
            'templates'          => $templates,
            'patient'            => $questionnaire->patient,
            'reports'            => $reports,
            'selectedTemplateId' => $questionnaire->questionnaire_template_id,
            'selectedReportId'   => $questionnaire->report_id,
            'questionnaire'      => $questionnaire,
        ]);
    }

    public function update(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        abort_if($questionnaire->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'questionnaire_template_id' => 'required|exists:questionnaire_templates,id',
            'report_id'                 => 'nullable|exists:reports,id',
            'filled_at'                 => 'required|date',
            'answers'                   => 'required|array',
            'notes'                     => 'nullable|string',
        ]);

        $totalScore = collect($validated['answers'])
            ->sum(fn ($a) => is_numeric($a['value'] ?? null) ? (int) $a['value'] : 0);

        $questionnaire->update([
            'questionnaire_template_id' => $validated['questionnaire_template_id'],
            'report_id'                 => $validated['report_id'] ?? null,
            'filled_at'                 => $validated['filled_at'],
            'answers'                   => $validated['answers'],
            'total_score'               => $totalScore,
            'notes'                     => $validated['notes'] ?? null,
        ]);

        return redirect()->route('questionnaires.show', $questionnaire)
            ->with('success', 'Questionario aggiornato.');
    }

    public function destroy(Request $request, Questionnaire $questionnaire): RedirectResponse
    {
        abort_if($questionnaire->user_id !== $request->user()->id, 403);

        $patientId = $questionnaire->patient_id;
        $questionnaire->delete();

        return redirect()->route('patients.show', $patientId)
            ->with('success', 'Questionario eliminato.');
    }
}
