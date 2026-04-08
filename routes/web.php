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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
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
    Route::post('intervisioni', [\App\Http\Controllers\IntervisioneController::class, 'store'])->name('intervisioni.store');
    Route::get('intervisioni/{intervisione}', [\App\Http\Controllers\IntervisioneController::class, 'show'])->name('intervisioni.show');
    Route::put('intervisioni/{intervisione}', [\App\Http\Controllers\IntervisioneController::class, 'update'])->name('intervisioni.update');

    // Fatturazione
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/sts', [InvoiceController::class, 'exportSts'])->name('invoices.sts');

    // Chat
    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{channel}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');

    // Repository documenti
    Route::resource('documents', DocumentController::class)->except(['edit', 'update']);

    // Workspace (social calendar)
    Route::get('workspace', function () {
        return Inertia::render('Workspace/Index');
    })->name('workspace.index');

    // Profili professionisti (admin)
    Route::get('team/professionals', [\App\Http\Controllers\ProfessionalController::class, 'index'])->name('professionals.index');
    Route::get('team/professionals/{user}', [\App\Http\Controllers\ProfessionalController::class, 'show'])->name('professionals.show');
    Route::put('team/professionals/{user}', [\App\Http\Controllers\ProfessionalController::class, 'update'])->name('professionals.update');
});
