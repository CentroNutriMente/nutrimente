<?php

/*
|------------------------------------------------------------------------------
| Build the Aruba bootstrap seed (Sara + template library) as MySQL SQL
|------------------------------------------------------------------------------
| Produces deploy/aruba/sara-seed.sql, which on a FRESH (migrated, empty) Aruba
| MySQL database creates:
|   - Sara's professional user account (email overridden, see --email)
|   - her `psicologo` role
|   - her professional_profile (slug, fiscal data, booking settings, ...)
|   - the template library (questionnaire_templates, report_templates,
|     doc_templates), all owned by Sara
|
| NOTHING else is carried over (no patients, reports, filled questionnaires,
| invoices, appointments, documents).
|
| WHERE TO RUN: locally, while your .env still points at the current
| (Supabase/Postgres) database. Reads through Laravel models, engine-agnostic.
|
| USAGE:
|   php deploy/aruba/export-seed.php                       # email = psicoalessandri@gmail.com
|   php deploy/aruba/export-seed.php --email=other@x.it    # override
|
| Then import deploy/aruba/sara-seed.sql via Aruba phpMyAdmin AFTER you have run
| migrations (so the schema + the seeded roles exist). The file uses a @sara SQL
| variable, so you never need to know Sara's new id in advance.
|
| Login: Sara signs in with the new email and her EXISTING password (her hash is
| carried over). She can use "password reset" later if she prefers a new one.
*/

require __DIR__.'/../../vendor/autoload.php';

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// --- args --------------------------------------------------------------------
$newEmail = 'psicoalessandri@gmail.com';
foreach ($argv as $arg) {
    if (preg_match('/^--email=(.+)$/', $arg, $m)) {
        $newEmail = trim($m[1]);
    }
}

// --- locate Sara (the founder professional) ----------------------------------
$profile = \App\Models\ProfessionalProfile::where('is_founder', true)->first()
    ?? \App\Models\ProfessionalProfile::whereHas('user', fn ($q) => $q->where('email', 'sara.alessandri@icloud.com'))->first();

if (! $profile) {
    fwrite(STDERR, "ERROR: could not find Sara's founder professional_profile.\n");
    exit(1);
}
$sara = \App\Models\User::find($profile->user_id);

/** Serialize a PHP value (model-cast) into a MySQL literal. */
function sqlVal($v): string
{
    if (is_null($v)) {
        return 'NULL';
    }
    if (is_bool($v)) {
        return $v ? '1' : '0';
    }
    if (is_int($v) || is_float($v)) {
        return (string) $v;
    }
    // Model casts serialize dates as ISO-8601, which MySQL DATETIME rejects.
    if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $v)) {
        $v = \Carbon\Carbon::parse($v)->format('Y-m-d H:i:s');
    } elseif (is_array($v)) {
        $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $v)."'";
}

/** Build an INSERT. $rawCols maps column => raw SQL (e.g. '@sara') left unquoted. */
function emitInsert(string $table, array $attrs, array $rawCols = []): string
{
    $cols = array_keys($attrs);
    $vals = [];
    foreach ($attrs as $col => $val) {
        $vals[] = array_key_exists($col, $rawCols) ? $rawCols[$col] : sqlVal($val);
    }

    return "INSERT INTO `{$table}` (`".implode('`, `', $cols)."`) VALUES (".implode(', ', $vals).");";
}

$out = [];
$out[] = '-- NutriMente -> Aruba bootstrap seed (Sara + template library)';
$out[] = '-- Generated '.date('Y-m-d H:i:s').' | login email = '.$newEmail;
$out[] = '-- Import AFTER running migrations on an empty Aruba MySQL database.';
$out[] = 'SET NAMES utf8mb4;';
$out[] = 'START TRANSACTION;';
$out[] = '';

// --- 1) Sara's user account --------------------------------------------------
$raw = $sara->getAttributes();   // includes the hidden password hash
$userAttrs = [
    'name'               => $sara->name,
    'email'              => $newEmail,
    'email_verified_at'  => optional($sara->email_verified_at)->format('Y-m-d H:i:s'),
    'password'           => $raw['password'] ?? null,
    'current_team_id'    => null,  // app does not rely on a team; Sara's is NULL today
    'profile_photo_path' => $sara->profile_photo_path,
    'created_at'         => optional($sara->created_at)->format('Y-m-d H:i:s'),
    'updated_at'         => optional($sara->updated_at)->format('Y-m-d H:i:s'),
];
$out[] = '-- Sara professional account';
$out[] = emitInsert('users', $userAttrs);
$out[] = 'SET @sara := LAST_INSERT_ID();';
$out[] = '';

// --- 2) psicologo role (teams=false => 3-column pivot; resolve id by name) ----
$out[] = '-- assign the psicologo role';
$out[] = "INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`)\n"
    ."  SELECT `id`, 'App\\\\Models\\\\User', @sara FROM `roles` WHERE `name` = 'psicologo' LIMIT 1;";
$out[] = '';

// --- 3) Sara's professional profile ------------------------------------------
$profAttrs = $profile->attributesToArray();
unset($profAttrs['id']);
$out[] = '-- Sara professional profile';
$out[] = emitInsert('professional_profiles', $profAttrs, ['user_id' => '@sara']);
$out[] = '';

// --- 4) template library (owned by Sara) -------------------------------------
$templates = [
    'questionnaire_templates' => [\App\Models\QuestionnaireTemplate::class, 'user_id'],
    'report_templates'        => [\App\Models\ReportTemplate::class,        'user_id'],
    'doc_templates'           => [\App\Models\DocTemplate::class,           'created_by'],
];

$summary = ['users' => 1, 'professional_profiles' => 1];
foreach ($templates as $table => [$modelClass, $ownerCol]) {
    /** @var \Illuminate\Database\Eloquent\Model $modelClass */
    $rows = $modelClass::orderBy('id')->get();
    $summary[$table] = $rows->count();

    $out[] = "-- {$table}";
    foreach ($rows as $row) {
        $attrs = $row->attributesToArray();
        unset($attrs['id']);
        $out[] = emitInsert($table, $attrs, [$ownerCol => '@sara']);
    }
    $out[] = '';
}

$out[] = 'COMMIT;';
$out[] = '';

$file = __DIR__.'/sara-seed.sql';
file_put_contents($file, implode("\n", $out));

fwrite(STDERR, "Wrote {$file}\n");
fwrite(STDERR, "  login email: {$newEmail}  (name: {$sara->name})\n");
foreach ($summary as $table => $count) {
    fwrite(STDERR, sprintf("  %-26s %d row(s)\n", $table, $count));
}
