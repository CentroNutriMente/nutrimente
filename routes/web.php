<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PatientController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Pagina pubblica di booking
Route::get('/prenota', [BookingController::class, 'index'])->name('booking.index');
Route::get('/prenota/{profile}', [BookingController::class, 'show'])->name('booking.show');
Route::post('/prenota/{profile}', [BookingController::class, 'store'])->name('booking.store');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pazienti
    Route::resource('patients', PatientController::class);
    Route::get('patients/{patient}/records', [PatientController::class, 'records'])->name('patients.records');
    Route::post('patients/{patient}/records', [PatientController::class, 'storeRecord'])->name('patients.records.store');

    // Calendario & Appuntamenti
    Route::resource('appointments', AppointmentController::class);
    Route::get('calendar', [AppointmentController::class, 'calendar'])->name('calendar');

    // Intervisioni
    Route::get('intervisioni', [\App\Http\Controllers\IntervisioneController::class, 'index'])->name('intervisioni.index');
    Route::get('intervisioni/create', [\App\Http\Controllers\IntervisioneController::class, 'create'])->name('intervisioni.create');
    Route::post('intervisioni', [\App\Http\Controllers\IntervisioneController::class, 'store'])->name('intervisioni.store');
    Route::get('intervisioni/{intervisione}', [\App\Http\Controllers\IntervisioneController::class, 'show'])->name('intervisioni.show');
    Route::put('intervisioni/{intervisione}', [\App\Http\Controllers\IntervisioneController::class, 'update'])->name('intervisioni.update');
    Route::delete('intervisioni/{intervisione}', [\App\Http\Controllers\IntervisioneController::class, 'destroy'])->name('intervisioni.destroy');

    // Fatturazione
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/sts', [InvoiceController::class, 'exportSts'])->name('invoices.sts');

    // Chat
    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/load', [MessageController::class, 'messages'])->name('messages.load');
    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
    Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

    // Repository documenti
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Workspace (social calendar)
    Route::get('workspace', function () {
        return Inertia::render('Workspace/Index');
    })->name('workspace.index');

    // GDPR & Privacy
    Route::get('gdpr', function () {
        return Inertia::render('Gdpr/Index');
    })->name('gdpr.index');

    // Profili professionisti (admin)
    Route::get('team/professionals', [\App\Http\Controllers\ProfessionalController::class, 'index'])->name('professionals.index');
    Route::get('team/professionals/{user}', [\App\Http\Controllers\ProfessionalController::class, 'show'])->name('professionals.show');
    Route::put('team/professionals/{user}', [\App\Http\Controllers\ProfessionalController::class, 'update'])->name('professionals.update');
});
