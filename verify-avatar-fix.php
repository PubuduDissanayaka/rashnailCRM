<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Profile Picture Upload Verification ===\n\n";

// Check directories
$publicPath = public_path('storage/avatars');
$storagePath = storage_path('app/public/avatars');

echo "1. Directory Structure:\n";
echo "   Public:  $publicPath\n";
echo "   Storage: $storagePath\n\n";

echo "2. Directory Status:\n";
echo "   Public exists:  " . (file_exists($publicPath) ? '✓ YES' : '✗ NO') . "\n";
echo "   Storage exists: " . (file_exists($storagePath) ? '✓ YES' : '✗ NO') . "\n";
echo "   Public writable:  " . (is_writable($publicPath) ? '✓ YES' : '✗ NO') . "\n";
echo "   Storage writable: " . (is_writable($storagePath) ? '✓ YES' : '✗ NO') . "\n\n";

echo "3. Files in public/storage/avatars:\n";
$publicFiles = glob($publicPath . '/*');
if (count($publicFiles) > 0) {
    foreach ($publicFiles as $file) {
        $size = filesize($file);
        echo "   - " . basename($file) . " (" . round($size/1024, 2) . " KB)\n";
    }
} else {
    echo "   (no files)\n";
}

echo "\n4. Files in storage/app/public/avatars:\n";
$storageFiles = glob($storagePath . '/*');
if (count($storageFiles) > 0) {
    foreach ($storageFiles as $file) {
        $size = filesize($file);
        echo "   - " . basename($file) . " (" . round($size/1024, 2) . " KB)\n";
    }
} else {
    echo "   (no files)\n";
}

echo "\n5. Current User Avatar:\n";
$user = App\Models\User::first();
if ($user) {
    echo "   Email: {$user->email}\n";
    echo "   Avatar: " . ($user->avatar ?? 'NULL') . "\n";
    if ($user->avatar) {
        $avatarPublicPath = $publicPath . '/' . $user->avatar;
        $avatarStoragePath = $storagePath . '/' . $user->avatar;
        echo "   File in public:  " . (file_exists($avatarPublicPath) ? '✓ EXISTS' : '✗ MISSING') . "\n";
        echo "   File in storage: " . (file_exists($avatarStoragePath) ? '✓ EXISTS' : '✗ MISSING') . "\n";
        echo "   URL: " . asset('storage/avatars/' . $user->avatar) . "\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "\nThe upload function now:\n";
echo "1. Saves files directly to: public/storage/avatars/\n";
echo "2. Creates backup in: storage/app/public/avatars/\n";
echo "3. Updates database with filename\n";
echo "4. Shows success message with filename\n";
echo "\nTry uploading now at: http://127.0.0.1:8000/profile/edit\n";
