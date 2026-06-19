<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Builds the downloadable ZIPs for the official CMS integration modules and
 * upserts their catalog rows. Idempotent — safe to run from any migration.
 */
class ModuleCatalog
{
    /** 'root' = top folder inside the ZIP ('' keeps contents at the archive root). */
    public static function modules(): array
    {
        return [
            ['slug' => 'crynova-woocommerce', 'name' => 'WooCommerce', 'root' => 'crynova-woocommerce',
             'description' => 'Приймайте криптоплатежі у WooCommerce — клієнт обирає валюту на хостованому checkout Crynova.',
             'long' => "Офіційний плагін Crynova для WooCommerce (WordPress).\n\n• BTC, ETH, USDT (TRC-20/ERC-20/BEP-20), TRX, LTC, DOGE.\n• Створення рахунку через API та редірект на сторінку оплати.\n• Підписані вебхуки (HMAC-SHA256) — замовлення позначається оплаченим автоматично.\n\nВстановлення: Plugins → Add New → Upload Plugin → активуйте → WooCommerce → Settings → Payments → Crynova (API Key, Webhook Secret) → додайте Webhook URL у вебхуки проєкту.\n\nСумісність: WooCommerce 5.0+, PHP 7.4+, API v1.",
             'desc_en' => 'Accept crypto payments in WooCommerce — the customer picks the currency on the hosted Crynova checkout.',
             'desc_pl' => 'Przyjmuj płatności krypto w WooCommerce — klient wybiera walutę na hostowanym checkoucie Crynova.',
             'long_en' => "Official Crynova plugin for WooCommerce (WordPress).\n\n• BTC, ETH, USDT (TRC-20/ERC-20/BEP-20), TRX, LTC, DOGE.\n• Creates an invoice via the API and redirects to the payment page.\n• Signed webhooks (HMAC-SHA256) mark the order paid automatically.\n\nInstall: Plugins → Add New → Upload Plugin → activate → WooCommerce → Settings → Payments → Crynova (API Key, Webhook Secret) → add the Webhook URL to your project webhooks.\n\nCompatibility: WooCommerce 5.0+, PHP 7.4+, API v1.",
             'long_pl' => "Oficjalna wtyczka Crynova dla WooCommerce (WordPress).\n\n• BTC, ETH, USDT (TRC-20/ERC-20/BEP-20), TRX, LTC, DOGE.\n• Tworzy fakturę przez API i przekierowuje na stronę płatności.\n• Podpisane webhooki (HMAC-SHA256) automatycznie oznaczają zamówienie jako opłacone.\n\nInstalacja: Plugins → Add New → Upload Plugin → aktywuj → WooCommerce → Settings → Payments → Crynova (API Key, Webhook Secret) → dodaj Webhook URL do webhooków projektu.\n\nZgodność: WooCommerce 5.0+, PHP 7.4+, API v1."],

            ['slug' => 'crynova-opencart', 'name' => 'OpenCart', 'root' => '',
             'description' => 'Розширення оплати криптовалютою для OpenCart 3.x на базі API Crynova.',
             'long' => "Офіційне розширення Crynova для OpenCart 3.x.\n\nВстановлення: Extensions → Installer → завантажте архів → Extensions → Payments → встановіть «Crynova» → вкажіть API Key, Webhook Secret і статус оплаченого замовлення → додайте Webhook URL у вебхуки проєкту.\n\nСумісність: OpenCart 3.x, PHP 7.4+, API v1.",
             'desc_en' => 'Crypto payment extension for OpenCart 3.x powered by the Crynova API.',
             'desc_pl' => 'Rozszerzenie płatności krypto dla OpenCart 3.x oparte na API Crynova.',
             'long_en' => "Official Crynova extension for OpenCart 3.x.\n\nInstall: Extensions → Installer → upload the archive → Extensions → Payments → install «Crynova» → set API Key, Webhook Secret and the paid order status → add the Webhook URL to your project webhooks.\n\nCompatibility: OpenCart 3.x, PHP 7.4+, API v1.",
             'long_pl' => "Oficjalne rozszerzenie Crynova dla OpenCart 3.x.\n\nInstalacja: Extensions → Installer → wgraj archiwum → Extensions → Payments → zainstaluj «Crynova» → ustaw API Key, Webhook Secret i status opłaconego zamówienia → dodaj Webhook URL do webhooków projektu.\n\nZgodność: OpenCart 3.x, PHP 7.4+, API v1."],

            ['slug' => 'crynova-prestashop', 'name' => 'PrestaShop', 'root' => 'crynovapay',
             'description' => 'Модуль оплати криптовалютою для PrestaShop 1.7 / 8.x.',
             'long' => "Офіційний модуль Crynova для PrestaShop 1.7 / 8.x.\n\nВстановлення: Modules → Upload a module → завантажте архів → налаштуйте API Key, Webhook Secret, API Base URL → додайте Webhook URL у вебхуки проєкту.\n\nСумісність: PrestaShop 1.7–8.x, API v1.",
             'desc_en' => 'Crypto payment module for PrestaShop 1.7 / 8.x.',
             'desc_pl' => 'Moduł płatności krypto dla PrestaShop 1.7 / 8.x.',
             'long_en' => "Official Crynova module for PrestaShop 1.7 / 8.x.\n\nInstall: Modules → Upload a module → upload the archive → set API Key, Webhook Secret, API Base URL → add the Webhook URL to your project webhooks.\n\nCompatibility: PrestaShop 1.7–8.x, API v1.",
             'long_pl' => "Oficjalny moduł Crynova dla PrestaShop 1.7 / 8.x.\n\nInstalacja: Modules → Upload a module → wgraj archiwum → ustaw API Key, Webhook Secret, API Base URL → dodaj Webhook URL do webhooków projektu.\n\nZgodność: PrestaShop 1.7–8.x, API v1."],

            ['slug' => 'crynova-drupal', 'name' => 'Drupal Commerce', 'root' => 'crynova_commerce',
             'description' => 'Платіжний шлюз Crynova для Drupal Commerce 9/10.',
             'long' => "Офіційний модуль Crynova для Drupal Commerce 2.x (Drupal 9/10).\n\nВстановлення: розпакуйте у modules/custom/ → увімкніть «Crynova Commerce» → Commerce → Payment gateways → додайте шлюз Crynova → вкажіть ключі → додайте /crynova/webhook у вебхуки.\n\nСумісність: Drupal 9/10 + Commerce, API v1.",
             'desc_en' => 'Crynova payment gateway for Drupal Commerce 9/10.',
             'desc_pl' => 'Bramka płatności Crynova dla Drupal Commerce 9/10.',
             'long_en' => "Official Crynova module for Drupal Commerce 2.x (Drupal 9/10).\n\nInstall: unpack into modules/custom/ → enable «Crynova Commerce» → Commerce → Payment gateways → add the Crynova gateway → set the keys → add /crynova/webhook to your project webhooks.\n\nCompatibility: Drupal 9/10 + Commerce, API v1.",
             'long_pl' => "Oficjalny moduł Crynova dla Drupal Commerce 2.x (Drupal 9/10).\n\nInstalacja: rozpakuj do modules/custom/ → włącz «Crynova Commerce» → Commerce → Payment gateways → dodaj bramkę Crynova → ustaw klucze → dodaj /crynova/webhook do webhooków projektu.\n\nZgodność: Drupal 9/10 + Commerce, API v1."],

            ['slug' => 'crynova-xenforo', 'name' => 'XenForo', 'root' => '',
             'description' => 'Платіжний провайдер Crynova для XenForo 2.2+.',
             'long' => "Офіційний додаток Crynova для XenForo 2.2+.\n\nВстановлення: завантажте вміст upload/ у корінь форуму → Admin CP → Add-ons → встановіть «Crynova Crypto Payments» → Setup → Payments → створіть профіль з провайдером Crynova → додайте payment_callback.php?_xfProvider=crynova у вебхуки.\n\nСумісність: XenForo 2.2+, API v1.",
             'desc_en' => 'Crynova payment provider for XenForo 2.2+.',
             'desc_pl' => 'Dostawca płatności Crynova dla XenForo 2.2+.',
             'long_en' => "Official Crynova add-on for XenForo 2.2+.\n\nInstall: upload the contents of upload/ to the forum root → Admin CP → Add-ons → install «Crynova Crypto Payments» → Setup → Payments → create a profile with the Crynova provider → add payment_callback.php?_xfProvider=crynova to your webhooks.\n\nCompatibility: XenForo 2.2+, API v1.",
             'long_pl' => "Oficjalny dodatek Crynova dla XenForo 2.2+.\n\nInstalacja: wgraj zawartość upload/ do katalogu głównego forum → Admin CP → Add-ons → zainstaluj «Crynova Crypto Payments» → Setup → Payments → utwórz profil z dostawcą Crynova → dodaj payment_callback.php?_xfProvider=crynova do webhooków.\n\nZgodność: XenForo 2.2+, API v1."],

            ['slug' => 'crynova-shopify', 'name' => 'Shopify', 'root' => 'crynova-shopify',
             'description' => 'PHP-міст для приймання криптоплатежів у Shopify через Crynova.',
             'long' => "Міст Crynova × Shopify (self-hosted PHP).\n\nShopify не дозволяє кастомні платіжні шлюзи без партнерства, тому інтеграція працює через ручний спосіб оплати + вебхуки.\n\nВстановлення: завантажте теку на PHP-хостинг, заповніть config.php → додайте ручний спосіб оплати «Crynova» у Shopify → налаштуйте вебхук orders/create → order-created.php → додайте crynova-webhook.php у вебхуки проєкту.\n\nСумісність: Shopify Admin API, API v1.",
             'desc_en' => 'PHP bridge to accept crypto payments on Shopify via Crynova.',
             'desc_pl' => 'Most PHP do przyjmowania płatności krypto w Shopify przez Crynova.',
             'long_en' => "Crynova × Shopify bridge (self-hosted PHP).\n\nShopify does not allow custom payment gateways without partnership, so the integration works via a manual payment method + webhooks.\n\nInstall: upload the folder to PHP hosting, fill in config.php → add a manual «Crynova» payment method in Shopify → set the orders/create webhook → order-created.php → add crynova-webhook.php to your project webhooks.\n\nCompatibility: Shopify Admin API, API v1.",
             'long_pl' => "Most Crynova × Shopify (self-hosted PHP).\n\nShopify nie zezwala na własne bramki bez partnerstwa, dlatego integracja działa przez ręczną metodę płatności + webhooki.\n\nInstalacja: wgraj folder na hosting PHP, uzupełnij config.php → dodaj ręczną metodę płatności «Crynova» w Shopify → ustaw webhook orders/create → order-created.php → dodaj crynova-webhook.php do webhooków projektu.\n\nZgodność: Shopify Admin API, API v1."],

            ['slug' => 'crynova-tilda', 'name' => 'Tilda', 'root' => 'crynova-tilda',
             'description' => 'PHP-міст для приймання криптоплатежів на Tilda через Crynova.',
             'long' => "Міст Crynova × Tilda (self-hosted PHP).\n\nВстановлення: завантажте теку на PHP-хостинг, заповніть config.php → спрямуйте кнопку/форму оплати Tilda на pay.php (amount, orderid) → додайте webhook.php у вебхуки проєкту.\n\nСумісність: Tilda, API v1.",
             'desc_en' => 'PHP bridge to accept crypto payments on Tilda via Crynova.',
             'desc_pl' => 'Most PHP do przyjmowania płatności krypto na Tilda przez Crynova.',
             'long_en' => "Crynova × Tilda bridge (self-hosted PHP).\n\nInstall: upload the folder to PHP hosting, fill in config.php → point your Tilda payment button/form to pay.php (amount, orderid) → add webhook.php to your project webhooks.\n\nCompatibility: Tilda, API v1.",
             'long_pl' => "Most Crynova × Tilda (self-hosted PHP).\n\nInstalacja: wgraj folder na hosting PHP, uzupełnij config.php → skieruj przycisk/formularz płatności Tilda na pay.php (amount, orderid) → dodaj webhook.php do webhooków projektu.\n\nZgodność: Tilda, API v1."],

            ['slug' => 'crynova-flute', 'name' => 'Flute CMS', 'root' => 'Crynova',
             'description' => 'Модуль приёма криптоплатежей для Flute CMS на базе Omnipay и API Crynova.',
             'long' => "Офіційний модуль Crynova для Flute CMS.\n\nПобудований на платіжній системі Flute (Omnipay): створення рахунку, редірект і перевірка колбеку обробляються ядром.\n\nВстановлення: Admin Panel → Modules → завантажте архів → активуйте «Crynova» → Payment Systems → Add → Crynova → вкажіть API Key і Webhook Secret → додайте Notification URL у вебхуки проєкту.\n\nСтруктура: module.json, Providers/, Drivers/, Gateway/ (Omnipay), Resources/views.\nСумісність: Flute CMS, API v1.",
             'desc_en' => 'Crynova crypto payments module for Flute CMS (Omnipay-based).',
             'desc_pl' => 'Moduł płatności krypto Crynova dla Flute CMS (oparty na Omnipay).',
             'long_en' => "Official Crynova module for Flute CMS.\n\nBuilt on Flute's Omnipay payment system: invoice creation, redirect and callback verification are handled by the core.\n\nInstall: Admin Panel → Modules → upload the archive → activate «Crynova» → Payment Systems → Add → Crynova → set API Key and Webhook Secret → add the Notification URL to your project webhooks.\n\nStructure: module.json, Providers/, Drivers/, Gateway/ (Omnipay), Resources/views.\nCompatibility: Flute CMS, API v1.",
             'long_pl' => "Oficjalny moduł Crynova dla Flute CMS.\n\nZbudowany na systemie płatności Flute (Omnipay): tworzenie faktury, przekierowanie i weryfikacja callbacku są obsługiwane przez rdzeń.\n\nInstalacja: Admin Panel → Modules → wgraj archiwum → aktywuj «Crynova» → Payment Systems → Add → Crynova → ustaw API Key i Webhook Secret → dodaj Notification URL do webhooków projektu.\n\nStruktura: module.json, Providers/, Drivers/, Gateway/ (Omnipay), Resources/views.\nZgodność: Flute CMS, API v1."],

            ['slug' => 'crynova-getcourse', 'name' => 'GetCourse', 'root' => 'crynova-getcourse',
             'description' => 'PHP-міст для приймання криптоплатежів у GetCourse через Crynova.',
             'long' => "Міст Crynova × GetCourse (self-hosted PHP).\n\nВстановлення: завантажте теку на PHP-хостинг, заповніть config.php (ключі Crynova + GetCourse API) → створіть кастомну платіжну систему в GetCourse, Payment URL → pay.php → додайте webhook.php у вебхуки проєкту.\n\nПісля оплати угода GetCourse автоматично позначається оплаченою.\nСумісність: GetCourse Deals API, API v1.",
             'desc_en' => 'PHP bridge to accept crypto payments in GetCourse via Crynova.',
             'desc_pl' => 'Most PHP do przyjmowania płatności krypto w GetCourse przez Crynova.',
             'long_en' => "Crynova × GetCourse bridge (self-hosted PHP).\n\nInstall: upload the folder to PHP hosting, fill in config.php (Crynova + GetCourse API keys) → create a custom payment system in GetCourse, Payment URL → pay.php → add webhook.php to your project webhooks.\n\nAfter payment the GetCourse deal is marked paid automatically.\nCompatibility: GetCourse Deals API, API v1.",
             'long_pl' => "Most Crynova × GetCourse (self-hosted PHP).\n\nInstalacja: wgraj folder na hosting PHP, uzupełnij config.php (klucze Crynova + GetCourse API) → utwórz własny system płatności w GetCourse, Payment URL → pay.php → dodaj webhook.php do webhooków projektu.\n\nPo płatności transakcja GetCourse jest automatycznie oznaczana jako opłacona.\nZgodność: GetCourse Deals API, API v1."],
        ];
    }

    public static function sync(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            return;
        }

        File::ensureDirectoryExists(storage_path('app/public/modules/images'));

        $sort = 0;
        foreach (self::modules() as $m) {
            $source = resource_path('modules/' . $m['slug']);
            if (! is_dir($source)) {
                continue;
            }

            $zipRel = 'modules/' . $m['slug'] . '.zip';
            self::buildZip($source, storage_path('app/public/' . $zipRel), $m['root']);

            $imgRel = null;
            if (is_file($source . '/cover.svg')) {
                $imgRel = 'modules/images/' . $m['slug'] . '.svg';
                File::copy($source . '/cover.svg', storage_path('app/public/' . $imgRel), true);
            }

            $exists = DB::table('integration_modules')->where('slug', $m['slug'])->exists();

            $row = [
                'name'             => $m['name'],
                'description'      => $m['description'],
                'long_description' => $m['long'],
                'icon'             => 'layout',
                'image_path'       => $imgRel,
                'version'          => '1.0.0',
                'file_path'        => $zipRel,
                'is_active'        => true,
                'sort'             => $sort++,
                'updated_at'       => now(),
                'created_at'       => $exists ? null : now(),
            ];

            // Translation columns may not exist yet on early seed runs.
            if (Schema::hasColumn('integration_modules', 'name_en')) {
                $row += [
                    'name_en'             => $m['name'], // brand names are identical across locales
                    'name_pl'             => $m['name'],
                    'description_en'      => $m['desc_en'] ?? null,
                    'description_pl'      => $m['desc_pl'] ?? null,
                    'long_description_en' => $m['long_en'] ?? null,
                    'long_description_pl' => $m['long_pl'] ?? null,
                ];
            }

            DB::table('integration_modules')->updateOrInsert(
                ['slug' => $m['slug']],
                array_filter($row, fn ($v) => $v !== null)
            );
        }
    }

    public static function removeAll(): void
    {
        foreach (self::modules() as $m) {
            File::delete(storage_path('app/public/modules/' . $m['slug'] . '.zip'));
            File::delete(storage_path('app/public/modules/images/' . $m['slug'] . '.svg'));
            DB::table('integration_modules')->where('slug', $m['slug'])->delete();
        }
    }

    /** Zip a directory; $prefix wraps contents in a top folder (empty = root). cover.svg skipped. */
    private static function buildZip(string $source, string $target, string $prefix): void
    {
        @unlink($target);
        $zip = new \ZipArchive();
        $zip->open($target, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isDir() || $file->getFilename() === 'cover.svg') {
                continue;
            }
            $rel = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($source))), '/');
            $local = ($prefix !== '' ? $prefix . '/' : '') . $rel;
            $zip->addFile($file->getPathname(), $local);
        }

        $zip->close();
    }
}
