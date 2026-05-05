<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GdprController extends Controller
{
    private const DOCUMENTS = [
        'consenso-informato' => 'Consenso Informato.pdf',
        'privacy-policy'     => 'Privacy Policy.pdf',
    ];

    public function index(): Response
    {
        $available = [];
        foreach (self::DOCUMENTS as $slug => $filename) {
            $available[$slug] = Storage::disk('local')->exists("gdpr/{$filename}");
        }

        return Inertia::render('Gdpr/Index', ['available' => $available]);
    }

    public function download(string $document): StreamedResponse
    {
        abort_unless(array_key_exists($document, self::DOCUMENTS), 404);

        $filename = self::DOCUMENTS[$document];
        $path = "gdpr/{$filename}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $filename);
    }
}
