<?php

namespace App\Http\Controllers;

use App\Models\DocTemplate;
use App\Models\Document;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Document::with('uploader', 'patient')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->boolean('shared_only')) {
            $query->whereNull('patient_id');
        }

        $documents = $query->paginate(30)->through(fn ($d) => [
            'id'                     => $d->id,
            'title'                  => $d->title,
            'description'            => $d->description,
            'file_name'              => $d->file_name,
            'mime_type'              => $d->mime_type,
            'file_size'              => $d->file_size,
            'category'               => $d->category,
            'template_id'            => $d->template_id,
            'is_shared_with_patient' => $d->is_shared_with_patient,
            'patient_name'           => $d->patient ? $d->patient->full_name : null,
            'uploaded_by_name'       => $d->uploader->name,
            'created_at'             => $d->created_at,
        ]);

        $templates = DocTemplate::orderByDesc('is_system')->orderBy('name')->get()->map(fn ($t) => [
            'id'          => $t->id,
            'name'        => $t->name,
            'description' => $t->description,
            'is_system'   => $t->is_system,
        ]);

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters'   => $request->only(['search', 'category', 'shared_only']),
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:20480', // 20 MB
            'category' => 'nullable|string|max:100',
            'patient_id' => 'nullable|exists:patients,id',
            'is_shared_with_patient' => 'boolean',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', config('filesystems.default'));

        Document::create([
            'uploaded_by' => $request->user()->id,
            'patient_id' => $validated['patient_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'category' => $validated['category'] ?? null,
            'is_shared_with_patient' => $validated['is_shared_with_patient'] ?? false,
        ]);

        return back()->with('success', 'Documento caricato.');
    }

    public function download(Document $document)
    {
        $disk = config('filesystems.default');
        abort_if(! Storage::disk($disk)->exists($document->file_path), 404);
        return Storage::disk($disk)->download($document->file_path, $document->file_name);
    }

    /** Serve the file inline (opens in browser tab rather than downloading). */
    public function view(Document $document)
    {
        $disk    = config('filesystems.default');
        $storage = Storage::disk($disk);

        abort_if(! $storage->exists($document->file_path), 404, 'File non disponibile.');

        // For S3-compatible disks: redirect to a short-lived signed URL so the
        // browser fetches the file directly (no PHP memory overhead, no CORS).
        if ($disk !== 'local') {
            try {
                return redirect($storage->temporaryUrl($document->file_path, now()->addMinutes(30)));
            } catch (\Exception) {
                // Driver doesn't support temporaryUrl — fall through to streaming.
            }
        }

        return response($storage->get($document->file_path), 200, [
            'Content-Type'        => $document->mime_type ?? 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($document->file_name) . '"',
        ]);
    }

    public function destroy(Document $document): RedirectResponse
    {
        Storage::disk(config('filesystems.default'))->delete($document->file_path);
        $document->update(['deleted_at' => now()]);
        return back()->with('success', 'Documento eliminato.');
    }
}
