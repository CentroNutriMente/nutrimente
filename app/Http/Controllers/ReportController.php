<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Report;
use App\Models\ReportTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $reports = Report::with(['patient', 'template'])
            ->where('user_id', $request->user()->id)
            ->when($request->patient_id, fn ($q) => $q->where('patient_id', $request->patient_id))
            ->orderByDesc('report_date')
            ->paginate(25)
            ->withQueryString();

        $patients = Patient::visibleTo($request->user())->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Reports/Index', [
            'reports'   => $reports,
            'patients'  => $patients,
            'filters'   => $request->only(['patient_id']),
        ]);
    }

    public function create(Request $request): Response
    {
        $templates = ReportTemplate::orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $patients = Patient::visibleTo($request->user())->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        $selectedTemplate = null;
        if ($request->template_id) {
            $selectedTemplate = ReportTemplate::find($request->template_id);
        } elseif ($templates->isNotEmpty()) {
            $selectedTemplate = $templates->firstWhere('is_default', true) ?? $templates->first();
        }

        return Inertia::render('Reports/Create', [
            'templates'        => $templates,
            'patients'         => $patients,
            'selectedTemplate' => $selectedTemplate,
            'selectedPatient'  => $request->patient_id ? (int) $request->patient_id : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id'         => 'required|exists:patients,id',
            'report_template_id' => 'nullable|exists:report_templates,id',
            'title'              => 'required|string|max:255',
            'report_date'        => 'required|date',
            'sections_data'      => 'required|array',
            'sections_data.*.label'   => 'required|string',
            'sections_data.*.content' => 'nullable|string',
            'next_appointment'   => 'nullable|string|max:255',
            'notes'              => 'nullable|string',
        ]);

        $report = Report::create([
            'user_id'            => $request->user()->id,
            'patient_id'         => $validated['patient_id'],
            'report_template_id' => $validated['report_template_id'] ?? null,
            'title'              => $validated['title'],
            'report_date'        => $validated['report_date'],
            'sections_data'      => $validated['sections_data'],
            'next_appointment'   => $validated['next_appointment'] ?? null,
            'notes'              => $validated['notes'] ?? null,
        ]);

        return redirect()->route('reports.show', $report)
            ->with('success', 'Referto salvato.');
    }

    public function show(Request $request, Report $report): Response
    {
        $this->authorizeRead($report, $request->user()->id);

        $report->load(['patient', 'template', 'user.professionalProfile']);

        return Inertia::render('Reports/Show', [
            'report'     => $report,
            'canEdit'    => $report->user_id === $request->user()->id,
        ]);
    }

    public function edit(Request $request, Report $report): Response
    {
        abort_if($report->user_id !== $request->user()->id, 403);

        $report->load(['template']);

        $templates = ReportTemplate::orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $patients = Patient::visibleTo($request->user())->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Reports/Create', [
            'report'           => $report,
            'templates'        => $templates,
            'patients'         => $patients,
            'selectedTemplate' => $report->template,
            'selectedPatient'  => $report->patient_id,
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        abort_if($report->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'report_date'        => 'required|date',
            'sections_data'      => 'required|array',
            'sections_data.*.label'   => 'required|string',
            'sections_data.*.content' => 'nullable|string',
            'next_appointment'   => 'nullable|string|max:255',
            'notes'              => 'nullable|string',
        ]);

        $report->update($validated);

        return redirect()->route('reports.show', $report)
            ->with('success', 'Referto aggiornato.');
    }

    public function destroy(Request $request, Report $report): RedirectResponse
    {
        abort_if($report->user_id !== $request->user()->id, 403);
        $report->delete();

        return redirect()->route('reports.index')
            ->with('success', 'Referto eliminato.');
    }

    private function authorizeRead(Report $report, int $userId): void
    {
        abort_if($report->user_id !== $userId, 403);
    }

    public function downloadPdf(Request $request, Report $report): HttpResponse
    {
        $this->authorizeRead($report, $request->user()->id);

        $report->load(['patient', 'template', 'user.professionalProfile']);

        $pdf = Pdf::loadView('pdf.report', ['report' => $report])
            ->setPaper('a4', 'portrait');

        $filename = 'referto-' . $report->patient->last_name . '-' . $report->report_date->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }
}
