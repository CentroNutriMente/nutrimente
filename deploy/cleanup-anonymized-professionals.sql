-- NutriMente — pulizia utenti già anonimizzati ("diritto all'oblio")
-- Gli utenti eliminati prima del fix conservavano profilo professionista e slot,
-- quindi continuavano a comparire negli elenchi professionisti e nelle prenotazioni.
-- Esegui in phpMyAdmin. Gli utenti anonimizzati hanno email deleted+ID@invalid.local.

-- (Facoltativo) Verifica prima quali utenti verranno ripuliti:
-- SELECT id, name, email FROM `users` WHERE `email` LIKE 'deleted+%@invalid.local';

DELETE FROM `availability_slots`
WHERE `user_id` IN (
    SELECT `id` FROM `users` WHERE `email` LIKE 'deleted+%@invalid.local'
);

DELETE FROM `professional_profiles`
WHERE `user_id` IN (
    SELECT `id` FROM `users` WHERE `email` LIKE 'deleted+%@invalid.local'
);
