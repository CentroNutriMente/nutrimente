-- Fatture caricate da PDF: il paziente diventa facoltativo
-- (una fattura caricata può essere intestata a un cliente non in anagrafica).
-- Da eseguire in phpMyAdmin sul DB Sql1945528_1.

ALTER TABLE invoices MODIFY patient_id BIGINT UNSIGNED NULL;

SET @b = (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations);
INSERT INTO migrations (migration, batch) VALUES ('2026_08_06_100000_make_invoice_patient_nullable', @b);
