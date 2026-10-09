<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:upcoming-tasks')->dailyAt('08:00');

// Politica di conservazione dati (GDPR) — esecuzione notturna.
Schedule::command('data:apply-retention')->dailyAt('03:30');

// Backup cifrato degli allegati — settimanale (domenica notte).
Schedule::command('backup:files')->weeklyOn(0, '02:00');
