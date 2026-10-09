<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ClinicalNote;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientRecord;
use App\Models\Questionnaire;
use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Applica la politica di conservazione (GDPR art. 5.1.e):
 * - cancella DEFINITIVAMENTE i record cestinati oltre il periodo di grazia;
 * - elimina i log di audit più vecchi della soglia;
 * - segnala i pazienti "conclusi" oltre soglia (nessuna cancellazione automatica).
 *
 * Su Aruba va richiamato da un cron del pannello. Usare --dry-run per simulare.
 */
class ApplyRetention extends Command
{
    protected $signature = 'data:apply-retention {--dry-run}';

    protected $description = 'Applica la politica di conservazione dei dati (retention GDPR)';

    /** Modelli con soft-delete da purgare definitivamente dopo il periodo di grazia. */
    private array $softDeleteModels = [
        Patient::class, PatientRecord::class, Report::class, Questionnaire::class,
        Appointment::class, Invoice::class, ClinicalNote::class, Document::class,
    ];

    public function handle(): int
    {
        $dry     = (bool) $this->option('dry-run');
        $grace   = now()->subDays(config('gdpr.retention.trashed_grace_days'));
        $auditTo = now()->subMonths(config('gdpr.retention.audit_log_months'));

        // 1) Purga definitiva dei record cestinati oltre il periodo di grazia.
        foreach ($this->softDeleteModels as $model) {
            $query = $model::onlyTrashed()->where('deleted_at', '<', $grace);
            $count = (clone $query)->count();

            if (! $dry) {
                if ($model === Document::class) {
                    $disk = config('filesystems.default');
                    foreach ($query->cursor() as $doc) {
                        if ($doc->file_path) {
                            Storage::disk($disk)->delete($doc->file_path);
                        }
                        $doc->forceDelete();
                    }
                } else {
                    $query->forceDelete();
                }
            }

            $this->line(sprintf('%-16s %d record cestinati purgati', class_basename($model), $count));
        }

        // 2) Pruning del registro operazioni.
        $auditCount = AuditLog::where('created_at', '<', $auditTo)->count();
        if (! $dry) {
            AuditLog::where('created_at', '<', $auditTo)->delete();
        }
        $this->line(sprintf('%-16s %d voci di audit rimosse (oltre %d mesi)', 'AuditLog', $auditCount, config('gdpr.retention.audit_log_months')));

        // 3) Segnalazione pazienti conclusi oltre soglia (solo report).
        $threshold = now()->subYears(config('gdpr.retention.concluded_patient_years'));
        $toReview = Patient::where('status', 'concluso')->where('updated_at', '<', $threshold)->count();
        $this->line(sprintf('%-16s %d pazienti conclusi da riesaminare (oltre %d anni)', 'Review', $toReview, config('gdpr.retention.concluded_patient_years')));

        $this->info(($dry ? '[dry-run] ' : '').'Retention applicata.');

        return self::SUCCESS;
    }
}
