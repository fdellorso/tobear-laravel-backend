<?php

// SICUREZZA: token obbligatorio via query string
// Esempio: https://tobear.x10.mx/migrate.php?token=tobear-migrate-2024
// ELIMINARE IMMEDIATAMENTE dopo l'uso

define('TOKEN', 'tobear-migrate-2024');

if (! isset($_GET['token']) || $_GET['token'] !== TOKEN) {
    http_response_code(403);
    exit('Forbidden');
}

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Esegui migrate --force
$exitCode = Artisan::call('migrate', ['--force' => true]);
$output = Artisan::output();

// Output leggibile
header('Content-Type: text/plain');
echo "=== php artisan migrate --force ===\n\n";
echo $output;
echo "\n=== Exit code: {$exitCode} ===\n";
echo "\n⚠️  ELIMINA QUESTO FILE IMMEDIATAMENTE!\n";
