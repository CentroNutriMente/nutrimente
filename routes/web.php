<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocTemplateController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportTemplateController;
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

    // Referti & Modelli
    Route::resource('report-templates', ReportTemplateController::class);
    Route::resource('reports', ReportController::class);
    Route::get('reports/{report}/pdf', [ReportController::class, 'downloadPdf'])->name('reports.pdf');

    // Fatturazione
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/sts', [InvoiceController::class, 'exportSts'])->name('invoices.sts');

    // Chat
    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/load', [MessageController::class, 'messages'])->name('messages.load');
    Route::get('messages/unread', [MessageController::class, 'unread'])->name('messages.unread');
    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('messages/read-channel', [MessageController::class, 'readChannel'])->name('messages.read-channel');
    Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

    // Repository documenti
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('documents/{document}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Template documenti
    Route::post('doc-templates', [DocTemplateController::class, 'store'])->name('doc-templates.store');
    Route::get('doc-templates/{docTemplate}/edit', [DocTemplateController::class, 'edit'])->name('doc-templates.edit');
    Route::put('doc-templates/{docTemplate}', [DocTemplateController::class, 'update'])->name('doc-templates.update');
    Route::delete('doc-templates/{docTemplate}', [DocTemplateController::class, 'destroy'])->name('doc-templates.destroy');
    Route::get('doc-templates/{docTemplate}/compile', [DocTemplateController::class, 'compile'])->name('doc-templates.compile');
    Route::post('doc-templates/{docTemplate}/generate', [DocTemplateController::class, 'generatePdf'])->name('doc-templates.generate');

    // Notifiche
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Workspace – task list
    Route::get('workspace', [TaskController::class, 'index'])->name('workspace.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // GDPR & Privacy
    Route::get('gdpr', function () {
        return Inertia::render('Gdpr/Index');
    })->name('gdpr.index');

    // Profili professionisti (admin)
    Route::get('team/professionals', [\App\Http\Controllers\ProfessionalController::class, 'index'])->name('professionals.index');
    Route::post('team/professionals', [\App\Http\Controllers\ProfessionalController::class, 'store'])->name('professionals.store');
    Route::get('team/professionals/{user}', [\App\Http\Controllers\ProfessionalController::class, 'show'])->name('professionals.show');
    Route::put('team/professionals/{user}', [\App\Http\Controllers\ProfessionalController::class, 'update'])->name('professionals.update');
});
