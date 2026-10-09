-- Registro presenze gruppi — da eseguire in phpMyAdmin (DB Sql1945528_1)

CREATE TABLE `group_attendances` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_meeting_id` bigint UNSIGNED NOT NULL,
  `group_participant_id` bigint UNSIGNED NOT NULL,
  `present` tinyint(1) NOT NULL DEFAULT 0,
  `marked_by` bigint UNSIGNED NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_attendance_unique` (`group_meeting_id`, `group_participant_id`),
  KEY `group_attendances_group_participant_id_foreign` (`group_participant_id`),
  KEY `group_attendances_marked_by_foreign` (`marked_by`),
  CONSTRAINT `group_attendances_group_meeting_id_foreign` FOREIGN KEY (`group_meeting_id`) REFERENCES `group_meetings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_attendances_group_participant_id_foreign` FOREIGN KEY (`group_participant_id`) REFERENCES `group_participants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_attendances_marked_by_foreign` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @b = (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations);
INSERT INTO migrations (migration, batch) VALUES ('2026_08_04_100000_create_group_attendances_table', @b);
