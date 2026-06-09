<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;

/*
 * Account-level integration hub pages. Modules & Brandbook render real content
 * scoped to a selected project; API is a docs+keys page; Exchange is a placeholder.
 */
class HubController extends Controller
{
    public function api(Request $request)
    {
        $merchants = $request->user()->merchants()->latest()->get();
        $merchant = $this->selected($request, $merchants);

        return view('account.integration.api', compact('merchants', 'merchant'));
    }

    public function modules(Request $request)
    {
        $merchants = $request->user()->merchants()->latest()->get();
        $merchant = $this->selected($request, $merchants);

        // Available CMS modules (download + install guide)
        $modules = [
            ['name' => 'WordPress / WooCommerce', 'slug' => 'woocommerce', 'icon' => 'globe', 'desc' => 'Плагін для приймання криптоплатежів у WooCommerce.'],
            ['name' => 'OpenCart',  'slug' => 'opencart',  'icon' => 'layout', 'desc' => 'Модуль оплати для OpenCart 2.x / 3.x.'],
            ['name' => 'Tilda',     'slug' => 'tilda',     'icon' => 'layout', 'desc' => 'Інтеграція через приймання вебхуків Tilda.'],
            ['name' => 'PrestaShop','slug' => 'prestashop','icon' => 'globe',  'desc' => 'Платіжний модуль для PrestaShop 1.7+.'],
            ['name' => 'Bitrix',    'slug' => 'bitrix',    'icon' => 'layers', 'desc' => 'Обробник платіжної системи для 1С-Bitrix.'],
            ['name' => 'Magento 2', 'slug' => 'magento',   'icon' => 'layers', 'desc' => 'Payment method для Magento 2.'],
        ];

        return view('account.integration.modules', compact('merchants', 'merchant', 'modules'));
    }

    public function widget(Request $request)
    {
        $merchants = $request->user()->merchants()->latest()->get();
        $merchant = $this->selected($request, $merchants);

        return view('account.integration.widget', compact('merchants', 'merchant'));
    }

    public function brandbook(Request $request)
    {
        $merchants = $request->user()->merchants()->latest()->get();
        $merchant = $this->selected($request, $merchants);

        return view('account.integration.brandbook', compact('merchants', 'merchant'));
    }

    public function exchange(Request $request)
    {
        return view('account.integration.exchange');
    }

    /** Resolve the selected project from ?project= or default to the first. */
    private function selected(Request $request, $merchants): ?Merchant
    {
        if ($id = $request->integer('project')) {
            return $merchants->firstWhere('id', $id) ?? $merchants->first();
        }

        return $merchants->first();
    }
}
