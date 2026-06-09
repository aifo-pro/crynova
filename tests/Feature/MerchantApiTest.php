<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Blockchain\TestBlockchainDriver;
use App\Services\PaymentCheckerService;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MerchantApiTest extends TestCase
{
    use RefreshDatabase;

    private string $rawApiKey;
    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $user = User::create([
            'name' => 'API Merchant',
            'email' => 'api@test.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->merchant = Merchant::create([
            'user_id' => $user->id,
            'name' => 'Test Shop',
            'slug' => 'test-shop',
            'status' => Merchant::STATUS_ACTIVE,
            'is_active' => true,
            'merchant_type' => 'website',
            'domain' => 'test.shop',
            'business_type' => 'other',
            'project_description' => str_repeat('Test project description for Crynova payment gateway integration. ', 3),
            'fee_percent' => 1.0,
        ]);

        $btc = Currency::where('code', 'BTC')->first();
        $this->merchant->currencies()->sync([$btc->id => ['is_enabled' => true]]);

        $master = HierarchicalKeyFactory::generateMasterKey();
        $accountXpub = $master->derivePath("84'/0'/0'")->toExtendedPublicKey();
        Setting::set('hd_xpub_btc', $accountXpub, encrypt: true);

        Wallet::create([
            'currency_id' => $btc->id,
            'address' => 'bc1qpooltest0000000000000000000001',
            'type' => 'hot',
            'is_used' => false,
            'hd_path' => "m/84'/0'/0'/0/0",
        ]);

        ['raw_key' => $this->rawApiKey] = ApiKey::generate($this->merchant, 'test');
    }

    public function test_currencies_endpoint_requires_auth(): void
    {
        $this->getJson('/api/v1/currencies')->assertUnauthorized();
    }

    public function test_create_and_show_invoice(): void
    {
        $response = $this->withToken($this->rawApiKey)->postJson('/api/v1/invoices', [
            'currency' => 'BTC',
            'amount' => '0.0001',
            'order_id' => 'ord-1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('currency', 'BTC');

        $uuid = $response->json('invoice_id');

        $this->withToken($this->rawApiKey)
            ->getJson("/api/v1/invoices/{$uuid}")
            ->assertOk()
            ->assertJsonPath('order_id', 'ord-1');
    }

    public function test_rejects_disabled_currency_for_merchant(): void
    {
        $eth = Currency::where('code', 'ETH')->first();

        $this->withToken($this->rawApiKey)->postJson('/api/v1/invoices', [
            'currency' => 'ETH',
            'amount' => '0.01',
        ])->assertUnprocessable();
    }

    public function test_test_mode_payment_simulation(): void
    {
        $this->merchant->update(['test_mode' => true]);

        $response = $this->withToken($this->rawApiKey)->postJson('/api/v1/invoices', [
            'currency' => 'BTC',
            'amount' => '0.0002',
            'order_id' => 'sandbox-1',
        ])->assertCreated();

        $invoice = \App\Models\PaymentInvoice::where('uuid', $response->json('invoice_id'))->first();

        app(PaymentCheckerService::class)->simulateTestPayment($invoice);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
    }
}
