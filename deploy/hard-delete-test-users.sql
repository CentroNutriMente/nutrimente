-- NutriMente — cancellazione DEFINITIVA degli utenti di prova già anonimizzati.
-- Target: gli utenti con email deleted+ID@invalid.local (i 4 eliminati dall'area admin).
--
-- ⚠️  OPERAZIONE IRREVERSIBILE. Fai un backup del database da phpMyAdmin (Esporta)
--     PRIMA di eseguire. Verifica l'elenco con la SELECT del punto 1.
--
-- Le FK CASCADE/SET NULL (professional_profiles, availability_slots, reports,
-- questionnaires, patient_records, patient_professional, messages, notifications,
-- clinical_notes, audit_logs, tasks, patients.created_by, ...) si aggiornano da sole
-- alla cancellazione dell'utente. Qui svuoto prima solo le tabelle con FK RESTRICT.

-- ── 1) VERIFICA: chi verrà eliminato (attesi 4) ─────────────────────────────
SELECT `id`, `name`, `email`
FROM `users`
WHERE `email` LIKE 'deleted+%@invalid.local';

-- ── 2) CANCELLAZIONE (esegui dopo aver controllato la SELECT sopra) ─────────
START TRANSACTION;

-- Id target congelati in tabelle temporanee (evita self-reference su users/teams)
DROP TEMPORARY TABLE IF EXISTS `tmp_del_users`;
DROP TEMPORARY TABLE IF EXISTS `tmp_del_teams`;

CREATE TEMPORARY TABLE `tmp_del_users` AS
    SELECT `id` FROM `users` WHERE `email` LIKE 'deleted+%@invalid.local';

CREATE TEMPORARY TABLE `tmp_del_teams` AS
    SELECT `id` FROM `teams` WHERE `user_id` IN (SELECT `id` FROM `tmp_del_users`);

-- Tabelle con FK RESTRICT verso users → vanno svuotate prima
DELETE FROM `appointments`      WHERE `user_id`     IN (SELECT `id` FROM `tmp_del_users`);
DELETE FROM `invoices`          WHERE `user_id`     IN (SELECT `id` FROM `tmp_del_users`);
DELETE FROM `patient_consents`  WHERE `user_id`     IN (SELECT `id` FROM `tmp_del_users`);
DELETE FROM `documents`         WHERE `uploaded_by` IN (SELECT `id` FROM `tmp_del_users`);
DELETE FROM `intervisioni`      WHERE `created_by`  IN (SELECT `id` FROM `tmp_del_users`);
DELETE FROM `social_posts`      WHERE `created_by`  IN (SELECT `id` FROM `tmp_del_users`);
DELETE FROM `sessions`          WHERE `user_id`     IN (SELECT `id` FROM `tmp_del_users`);

-- Team Jetstream: appartenenze (dell'utente e ai suoi team), riferimento current_team, poi i team
DELETE FROM `team_user` WHERE `user_id` IN (SELECT `id` FROM `tmp_del_users`);
DELETE FROM `team_user` WHERE `team_id` IN (SELECT `id` FROM `tmp_del_teams`);
UPDATE `users` SET `current_team_id` = NULL
    WHERE `current_team_id` IN (SELECT `id` FROM `tmp_del_teams`);
DELETE FROM `teams` WHERE `id` IN (SELECT `id` FROM `tmp_del_teams`);

-- Ruoli/permessi Spatie (nessuna FK, pulizia esplicita)
DELETE FROM `model_has_roles`
    WHERE `model_type` = 'App\\Models\\User' AND `model_id` IN (SELECT `id` FROM `tmp_del_users`);
DELETE FROM `model_has_permissions`
    WHERE `model_type` = 'App\\Models\\User' AND `model_id` IN (SELECT `id` FROM `tmp_del_users`);

-- ── 3) Cancellazione finale degli utenti (cascate automatiche sul resto) ────
DELETE FROM `users` WHERE `id` IN (SELECT `id` FROM `tmp_del_users`);

DROP TEMPORARY TABLE `tmp_del_users`;
DROP TEMPORARY TABLE `tmp_del_teams`;

COMMIT;

-- Se qualcosa va storto prima del COMMIT: esegui  ROLLBACK;
