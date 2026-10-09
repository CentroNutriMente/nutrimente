-- =====================================================================
-- Referto: "Prossimo appuntamento" datetime + link all'appuntamento creato.
-- MySQL (Aruba, phpMyAdmin, no SSH). FARE BACKUP prima. Eseguire PRIMA del codice.
-- Equivalente di 2026_07_08_120000_add_next_appointment_to_reports.
-- =====================================================================

ALTER TABLE `reports`
  ADD COLUMN `next_appointment_at` DATETIME NULL AFTER `next_appointment`,
  ADD COLUMN `next_appointment_id` BIGINT UNSIGNED NULL AFTER `next_appointment_at`;

ALTER TABLE `reports`
  ADD CONSTRAINT `reports_next_appointment_id_foreign`
  FOREIGN KEY (`next_appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_08_120000_add_next_appointment_to_reports', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
