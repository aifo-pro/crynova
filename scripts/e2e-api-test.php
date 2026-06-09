<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ApiKey;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\PaymentInvoice;
use App\Models\User;
use App\Services\PaymentCheckerService;
use Illuminate\Support\Facades\Hash;

$user = User::firstOrCreate(
    ['email' => 'e2e@test.com'],
    ['name' => 'E2E', 'password' => Hash::make('x'), 'role' => 'user', 'is_active' => true, 'email_verified_at' => now()]
);

$merchant = Merchant::firstOrCreate(
    ['user_id' => $user->id, 'name' => 'E2E Shop'],
    [
        'slug' => 'e2e', 'status' => 'active', 'is_active' => true, 'merchant_type' => 'website',
        'domain' => 'e2e.com', 'business_type' => 'other',
        'project_description' => str_repeat('Integration test project description. ', 8),
        'fee_percent' => 1, 'test_mode' => true,
    ]
);
$merchant->update(['test_mode' => true, 'status' => 'active', 'is_active' => true]);

$btc = Currency::where('code', 'BTC')->firstOrFail();
$merchant->currencies()->sync([$btc->id => ['is_enabled' => true]]);

$gen = ApiKey::generate($merchant, 'e2e-' . time());
$key = $gen['raw_key'];

$base = getenv('APP_URL') ?: 'http://127.0.0.1:8099';
$resp = Illuminate\Support\Facades\Http::withToken($key)->post("{$base}/api/v1/invoices", [
    'currency' => 'BTC',
    'amount'   => '0.0001',
    'order_id' => 'e2e-1',
]);

echo "CREATE HTTP {$resp->status()}\n";
echo $resp->body() . "\n";

if (! $resp->successful()) {
    exit(1);
}

$inv = PaymentInvoice::where('uuid', $resp->json('invoice_id'))->firstOrFail();
app(PaymentCheckerService::class)->simulateTestPayment($inv);
$inv->refresh();

echo "Final status: {$inv->status}\n";
exit($inv->status === 'paid' ? 0 : 1);
