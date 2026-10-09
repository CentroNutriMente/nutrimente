-- =====================================================================
-- GDPR hardening — schema MySQL (Aruba, phpMyAdmin, no SSH).
-- Equivalente delle migration:
--   2026_07_03_120000_make_audit_log_model_nullable
--   2026_07_03_130000_privacy_notes_and_soft_deletes
-- FARE UN BACKUP DEL DB prima. Eseguire PRIMA di caricare il codice nuovo.
-- =====================================================================

-- 1) audit_logs: model_type / model_id nullable (eventi di auth senza modello).
ALTER TABLE `audit_logs`
  MODIFY `model_type` VARCHAR(255) NULL,
  MODIFY `model_id` BIGINT UNSIGNED NULL;

-- 2) Nota clinica riservata + soft delete.
ALTER TABLE `clinical_notes`
  ADD COLUMN `is_private` TINYINT(1) NOT NULL DEFAULT 0 AFTER `body`,
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL;

-- 3) Soft delete sui dati clinici finora eliminati definitivamente.
ALTER TABLE `reports`        ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `questionnaires` ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `appointments`   ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `invoices`       ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL;
-- documents.deleted_at esiste già (era gestito manualmente): NON riaggiungere.

-- 4) Registrare le migration come eseguite (evita ri-esecuzioni via artisan).
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_03_120000_make_audit_log_model_nullable', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_03_130000_privacy_notes_and_soft_deletes', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
