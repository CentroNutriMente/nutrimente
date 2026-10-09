-- =====================================================================
-- GDPR — Cifratura a riposo: schema MySQL (Aruba, phpMyAdmin, no SSH).
-- Equivalente di database/migrations/2026_06_28_120000_encrypt_sensitive_columns.php
--
-- ESEGUIRE PRIMA di caricare il codice nuovo. FARE UN BACKUP DEL DB prima.
-- Dopo: visitare una volta /__migrazione-cifratura-7f3a9c2e (login admin/founder)
-- per cifrare le righe esistenti, poi rimuovere quella rotta.
--
-- Nota: i blind index sono VARCHAR(64) (hash HMAC-SHA256 esadecimale fisso):
-- su MySQL TEXT non è indicizzabile UNIQUE/INDEX senza prefisso.
-- =====================================================================

-- 1) Rimuovere l'indice UNIQUE sul codice fiscale (verrà spostato sull'hash).
ALTER TABLE `patients` DROP INDEX `patients_codice_fiscale_unique`;

-- 2) Colonne varchar/date -> TEXT (ospitano blob cifrati).
ALTER TABLE `patients`
  MODIFY `first_name` TEXT NULL,
  MODIFY `last_name` TEXT NULL,
  MODIFY `codice_fiscale` TEXT NULL,
  MODIFY `gender` TEXT NULL,
  MODIFY `email` TEXT NULL,
  MODIFY `phone` TEXT NULL,
  MODIFY `city` TEXT NULL,
  MODIFY `cap` TEXT NULL,
  MODIFY `emergency_contact_name` TEXT NULL,
  MODIFY `emergency_contact_phone` TEXT NULL,
  MODIFY `medico_base` TEXT NULL,
  MODIFY `diagnosis` TEXT NULL,
  MODIFY `date_of_birth` TEXT NULL;

ALTER TABLE `reports`
  MODIFY `title` TEXT NULL,
  MODIFY `next_appointment` TEXT NULL,
  MODIFY `sections_data` TEXT NULL;

ALTER TABLE `questionnaires`
  MODIFY `answers` TEXT NULL;

ALTER TABLE `patient_records`
  MODIFY `title` TEXT NULL,
  MODIFY `data` TEXT NULL;

ALTER TABLE `documents`
  MODIFY `title` TEXT NULL,
  MODIFY `file_name` TEXT NULL;

ALTER TABLE `appointments`
  MODIFY `title` TEXT NULL;

ALTER TABLE `intervisioni`
  MODIFY `title` TEXT NULL;

ALTER TABLE `contact_requests`
  MODIFY `name` TEXT NULL,
  MODIFY `surname` TEXT NULL,
  MODIFY `phone` TEXT NULL,
  MODIFY `email` TEXT NULL,
  MODIFY `how_found` TEXT NULL,
  MODIFY `contact_method` TEXT NULL,
  MODIFY `availability` TEXT NULL;

ALTER TABLE `booking_requests`
  MODIFY `patient_name` TEXT NULL,
  MODIFY `patient_surname` TEXT NULL,
  MODIFY `patient_email` TEXT NULL,
  MODIFY `patient_phone` TEXT NULL;

ALTER TABLE `group_participants`
  MODIFY `name` TEXT NULL,
  MODIFY `email` TEXT NULL,
  MODIFY `phone` TEXT NULL;

ALTER TABLE `group_enrollment_requests`
  MODIFY `name` TEXT NULL,
  MODIFY `email` TEXT NULL,
  MODIFY `phone` TEXT NULL,
  MODIFY `codice_fiscale` TEXT NULL;

ALTER TABLE `invoices`
  MODIFY `client_name` TEXT NULL,
  MODIFY `client_codice_fiscale` TEXT NULL,
  MODIFY `client_address` TEXT NULL,
  MODIFY `client_phone` TEXT NULL,
  MODIFY `client_email` TEXT NULL;

ALTER TABLE `messages`
  MODIFY `attachment_name` TEXT NULL;

-- 3) Blind index su patients + spostamento del vincolo di unicità sul CF.
ALTER TABLE `patients`
  ADD COLUMN `email_hash` VARCHAR(64) NULL AFTER `email`,
  ADD COLUMN `codice_fiscale_hash` VARCHAR(64) NULL AFTER `codice_fiscale`;

ALTER TABLE `patients`
  ADD INDEX `patients_email_hash_index` (`email_hash`),
  ADD UNIQUE `patients_codice_fiscale_hash_unique` (`codice_fiscale_hash`);

-- 4) Registrare la migration come eseguita (evita ri-esecuzioni se un domani gira artisan).
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_28_120000_encrypt_sensitive_columns', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
