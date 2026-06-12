<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Balance;
use App\Models\BalanceMovement;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\PaymentInvoice;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\PaymentCheckerService;
use App\Services\WithdrawalService;
use App\Support\UrlSafety;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;
    private Currency $btc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->btc = Currency::where('code', 'BTC')->first();

        $user = User::create([
            'name' => 'Security Test',
            'email' => 'security@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->merchant = Merchant::create([
            'user_id' => $user->id,
            'name' => 'Secure Shop',
            'slug' => 'secure-shop',
            'status' => Merchant::STATUS_ACTIVE,
            'is_active' => true,
            'merchant_type' => 'website',
            'domain' => 'secure.shop',
            'business_type' => 'other',
            'project_description' => str_repeat('Security regression test merchant project description. ', 3),
            'fee_percent' => 1.0,
            'test_mode' => true,
        ]);

        $this->merchant->currencies()->sync([$this->btc->id => ['is_enabled' => true]]);

        $master = HierarchicalKeyFactory::generateMasterKey();
        $accountXpub = $master->derivePath("84'/0'/0'")->toExtendedPublicKey();
        Setting::set('hd_xpub_btc', $accountXpub, encrypt: true);

        Wallet::create([
            'currency_id' => $this->btc->id,
            'address' => 'bc1qpooltest0000000000000000000002',
            'type' => 'hot',
            'is_used' => false,
            'hd_path' => "m/84'/0'/0'/0/0",
        ]);
    }

    public function test_payment_credit_is_idempotent(): void
    {
        ['raw_key' => $rawKey] = ApiKey::generate($this->merchant, 'pay-test');

        $response = $this->withToken($rawKey)->postJson('/api/v1/invoices', [
            'currency' => 'BTC',
            'amount' => '0.001',
            'order_id' => 'sec-1',
        ])->assertCreated();

        $invoice = PaymentInvoice::where('uuid', $response->json('invoice_id'))->firstOrFail();
        $checker = app(PaymentCheckerService::class);

        $checker->simulateTestPayment($invoice);
        $checker->simulateTestPayment($invoice->fresh());

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);

        $settlements = BalanceMovement::where('idempotency_key', 'invoice:' . $invoice->id . ':settlement')->count();
        $this->assertSame(1, $settlements);

        $balance = $this->merchant->balanceFor($this->btc)->fresh();
        $this->assertSame(0, bccomp((string) $balance->available, (string) $invoice->net_amount, 18));
    }

    public function test_withdrawal_reserve_approve_and_reject(): void
    {
        Balance::create([
            'merchant_id' => $this->merchant->id,
            'currency_id' => $this->btc->id,
            'available' => '1.0',
            'locked' => '0',
        ]);

        $withdrawals = app(WithdrawalService::class);

        $w = $withdrawals->request($this->merchant, $this->btc, '0.4', 'bc1qwithdraw000000000000000001');

        $balance = $this->merchant->balanceFor($this->btc)->fresh();
        $this->assertTrue($w->funds_reserved);
        $this->assertSame(0, bccomp('0.6', (string) $balance->available, 18));
        $this->assertSame(0, bccomp('0.4', (string) $balance->locked, 18));

        $withdrawals->ensureReserved($w->fresh());
        $balance = $this->merchant->balanceFor($this->btc)->fresh();
        $this->assertSame(0, bccomp('0.6', (string) $balance->available, 18));
        $this->assertSame(0, bccomp('0.4', (string) $balance->locked, 18));

        $w->update(['status' => 'approved', 'approved_at' => now()]);
        $balance = $this->merchant->balanceFor($this->btc)->fresh();
        $this->assertSame(0, bccomp('0.6', (string) $balance->available, 18));
        $this->assertSame(0, bccomp('0.4', (string) $balance->locked, 18));

        $w2 = $withdrawals->request($this->merchant, $this->btc, '0.2', 'bc1qwithdraw000000000000000002');
        $withdrawals->releaseIfReserved($w2->fresh());
        $w2->update(['status' => 'cancelled', 'funds_reserved' => false]);

        $balance = $this->merchant->balanceFor($this->btc)->fresh();
        $this->assertSame(0, bccomp('0.6', (string) $balance->available, 18));
        $this->assertSame(0, bccomp('0.4', (string) $balance->locked, 18));
        $this->assertSame('cancelled', $w2->fresh()->status);
    }

    public function test_api_key_without_permissions_is_denied(): void
    {
        ['raw_key' => $rawKey] = ApiKey::generate($this->merchant, 'restricted', []);

        $this->withToken($rawKey)
            ->getJson('/api/v1/currencies')
            ->assertForbidden();
    }

    public function test_api_key_with_default_permissions_works(): void
    {
        ['raw_key' => $rawKey] = ApiKey::generate($this->merchant, 'default');

        $this->withToken($rawKey)
            ->getJson('/api/v1/currencies')
            ->assertOk();
    }

    public function test_url_safety_blocks_private_networks(): void
    {
        $this->assertFalse(UrlSafety::isPublicHttpUrl('http://127.0.0.1/webhook'));
        $this->assertFalse(UrlSafety::isPublicHttpUrl('http://localhost/hook'));
        $this->assertFalse(UrlSafety::isPublicHttpUrl('ftp://example.com/hook'));
        $this->assertTrue(UrlSafety::isPublicHttpUrl('https://example.com/webhook'));
    }

    public function test_webhook_save_rejects_private_url(): void
    {
        $user = $this->merchant->user;
        $user->update(['role' => 'user']);

        $this->actingAs($user)
            ->post(route('merchant.webhooks.save', $this->merchant), [
                'url' => 'http://127.0.0.1/webhook',
                'events' => ['invoice.paid'],
            ])
            ->assertSessionHasErrors('url');
    }
}
