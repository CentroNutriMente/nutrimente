<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ContactRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\DocTemplateController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\QuestionnaireTemplateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportTemplateController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Scheda Primo Contatto — il cliente sceglie il professionista e compila la scheda
Route::get('/prenota', [ContactRequestController::class, 'index'])->name('booking.index');
Route::get('/prenota/{slug}', [ContactRequestController::class, 'create'])->name('contact-requests.create');
Route::post('/prenota/{slug}', [ContactRequestController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('contact-requests.store');

// Vecchie rotte di booking deprecate → reindirizzano alla selezione professionista
Route::get('/prenota/{slug}/conferma/{token}', fn () => redirect()->route('booking.index'));
Route::get('/prenota/{slug}/rifiuta/{token}', fn () => redirect()->route('booking.index'));

// Iscrizione pubblica ai Gruppi di aiuto e sostegno (form + QR del volantino)
Route::get('/iscrizione-gruppo', [\App\Http\Controllers\GroupEnrollmentController::class, 'create'])->name('groups.public.create');
Route::post('/iscrizione-gruppo', [\App\Http\Controllers\GroupEnrollmentController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('groups.public.store');
Route::get('/iscrizione-gruppo/grazie', [\App\Http\Controllers\GroupEnrollmentController::class, 'done'])->name('groups.public.done');
Route::get('/g/{token}', [\App\Http\Controllers\GroupEnrollmentController::class, 'show'])->name('groups.public.show');

// Area personale paziente (solo utenti con ruolo patient)
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified', 'require.patient'])->group(function () {
    Route::get('/mia-area', [\App\Http\Controllers\PatientPortalController::class, 'dashboard'])->name('patient.dashboard');
    Route::post('/mia-area/appointments/{appointment}/cancel', [\App\Http\Controllers\PatientPortalController::class, 'cancelAppointment'])->name('patient.appointment.cancel');
    Route::post('/mia-area/richiedi-appuntamento', [\App\Http\Controllers\PatientPortalController::class, 'requestAppointment'])->name('patient.appointment.request');
});

// Home = Sara-focused landing page (Centro NutriMente team page kept at /prenota for later).
Route::get('/', [ContactRequestController::class, 'home'])->name('home');

// SEO: sitemap con le pagine pubbliche (referenziata da robots.txt e Search Console)
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'require.professional',
])->group(function () {

    // ─────────────────────────────────────────────────────────────────────────
    // Accessibili anche all'AMMINISTRATORE DI SISTEMA (nessun dato clinico):
    // gestione utenti, template referti/questionari, calendario anonimo + feed.
    // ─────────────────────────────────────────────────────────────────────────
    Route::get('utenti', [\App\Http\Controllers\UserManagementController::class, 'index'])->name('utenti.index');
    Route::post('utenti', [\App\Http\Controllers\UserManagementController::class, 'store'])->name('utenti.store');
    Route::delete('utenti/{user}', [\App\Http\Controllers\UserManagementController::class, 'erase'])->name('utenti.erase');

    Route::resource('report-templates', ReportTemplateController::class);
    Route::resource('questionnaire-templates', QuestionnaireTemplateController::class);

    Route::get('calendar', [AppointmentController::class, 'calendar'])->name('calendar');
    Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');

    // Notifiche proprie dell'utente (usate anche dalla campanella del layout).
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // ─────────────────────────────────────────────────────────────────────────
    // AREA CLINICA — vietata all'amministratore di sistema (require.clinical).
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware('require.clinical')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Gruppi di aiuto e sostegno
    Route::resource('groups', \App\Http\Controllers\GroupController::class)->except(['edit']);
    Route::post('groups/{group}/participants', [\App\Http\Controllers\GroupController::class, 'addParticipant'])->name('groups.participants.store');
    Route::put('groups/{group}/participants/{participant}', [\App\Http\Controllers\GroupController::class, 'updateParticipant'])->name('groups.participants.update');
    Route::delete('groups/{group}/participants/{participant}', [\App\Http\Controllers\GroupController::class, 'removeParticipant'])->name('groups.participants.destroy');
    Route::post('groups/{group}/enrollments/{enrollment}/approve', [\App\Http\Controllers\GroupController::class, 'approveEnrollment'])->name('groups.enrollments.approve');
    Route::post('groups/{group}/enrollments/{enrollment}/reject', [\App\Http\Controllers\GroupController::class, 'rejectEnrollment'])->name('groups.enrollments.reject');
    Route::get('groups/{group}/export', [\App\Http\Controllers\GroupController::class, 'exportParticipants'])->name('groups.export');
    Route::get('groups/{group}/flyer', [\App\Http\Controllers\GroupController::class, 'flyer'])->name('groups.flyer');
    // Incontri
    Route::post('groups/{group}/meetings', [\App\Http\Controllers\GroupController::class, 'storeMeeting'])->name('groups.meetings.store');
    Route::put('groups/{group}/meetings/{meeting}', [\App\Http\Controllers\GroupController::class, 'updateMeeting'])->name('groups.meetings.update');
    Route::delete('groups/{group}/meetings/{meeting}', [\App\Http\Controllers\GroupController::class, 'destroyMeeting'])->name('groups.meetings.destroy');
    // Presenze
    Route::post('groups/{group}/meetings/{meeting}/attendance', [\App\Http\Controllers\GroupController::class, 'markAttendance'])->name('groups.meetings.attendance');
    // Materiali
    Route::post('groups/{group}/materials', [\App\Http\Controllers\GroupController::class, 'storeMaterial'])->name('groups.materials.store');
    Route::get('groups/{group}/materials/{material}/download', [\App\Http\Controllers\GroupController::class, 'downloadMaterial'])->name('groups.materials.download');
    Route::delete('groups/{group}/materials/{material}', [\App\Http\Controllers\GroupController::class, 'destroyMaterial'])->name('groups.materials.destroy');

    // Pazienti
    // Archivio: cartelle eliminate, consultabili ma fuori dall'elenco generale.
    // Va prima della resource, altrimenti "archivio" verrebbe letto come {patient}.
    Route::get('patients/archivio', [PatientController::class, 'archived'])->name('patients.archived');
    Route::post('patients/{patient}/restore', [PatientController::class, 'restore'])->name('patients.restore')->withTrashed();
    Route::resource('patients', PatientController::class)->withTrashed(['show']);
    Route::put('patients/{patient}/status', [PatientController::class, 'updateStatus'])->name('patients.status');
    Route::put('patients/{patient}/notes', [PatientController::class, 'updateNote'])->name('patients.notes.update');
    Route::post('patients/{patient}/consents', [PatientController::class, 'storeConsent'])->name('patients.consents.store');
    Route::delete('patients/{patient}/consents/{consent}', [PatientController::class, 'destroyConsent'])->name('patients.consents.destroy');
    Route::get('patients/{patient}/export', [PatientController::class, 'export'])->name('patients.export');
    Route::get('patients/{patient}/records', [PatientController::class, 'records'])->name('patients.records');
    Route::post('patients/{patient}/records', [PatientController::class, 'storeRecord'])->name('patients.records.store');

    // Calendario & Appuntamenti (index/calendar sono già aperti sopra)
    Route::resource('appointments', AppointmentController::class)->except(['index']);

    // Richieste di appuntamento (dal portale paziente)
    Route::get('richieste-appuntamento', [\App\Http\Controllers\AppointmentRequestController::class, 'index'])->name('appointment-requests.index');
    Route::post('richieste-appuntamento/{booking}/conferma', [\App\Http\Controllers\AppointmentRequestController::class, 'confirm'])->name('appointment-requests.confirm');
    Route::post('richieste-appuntamento/{booking}/rifiuta', [\App\Http\Controllers\AppointmentRequestController::class, 'reject'])->name('appointment-requests.reject');

    // Intervisioni
    Route::get('intervisioni', [\App\Http\Controllers\IntervisioneController::class, 'index'])->name('intervisioni.index');
    Route::get('intervisioni/create', [\App\Http\Controllers\IntervisioneController::class, 'create'])->name('intervisioni.create');
    Route::post('intervisioni', [\App\Http\Controllers\IntervisioneController::class, 'store'])->name('intervisioni.store');
    Route::get('intervisioni/{intervisione}', [\App\Http\Controllers\IntervisioneController::class, 'show'])->name('intervisioni.show');
    Route::put('intervisioni/{intervisione}', [\App\Http\Controllers\IntervisioneController::class, 'update'])->name('intervisioni.update');
    Route::delete('intervisioni/{intervisione}', [\App\Http\Controllers\IntervisioneController::class, 'destroy'])->name('intervisioni.destroy');

    // Referti (i template referti sono già aperti sopra)
    Route::resource('reports', ReportController::class);
    Route::get('reports/{report}/pdf', [ReportController::class, 'downloadPdf'])->name('reports.pdf');

    // Questionari (i template questionari sono già aperti sopra)
    Route::resource('questionnaires', QuestionnaireController::class)->except(['index']);

    // Fatturazione
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices-export', [InvoiceController::class, 'exportExcel'])->name('invoices.export');
    // Fattura emessa fuori dall'app: registrazione con PDF allegato
    Route::get('invoices-carica', [InvoiceController::class, 'uploadForm'])->name('invoices.upload');
    Route::post('invoices-carica', [InvoiceController::class, 'uploadStore'])->name('invoices.upload.store');
    Route::post('invoices-carica/analizza', [InvoiceController::class, 'analyzePdf'])->name('invoices.upload.analyze');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/anteprima', [InvoiceController::class, 'viewPdf'])->name('invoices.pdf.view');
    Route::post('invoices/{invoice}/sts', [InvoiceController::class, 'exportSts'])->name('invoices.sts');

    // Chat
    Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/load', [MessageController::class, 'messages'])->name('messages.load');
    Route::get('messages/unread', [MessageController::class, 'unread'])->name('messages.unread');
    Route::get('messages/poll', [MessageController::class, 'poll'])->name('messages.poll');
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

    // Inbox richieste di primo contatto (ogni professionista vede le proprie)
    Route::get('richieste', [ContactRequestController::class, 'inbox'])->name('contact-requests.inbox');
    Route::post('richieste/{contactRequest}/accept', [ContactRequestController::class, 'accept'])->name('contact-requests.accept');
    Route::post('richieste/{contactRequest}/reject', [ContactRequestController::class, 'reject'])->name('contact-requests.reject');

    // Workspace – task list
    Route::get('workspace', [TaskController::class, 'index'])->name('workspace.index');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Social content calendar — disattivato per ora (sezione non in uso).
    // Per riattivarlo: decommentare queste route e la voce "Social" in AppLayout.vue.
    // Route::get('social', [SocialController::class, 'index'])->name('social.index');
    // Route::post('social', [SocialController::class, 'store'])->name('social.store');
    // Route::put('social/{socialPost}', [SocialController::class, 'update'])->name('social.update');
    // Route::delete('social/{socialPost}', [SocialController::class, 'destroy'])->name('social.destroy');

    // GDPR & Privacy
    Route::get('gdpr', [\App\Http\Controllers\GdprController::class, 'index'])->name('gdpr.index');
    Route::get('gdpr/download/{document}', [\App\Http\Controllers\GdprController::class, 'download'])->name('gdpr.download');

    // Registro operazioni (audit log) — admin/founder
    Route::get('registro-operazioni', [\App\Http\Controllers\AuditLogController::class, 'index'])->name('audit.index');

    // Profili professionisti (admin)
    Route::get('team/professionals', [\App\Http\Controllers\ProfessionalController::class, 'index'])->name('professionals.index');
    Route::post('team/professionals', [\App\Http\Controllers\ProfessionalController::class, 'store'])->name('professionals.store');
    Route::get('team/professionals/{user}', [\App\Http\Controllers\ProfessionalController::class, 'show'])->name('professionals.show');
    Route::put('team/professionals/{user}', [\App\Http\Controllers\ProfessionalController::class, 'update'])->name('professionals.update');

    // Availability slots
    Route::post('team/professionals/{user}/slots', [\App\Http\Controllers\ProfessionalController::class, 'storeSlot'])->name('professionals.slots.store');
    Route::delete('team/professionals/{user}/slots/{slot}', [\App\Http\Controllers\ProfessionalController::class, 'destroySlot'])->name('professionals.slots.destroy');

    }); // fine area clinica (require.clinical)
});
