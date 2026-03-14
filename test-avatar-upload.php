<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Storage;

echo "=== Avatar Upload System Test ===\n\n";

// 1. Check storage directory exists
$storageDir = storage_path('app/public/avatars');
echo "1. Storage directory: ";
if (file_exists($storageDir)) {
    echo "✓ EXISTS\n";
    echo "   Path: $storageDir\n";
} else {
    echo "✗ MISSING\n";
    mkdir($storageDir, 0755, true);
    echo "   Created: $storageDir\n";
}

// 2. Check public storage link exists
$publicLink = public_path('storage');
echo "\n2. Public storage link: ";
if (is_link($publicLink) || file_exists($publicLink)) {
    echo "✓ EXISTS\n";
    echo "   Path: $publicLink\n";
} else {
    echo "✗ MISSING\n";
    echo "   Run: php artisan storage:link\n";
}

// 3. Check user model
$user = User::first();
echo "\n3. User model: ";
if ($user) {
    echo "✓ FOUND\n";
    echo "   ID: {$user->id}\n";
    echo "   Email: {$user->email}\n";
    echo "   Avatar field: " . ($user->avatar ?? 'NULL') . "\n";
    echo "   Fillable fields: " . implode(', ', $user->getFillable()) . "\n";
} else {
    echo "✗ NO USER FOUND\n";
}

// 4. Check avatar files
echo "\n4. Avatar files in storage: ";
$files = glob($storageDir . '/*');
if (count($files) > 0) {
    echo count($files) . " file(s)\n";
    foreach ($files as $file) {
        echo "   - " . basename($file) . "\n";
    }
} else {
    echo "0 files\n";
}

// 5. Test permissions
echo "\n5. Directory permissions: ";
$perms = substr(sprintf('%o', fileperms($storageDir)), -4);
echo "$perms\n";
if (is_writable($storageDir)) {
    echo "   ✓ Writable\n";
} else {
    echo "   ✗ NOT writable\n";
}

echo "\n=== Test Complete ===\n";
echo "\nNext steps:\n";
echo "1. Go to: http://127.0.0.1:8000/profile/edit\n";
echo "2. Upload an image\n";
echo "3. Check for success/error messages\n";
echo "4. Run this script again to verify the upload\n";
