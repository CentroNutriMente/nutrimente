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

        $userId  = $request->user()->id;
        $patient = Patient::findOrFail($request->patient_id);

        $templates = QuestionnaireTemplate::orderBy('name')->get();

        $reports = \App\Models\Report::where('user_id', $userId)
            ->where('patient_id', $patient->id)
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
            'answers.*.question_id'     => 'required|string',
            'answers.*.answer_id'       => 'required|string',
            'answers.*.score'           => 'required|numeric',
            'notes'                     => 'nullable|string',
        ]);

        $template   = QuestionnaireTemplate::findOrFail($validated['questionnaire_template_id']);
        $totalScore = $this->computeScore($validated['answers'], $template->scoring ?? [], $template->questions ?? []);

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

        return redirect()->route('patients.show', $questionnaire->patient_id)
            ->with('success', 'Questionario salvato.');
    }

    public function show(Request $request, Questionnaire $questionnaire): Response
    {
        $userId  = $request->user()->id;
        $patient = $questionnaire->patient;
        $hasAccess = $patient->user_id === $userId
            || $patient->professionals()->where('user_id', $userId)->exists();
        abort_if(! $hasAccess, 403);

        $questionnaire->load(['template', 'patient', 'report', 'user']);

        return Inertia::render('Questionnaires/Show', [
            'questionnaire' => $questionnaire,
            'canEdit'       => $questionnaire->user_id === $request->user()->id,
        ]);
    }

    public function edit(Request $request, Questionnaire $questionnaire): Response
    {
        abort_if($questionnaire->user_id !== $request->user()->id, 403);

        $questionnaire->load(['template', 'report']);

        $userId    = $request->user()->id;
        $templates = QuestionnaireTemplate::orderBy('name')->get();

        $reports = \App\Models\Report::where('user_id', $userId)
            ->where('patient_id', $questionnaire->patient_id)
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
            'answers.*.question_id'     => 'required|string',
            'answers.*.answer_id'       => 'required|string',
            'answers.*.score'           => 'required|numeric',
            'notes'                     => 'nullable|string',
        ]);

        $template   = QuestionnaireTemplate::findOrFail($validated['questionnaire_template_id']);
        $totalScore = $this->computeScore($validated['answers'], $template->scoring ?? [], $template->questions ?? []);

        $questionnaire->update([
            'questionnaire_template_id' => $validated['questionnaire_template_id'],
            'report_id'                 => $validated['report_id'] ?? null,
            'filled_at'                 => $validated['filled_at'],
            'answers'                   => $validated['answers'],
            'total_score'               => $totalScore,
            'notes'                     => $validated['notes'] ?? null,
        ]);

        return redirect()->route('patients.show', $questionnaire->patient_id)
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

    private function computeScore(array $answers, array $scoring, array $questions = []): int
    {
        $sections = $scoring['sections'] ?? [];

        if (!empty($sections) && !empty($scoring['formula'])) {
            $qSection = [];
            foreach ($questions as $q) {
                if (!empty($q['section_id'])) {
                    $qSection[$q['id']] = $q['section_id'];
                }
            }

            $buckets = array_fill_keys(array_column($sections, 'id'), []);
            foreach ($answers as $a) {
                $sid = $qSection[$a['question_id']] ?? null;
                if ($sid !== null && array_key_exists($sid, $buckets)) {
                    $buckets[$sid][] = (float) $a['score'];
                }
            }

            $vars = [];
            foreach ($sections as $s) {
                $scores = $buckets[$s['id']] ?? [];
                $n      = count($scores);
                $raw    = array_sum($scores);
                $val    = ($s['operation'] === 'average' && $n > 0) ? $raw / $n : $raw;
                if (!empty($s['multiplier'])) $val *= (float) $s['multiplier'];
                if (!empty($s['divisor']) && (float) $s['divisor'] !== 0.0) $val /= (float) $s['divisor'];
                $vars[$s['name']] = round($val, 6);
            }

            $expr = $scoring['formula'];
            uksort($vars, fn($a, $b) => strlen($b) - strlen($a));
            foreach ($vars as $name => $val) {
                $expr = str_replace($name, (string) $val, $expr);
            }

            if (!preg_match('/^[\d\s\+\-\*\/\(\)\.]+$/', $expr)) return 0;
            $result = eval("return (float)($expr);");
            return (int) round((float) $result);
        }

        $sum   = (float) collect($answers)->sum('score');
        $base  = $scoring['base'] ?? 'sum';
        $count = count($answers);
        $val   = ($base === 'average' && $count > 0) ? $sum / $count : $sum;
        if (!empty($scoring['multiplier'])) $val *= (float) $scoring['multiplier'];
        if (!empty($scoring['divisor']) && (float) $scoring['divisor'] !== 0.0) $val /= (float) $scoring['divisor'];
        return (int) round($val);
    }
}
