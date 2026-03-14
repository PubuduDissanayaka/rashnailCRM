<?php

use App\Models\Coupon;
use App\Models\CouponBatch;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Coupon System Manual Test ===\n";

$couponService = new CouponService();

// Helper to log success/failure
function assertTrue($condition, $message) {
    if ($condition) {
        echo "✓ $message\n";
        return true;
    } else {
        echo "✗ $message\n";
        return false;
    }
}

// 1. Create a coupon
echo "\n1. Creating coupon...\n";
$coupon = Coupon::create([
    'code' => 'MANUALTEST' . time(),
    'name' => 'Manual Test Coupon',
    'type' => Coupon::TYPE_PERCENTAGE,
    'discount_value' => 10,
    'minimum_purchase_amount' => 0,
    'start_date' => now()->subDay(),
    'end_date' => now()->addMonth(),
    'total_usage_limit' => 5,
    'per_customer_limit' => 2,
    'stackable' => false,
    'active' => true,
    'location_restriction_type' => 'all',
    'customer_eligibility_type' => 'all',
    'product_restriction_type' => 'all',
]);
assertTrue($coupon->exists, 'Coupon created');

// 2. Test isActive
echo "\n2. Testing isActive...\n";
assertTrue($coupon->isActive(), 'Coupon is active');

// 3. Test discount calculation
echo "\n3. Testing discount calculation...\n";
$discount = $couponService->calculateDiscount($coupon, 200);
assertTrue($discount == 20, '10% discount on 200 equals 20');

// 4. Create a sale
echo "\n4. Creating sale...\n";
$user = User::first();
$sale = Sale::create([
    'sale_number' => 'TEST-' . time(),
    'subtotal' => 200,
    'total_amount' => 200,
    'sale_date' => now(),
    'user_id' => $user ? $user->id : 1,
]);
assertTrue($sale->exists, 'Sale created');

// 5. Validate coupon against sale
echo "\n5. Validating coupon...\n";
$validation = $couponService->validate($coupon, $sale);
assertTrue($validation['valid'], 'Coupon validation passed');
if (!empty($validation['errors'])) {
    echo "Errors: " . implode(', ', $validation['errors']) . "\n";
}

// 6. Apply coupon
echo "\n6. Applying coupon...\n";
try {
    $redemption = $couponService->applyCoupon($sale, $coupon->code);
    assertTrue($redemption->exists, 'Coupon applied successfully');
    $sale->refresh();
    echo "Discount applied: " . $sale->coupon_discount_amount . "\n";
    echo "New total: " . $sale->total_amount . "\n";
} catch (Exception $e) {
    echo "Error applying coupon: " . $e->getMessage() . "\n";
}

// 7. Test usage limit
echo "\n7. Testing usage limit...\n";
$remaining = $coupon->remainingUses();
echo "Remaining uses: " . $remaining . "\n";
assertTrue($remaining === 4, 'Remaining uses decreased');

// 8. Create a customer and test per customer limit
echo "\n8. Testing per customer limit...\n";
$customer = Customer::first();
if ($customer) {
    $usageCount = $coupon->customerUsageCount($customer);
    echo "Customer usage count: $usageCount\n";
    assertTrue($coupon->canBeUsedByCustomer($customer), 'Customer can still use coupon');
} else {
    echo "No customer found, skipping customer limit test.\n";
}

// 9. Bulk coupon generation
echo "\n9. Testing bulk coupon generation...\n";
$batch = CouponBatch::create([
    'name' => 'Manual Batch',
    'pattern' => 'BATCH-{RANDOM6}',
    'total_count' => 3,
    'settings' => [
        'type' => Coupon::TYPE_FIXED,
        'discount_value' => 5,
        'start_date' => now()->subDay(),
        'end_date' => now()->addMonth(),
        'active' => true,
    ],
]);
$couponService->generateBulkCoupons($batch);
$generatedCount = Coupon::where('batch_id', $batch->id)->count();
assertTrue($generatedCount === 3, 'Generated 3 coupons from batch');

// 10. Clean up test data
echo "\n10. Cleaning up test data...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=0');
Coupon::where('code', 'like', 'MANUALTEST%')->delete();
CouponBatch::where('name', 'Manual Batch')->delete();
Sale::where('sale_number', 'like', 'TEST-%')->delete();
DB::statement('SET FOREIGN_KEY_CHECKS=1');
echo "Cleanup completed.\n";

echo "\n=== Manual test finished ===\n";