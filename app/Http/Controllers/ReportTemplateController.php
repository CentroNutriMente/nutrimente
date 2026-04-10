<?php

namespace App\Http\Controllers;

use App\Models\ReportTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ReportTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $templates = ReportTemplate::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return Inertia::render('Reports/Templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Reports/Templates/Form', [
            'template' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'description'              => 'nullable|string|max:500',
            'header_title'             => 'required|string|max:255',
            'header_subtitle'          => 'nullable|string|max:255',
            'sections'                 => 'required|array|min:1',
            'sections.*.label'         => 'required|string|max:255',
            'sections.*.type'          => 'required|in:textarea,text,date',
            'sections.*.placeholder'   => 'nullable|string|max:500',
            'sections.*.required'      => 'boolean',
            'show_patient_fields'      => 'boolean',
            'show_professional_footer' => 'boolean',
            'footer_note'              => 'nullable|string|max:1000',
            'is_default'               => 'boolean',
        ]);

        // Aggiunge un id univoco a ogni sezione
        $sections = collect($validated['sections'])->map(fn ($s, $i) => array_merge($s, [
            'id' => $s['id'] ?? uniqid('s'),
        ]))->values()->all();

        $logoPath = null;
        if ($request->hasFile('header_logo')) {
            $logoPath = $request->file('header_logo')->store('report-logos', 'public');
        }

        if ($validated['is_default'] ?? false) {
            ReportTemplate::where('user_id', $request->user()->id)
                ->update(['is_default' => false]);
        }

        ReportTemplate::create([
            'user_id'                  => $request->user()->id,
            'name'                     => $validated['name'],
            'description'              => $validated['description'] ?? null,
            'header_title'             => $validated['header_title'],
            'header_subtitle'          => $validated['header_subtitle'] ?? null,
            'header_logo'              => $logoPath,
            'sections'                 => $sections,
            'show_patient_fields'      => $validated['show_patient_fields'] ?? true,
            'show_professional_footer' => $validated['show_professional_footer'] ?? true,
            'footer_note'              => $validated['footer_note'] ?? null,
            'is_default'               => $validated['is_default'] ?? false,
        ]);

        return redirect()->route('report-templates.index')
            ->with('success', 'Modello creato con successo.');
    }

    public function edit(Request $request, ReportTemplate $reportTemplate): Response
    {
        abort_if($reportTemplate->user_id !== $request->user()->id, 403);

        return Inertia::render('Reports/Templates/Form', [
            'template' => $reportTemplate,
        ]);
    }

    public function update(Request $request, ReportTemplate $reportTemplate): RedirectResponse
    {
        abort_if($reportTemplate->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'description'              => 'nullable|string|max:500',
            'header_title'             => 'required|string|max:255',
            'header_subtitle'          => 'nullable|string|max:255',
            'sections'                 => 'required|array|min:1',
            'sections.*.label'         => 'required|string|max:255',
            'sections.*.type'          => 'required|in:textarea,text,date',
            'sections.*.placeholder'   => 'nullable|string|max:500',
            'sections.*.required'      => 'boolean',
            'show_patient_fields'      => 'boolean',
            'show_professional_footer' => 'boolean',
            'footer_note'              => 'nullable|string|max:1000',
            'is_default'               => 'boolean',
        ]);

        $sections = collect($validated['sections'])->map(fn ($s) => array_merge($s, [
            'id' => $s['id'] ?? uniqid('s'),
        ]))->values()->all();

        $logoPath = $reportTemplate->header_logo;
        if ($request->hasFile('header_logo')) {
            if ($logoPath) Storage::disk('public')->delete($logoPath);
            $logoPath = $request->file('header_logo')->store('report-logos', 'public');
        }

        if ($validated['is_default'] ?? false) {
            ReportTemplate::where('user_id', $request->user()->id)
                ->where('id', '!=', $reportTemplate->id)
                ->update(['is_default' => false]);
        }

        $reportTemplate->update([
            'name'                     => $validated['name'],
            'description'              => $validated['description'] ?? null,
            'header_title'             => $validated['header_title'],
            'header_subtitle'          => $validated['header_subtitle'] ?? null,
            'header_logo'              => $logoPath,
            'sections'                 => $sections,
            'show_patient_fields'      => $validated['show_patient_fields'] ?? true,
            'show_professional_footer' => $validated['show_professional_footer'] ?? true,
            'footer_note'              => $validated['footer_note'] ?? null,
            'is_default'               => $validated['is_default'] ?? false,
        ]);

        return redirect()->route('report-templates.index')
            ->with('success', 'Modello aggiornato.');
    }

    public function destroy(Request $request, ReportTemplate $reportTemplate): RedirectResponse
    {
        abort_if($reportTemplate->user_id !== $request->user()->id, 403);

        if ($reportTemplate->header_logo) {
            Storage::disk('public')->delete($reportTemplate->header_logo);
        }

        $reportTemplate->delete();

        return redirect()->route('report-templates.index')
            ->with('success', 'Modello eliminato.');
    }
}
