<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentScheduledMail;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\Report;
use App\Models\ReportTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Mail;
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

        $patients = Patient::visibleTo($request->user())->get(['id', 'first_name', 'last_name'])->sortBy(fn ($p) => mb_strtolower((string) $p->last_name))->values();

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

        $patients = Patient::visibleTo($request->user())->get(['id', 'first_name', 'last_name'])->sortBy(fn ($p) => mb_strtolower((string) $p->last_name))->values();

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
            'next_appointment_at' => 'nullable|date',
            'notes'              => 'nullable|string',
        ]);

        $report = Report::create([
            'user_id'             => $request->user()->id,
            'patient_id'          => $validated['patient_id'],
            'report_template_id'  => $validated['report_template_id'] ?? null,
            'title'               => $validated['title'],
            'report_date'         => $validated['report_date'],
            'sections_data'       => $validated['sections_data'],
            'next_appointment_at' => $validated['next_appointment_at'] ?? null,
            'notes'               => $validated['notes'] ?? null,
        ]);

        $this->syncNextAppointment($report);

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

        $patients = Patient::visibleTo($request->user())->get(['id', 'first_name', 'last_name'])->sortBy(fn ($p) => mb_strtolower((string) $p->last_name))->values();

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
            'next_appointment_at' => 'nullable|date',
            'notes'              => 'nullable|string',
        ]);

        $report->update($validated);

        $this->syncNextAppointment($report);

        return redirect()->route('reports.show', $report)
            ->with('success', 'Referto aggiornato.');
    }

    /**
     * Sincronizza l'appuntamento derivato dal campo "Prossimo appuntamento" del referto:
     * crea/aggiorna/elimina l'Appointment collegato, aggiorna la stringa mostrata nel PDF
     * e, solo quando l'appuntamento viene creato, invia le conferme (paziente + professionista)
     * e la notifica in-app.
     */
    private function syncNextAppointment(Report $report): void
    {
        $when = $report->next_appointment_at;

        // Campo svuotato: rimuovi l'eventuale appuntamento collegato.
        if (! $when) {
            if ($report->next_appointment_id) {
                Appointment::where('id', $report->next_appointment_id)->delete();
            }
            $report->forceFill(['next_appointment' => null, 'next_appointment_id' => null])->saveQuietly();

            return;
        }

        $report->loadMissing(['patient', 'user.professionalProfile']);
        $patient  = $report->patient;
        $duration = $report->user->professionalProfile?->session_duration_minutes ?? 50;
        $start    = Carbon::parse($when);
        $end      = $start->copy()->addMinutes($duration);
        $title    = 'Appuntamento – '.trim("{$patient->last_name} {$patient->first_name}");

        $attrs = [
            'user_id'    => $report->user_id,
            'patient_id' => $patient->id,
            'title'      => $title,
            'start_at'   => $start,
            'end_at'     => $end,
            'type'       => 'session',
            'status'     => 'confirmed',
            'color'      => '#10b981',
        ];

        $isNew = false;
        if ($report->next_appointment_id && ($appt = Appointment::find($report->next_appointment_id))) {
            $appt->update($attrs);
        } else {
            $appt  = Appointment::create($attrs);
            $isNew = true;
        }

        // Stringa leggibile per PDF/Show (i campi restano invariati) + link all'appuntamento.
        $report->forceFill([
            'next_appointment'    => $start->format('d/m/Y').' alle '.$start->format('H:i'),
            'next_appointment_id' => $appt->id,
        ])->saveQuietly();

        // Notifiche ed email solo alla creazione: risalvare il referto (o spostare
        // l'appuntamento già collegato) non deve rimandarle ogni volta.
        if (! $isNew) {
            return;
        }

        AuditLog::record('created', $appt);

        // Notifica in-app alla professionista.
        Notification::send(
            $report->user_id,
            'appointment_scheduled',
            'Appuntamento fissato',
            "Nuovo appuntamento con {$patient->first_name} {$patient->last_name} il ".$start->format('d/m/Y')." alle ".$start->format('H:i').'.',
            ['appointment_id' => $appt->id],
        );

        // Email di conferma: paziente (se ha email) + professionista.
        try {
            if ($patient->email) {
                Mail::to($patient->email)->send(new AppointmentScheduledMail($appt));
            }
            if ($report->user->email) {
                Mail::to($report->user->email)->send(new AppointmentScheduledMail($appt, forProfessional: true));
            }
        } catch (\Throwable $e) {
            \Log::error('AppointmentScheduledMail failed: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, Report $report): RedirectResponse
    {
        abort_if($report->user_id !== $request->user()->id, 403);
        \App\Models\AuditLog::record('deleted', $report);
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

        \App\Models\AuditLog::record('exported', $report);

        $filename = 'referto-' . $report->patient->last_name . '-' . $report->report_date->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }
}
