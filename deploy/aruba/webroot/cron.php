<?php

/*
|--------------------------------------------------------------------------
| Entry point per il cron di Aruba (modalità "PHP")
|--------------------------------------------------------------------------
| Avvia lo scheduler di Laravel: i comandi e i loro orari sono definiti in
| routes/console.php (notifiche 08:00, retention 03:30, backup domenica 02:00).
| Impostare il cron di Aruba su "PHP" → comando "cron.php" → ogni 30 minuti.
|
| Sicurezza: eseguibile solo da riga di comando (il cron di Aruba). Un accesso
| via HTTP richiede il segreto ?key=... (allineato a CRON_SECRET nel .env).
*/

$isCli = PHP_SAPI === 'cli';

$app_base = __DIR__.'/nutrimente_app';
require $app_base.'/vendor/autoload.php';
/** @var Illuminate\Foundation\Application $app */
$app = require_once $app_base.'/bootstrap/app.php';

if (! $isCli) {
    $expected = env('CRON_SECRET');
    if (! $expected || ($_GET['key'] ?? null) !== $expected) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->call('schedule:run');

echo $isCli ? '' : "OK\n";
exit($status);
