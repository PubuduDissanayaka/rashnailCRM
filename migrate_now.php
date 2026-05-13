<?php
// Minimal migration runner for PHP 8.0
// Bypasses platform checks and dev autoloading

define('LARAVEL_START', microtime(true));

// Register a simple autoloader that skips dev classes
require __DIR__.'/vendor/composer/autoload_real.php';

// Override the autoloader to skip dev files causing PHP 8.0 errors
spl_autoload_register(function ($class) {
    $skip = ['SebastianBergmann', 'PHPUnit', 'PhpParser', 'DeepCopy'];
    foreach ($skip as $prefix) {
        if (str_starts_with($class, $prefix)) {
            return; // skip
        }
    }
    // Let composer handle the rest
    $loader = ComposerAutoloaderInit9188fa4feeebedd602f4512a1b91b95a::getLoader();
    $loader->loadClass($class);
}, true, true);

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "Running migrations...\n";
$status = $kernel->call('migrate', ['--force' => true]);
echo "Migration done (exit: $status)\n";

$kernel->call('config:clear');
$kernel->call('cache:clear');
echo "Caches cleared.\n";
