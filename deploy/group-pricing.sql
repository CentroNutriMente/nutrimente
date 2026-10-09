-- NutriMente — quota e durata incontro dei gruppi
-- Esegui in phpMyAdmin (database Aruba) PRIMA di caricare i file.

ALTER TABLE `support_groups`
  ADD COLUMN `price_per_session`        DECIMAL(8,2)     NULL AFTER `capacity`,
  ADD COLUMN `session_duration_minutes` SMALLINT UNSIGNED NULL AFTER `price_per_session`;

-- Gruppi già esistenti: 35 € a incontro di due ore, a persona.
UPDATE `support_groups`
   SET `price_per_session` = 35.00,
       `session_duration_minutes` = 120
 WHERE `price_per_session` IS NULL;

-- Registra la migration come eseguita (evita ri-run da artisan).
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_30_100000_add_pricing_to_support_groups',
       COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`;
