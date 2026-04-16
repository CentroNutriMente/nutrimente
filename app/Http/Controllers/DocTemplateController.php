<?php

namespace App\Http\Controllers;

use App\Models\DocTemplate;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocTemplateController extends Controller
{
    // ── Template CRUD ──────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $template = DocTemplate::create([
            'created_by'  => $request->user()->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_system'   => false,
            'content'     => ['sections' => []],
        ]);

        return redirect()->route('doc-templates.edit', $template->id);
    }

    public function edit(Request $request, DocTemplate $docTemplate): Response
    {
        return Inertia::render('Documents/TemplateEdit', [
            'template' => [
                'id'          => $docTemplate->id,
                'name'        => $docTemplate->name,
                'description' => $docTemplate->description,
                'is_system'   => $docTemplate->is_system,
                'content'     => $docTemplate->content,
            ],
        ]);
    }

    public function update(Request $request, DocTemplate $docTemplate): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'content'     => 'required|array',
        ]);

        $docTemplate->update($validated);

        return response()->json(['ok' => true]);
    }

    public function destroy(DocTemplate $docTemplate): RedirectResponse
    {
        abort_if($docTemplate->is_system, 403, 'I template di sistema non possono essere eliminati.');
        $docTemplate->delete();
        return redirect()->route('documents.index')->with('success', 'Template eliminato.');
    }

    // ── Compile ────────────────────────────────────────────────────────────────

    public function compile(DocTemplate $docTemplate): Response
    {
        return Inertia::render('Documents/Compile', [
            'template' => [
                'id'      => $docTemplate->id,
                'name'    => $docTemplate->name,
                'content' => $docTemplate->content,
            ],
        ]);
    }

    public function generatePdf(Request $request, DocTemplate $docTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'sections' => 'required|array',
        ]);

        // Merge filled state with template definition for rendering
        $templateContent = $docTemplate->content;
        $stateBySection  = collect($validated['sections'])->keyBy('id');

        $renderedSections = [];
        foreach ($templateContent['sections'] as $section) {
            $sState = $stateBySection->get($section['id'], []);
            $included = $sState['included'] ?? !($section['optional'] ?? false);
            if (! $included) continue;

            $stateByItem = collect($sState['items'] ?? [])->keyBy('id');

            $renderedItems = [];
            foreach ($section['items'] as $item) {
                $iState   = $stateByItem->get($item['id'], []);
                $iIncluded = $iState['included'] ?? !($item['optional'] ?? false);
                if (! $iIncluded) continue;

                $renderedItem = array_merge($item, [
                    'value'      => $iState['value'] ?? '',
                    'checkboxes' => array_map(function ($cb) use ($iState) {
                        $cbState = collect($iState['checkboxes'] ?? [])->firstWhere('id', $cb['id']);
                        return array_merge($cb, ['checked' => $cbState['checked'] ?? ($cb['default_checked'] ?? false)]);
                    }, $item['checkboxes'] ?? []),
                ]);

                $renderedItems[] = $renderedItem;
            }

            $renderedSections[] = array_merge($section, ['items' => $renderedItems]);
        }

        $pdf = Pdf::loadView('pdf.compiled-doc', [
            'title'    => $validated['title'],
            'template' => $docTemplate,
            'sections' => $renderedSections,
            'date'     => now()->format('d/m/Y'),
        ])->setPaper('a4', 'portrait');

        $fileName = 'doc_' . now()->format('YmdHis') . '_' . \Illuminate\Support\Str::slug($validated['title']) . '.pdf';
        $path     = 'documents/' . $fileName;

        Storage::disk(config('filesystems.default'))->put($path, $pdf->output());

        Document::create([
            'uploaded_by' => $request->user()->id,
            'patient_id'  => null,
            'template_id' => $docTemplate->id,
            'title'       => $validated['title'],
            'description' => 'Generato da template: ' . $docTemplate->name,
            'file_path'   => $path,
            'file_name'   => $fileName,
            'mime_type'   => 'application/pdf',
            'file_size'   => strlen($pdf->output()),
            'category'    => 'modulo',
        ]);

        return redirect()->route('documents.index')->with('success', 'Documento generato e salvato nel repository.');
    }
}
