-- NutriMente — nuovi campi anagrafici paziente
-- Esegui in phpMyAdmin (database Aruba) PRIMA di caricare i file.
-- Colonne TEXT perché il contenuto è cifrato a riposo (encryption expand).

ALTER TABLE `patients`
  ADD COLUMN `stato_civile` TEXT NULL AFTER `medico_base`,
  ADD COLUMN `educazione`   TEXT NULL AFTER `stato_civile`,
  ADD COLUMN `professione`  TEXT NULL AFTER `educazione`,
  ADD COLUMN `relatives`    TEXT NULL AFTER `professione`;

-- Registra la migration come eseguita (evita ri-run da artisan).
-- Adegua `batch` all'ultimo valore presente in tabella se necessario.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_20_120000_add_socio_demographic_fields_to_patients',
       COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`;
