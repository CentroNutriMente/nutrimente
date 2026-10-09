# Backup e ripristino (GDPR) — NutriMente

Il GDPR (art. 32) richiede di garantire **disponibilità, integrità e ripristino** dei dati.
Un backup completo di NutriMente ha **tre componenti**.

## Cosa è già protetto
- **Colonne sensibili del DB**: cifrate a riposo (AES-256 via `APP_KEY`). Un dump del DB
  contiene quindi i dati sensibili **già cifrati**.
- **Allegati** (`storage/app`): cifrati a riposo. Una copia dei file è già illeggibile senza chiave.
- Le chiavi `APP_KEY` e `BLIND_INDEX_KEY` vanno conservate **separate** dai backup: un backup
  senza chiavi è di fatto inutilizzabile da un attaccante (backup cifrato "by design").

## 1) Database
Esporta il DB da **phpMyAdmin** (pannello Aruba → Esporta → SQL, con "Aggiungi DROP TABLE").
Pianificabile: alcuni piani Aruba permettono export/backup schedulati. In alternativa export
manuale periodico. Il file `.sql` contiene i dati sensibili cifrati.

## 2) Allegati (file caricati)
Sul server (o in locale con `.env` di produzione) esegui:

```
php artisan backup:files
```

Crea `storage/backups/files_<timestamp>.zip.enc` — archivio **cifrato** di `storage/app`.
Scaricalo via FTP. Ripristino:

```
php artisan backup:restore-files storage/backups/files_XXXX.zip.enc --to=storage/app
```

(Su Aruba senza SSH: scarica via FTP la cartella `storage/app` — i file sono comunque cifrati.)

## 3) Chiavi
Conserva **offline e in luogo sicuro** (password manager) le righe del `.env` di produzione:
- `APP_KEY=...`  → senza questa i dati cifrati sono irrecuperabili
- `BLIND_INDEX_KEY=...` → necessaria per i lookup (email/CF)

## Regole operative
- **Almeno una copia OFFLINE** (non solo sul server).
- **Frequenza**: DB giornaliero/settimanale secondo il volume; allegati settimanale.
- **Test di ripristino periodico**: almeno ogni 6 mesi, ripristinare su ambiente di prova e
  verificare l'apertura dei dati (conferma che le chiavi sono corrette e i backup integri).
- **Conservazione**: non accumulare backup all'infinito; ruotare secondo la retention definita
  in `config/gdpr.php`.
