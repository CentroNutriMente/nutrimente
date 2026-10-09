-- =====================================================================
-- "Data referto" con orario: reports.report_date da DATE a DATETIME.
-- MySQL (Aruba, phpMyAdmin, no SSH). FARE BACKUP prima. Eseguire PRIMA del codice.
-- Equivalente di 2026_07_08_130000_report_date_to_datetime.
-- =====================================================================

ALTER TABLE `reports` MODIFY `report_date` DATETIME NOT NULL;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_08_130000_report_date_to_datetime', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
