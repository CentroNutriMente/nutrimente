-- =====================================================================
-- Ruolo "system_admin" + utente amministratore di sistema.
-- MySQL (Aruba, phpMyAdmin, no SSH). Idempotente. FARE BACKUP prima.
--
-- Utente: alfonso93.esposito@gmail.com
-- Password: Al19fonso93!!  (hash bcrypt precalcolato sotto)
-- L'amministratore di sistema gestisce gli utenti e i template, vede il
-- calendario in forma anonima, NON accede ai dati clinici.
-- =====================================================================

-- 1) Permessi (se già presenti, ignora).
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'users.invite', 'web', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='users.invite' AND `guard_name`='web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'users.manage', 'web', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='users.manage' AND `guard_name`='web');

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'settings.manage', 'web', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='settings.manage' AND `guard_name`='web');

-- 2) Ruolo system_admin.
INSERT INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'system_admin', 'web', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name`='system_admin' AND `guard_name`='web');

-- 3) Collega i 3 permessi al ruolo.
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id FROM `permissions` p
JOIN `roles` r ON r.name='system_admin' AND r.guard_name='web'
WHERE p.guard_name='web' AND p.name IN ('users.invite','users.manage','settings.manage')
  AND NOT EXISTS (
    SELECT 1 FROM `role_has_permissions` rhp WHERE rhp.permission_id=p.id AND rhp.role_id=r.id
  );

-- 4) Utente amministratore di sistema.
INSERT INTO `users` (`name`, `email`, `email_verified_at`, `password`, `created_at`, `updated_at`)
SELECT 'Alfonso Esposito', 'alfonso93.esposito@gmail.com', NOW(),
       '$2y$12$0WGEygwSrCqhhrzAR.LJJuUlN9L7oUOkEkEX5iDZrhmvYhpcZmklS', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email`='alfonso93.esposito@gmail.com');

-- 5) Assegna il ruolo all'utente (model_has_roles).
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`)
SELECT r.id, 'App\\Models\\User', u.id
FROM `roles` r
JOIN `users` u ON u.email='alfonso93.esposito@gmail.com'
WHERE r.name='system_admin' AND r.guard_name='web'
  AND NOT EXISTS (
    SELECT 1 FROM `model_has_roles` mhr
    WHERE mhr.role_id=r.id AND mhr.model_type='App\\Models\\User' AND mhr.model_id=u.id
  );
