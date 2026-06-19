<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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
             'long' => "Офіційний плагін Crynova для WooCommerce (WordPress).\n\n• BTC, ETH, USDT (TRC-20/ERC-20/BEP-20), TRX, LTC, DOGE.\n• Створення рахунку через API та редірект на сторінку оплати.\n• Підписані вебхуки (HMAC-SHA256) — замовлення позначається оплаченим автоматично.\n\nВстановлення: Plugins → Add New → Upload Plugin → активуйте → WooCommerce → Settings → Payments → Crynova (API Key, Webhook Secret) → додайте Webhook URL у вебхуки проєкту.\n\nСумісність: WooCommerce 5.0+, PHP 7.4+, API v1."],

            ['slug' => 'crynova-opencart', 'name' => 'OpenCart', 'root' => '',
             'description' => 'Розширення оплати криптовалютою для OpenCart 3.x на базі API Crynova.',
             'long' => "Офіційне розширення Crynova для OpenCart 3.x.\n\nВстановлення: Extensions → Installer → завантажте архів → Extensions → Payments → встановіть «Crynova» → вкажіть API Key, Webhook Secret і статус оплаченого замовлення → додайте Webhook URL у вебхуки проєкту.\n\nСумісність: OpenCart 3.x, PHP 7.4+, API v1."],

            ['slug' => 'crynova-prestashop', 'name' => 'PrestaShop', 'root' => 'crynovapay',
             'description' => 'Модуль оплати криптовалютою для PrestaShop 1.7 / 8.x.',
             'long' => "Офіційний модуль Crynova для PrestaShop 1.7 / 8.x.\n\nВстановлення: Modules → Upload a module → завантажте архів → налаштуйте API Key, Webhook Secret, API Base URL → додайте Webhook URL у вебхуки проєкту.\n\nСумісність: PrestaShop 1.7–8.x, API v1."],

            ['slug' => 'crynova-drupal', 'name' => 'Drupal Commerce', 'root' => 'crynova_commerce',
             'description' => 'Платіжний шлюз Crynova для Drupal Commerce 9/10.',
             'long' => "Офіційний модуль Crynova для Drupal Commerce 2.x (Drupal 9/10).\n\nВстановлення: розпакуйте у modules/custom/ → увімкніть «Crynova Commerce» → Commerce → Payment gateways → додайте шлюз Crynova → вкажіть ключі → додайте /crynova/webhook у вебхуки.\n\nСумісність: Drupal 9/10 + Commerce, API v1."],

            ['slug' => 'crynova-xenforo', 'name' => 'XenForo', 'root' => '',
             'description' => 'Платіжний провайдер Crynova для XenForo 2.2+.',
             'long' => "Офіційний додаток Crynova для XenForo 2.2+.\n\nВстановлення: завантажте вміст upload/ у корінь форуму → Admin CP → Add-ons → встановіть «Crynova Crypto Payments» → Setup → Payments → створіть профіль з провайдером Crynova → додайте payment_callback.php?_xfProvider=crynova у вебхуки.\n\nСумісність: XenForo 2.2+, API v1."],

            ['slug' => 'crynova-shopify', 'name' => 'Shopify', 'root' => 'crynova-shopify',
             'description' => 'PHP-міст для приймання криптоплатежів у Shopify через Crynova.',
             'long' => "Міст Crynova × Shopify (self-hosted PHP).\n\nShopify не дозволяє кастомні платіжні шлюзи без партнерства, тому інтеграція працює через ручний спосіб оплати + вебхуки.\n\nВстановлення: завантажте теку на PHP-хостинг, заповніть config.php → додайте ручний спосіб оплати «Crynova» у Shopify → налаштуйте вебхук orders/create → order-created.php → додайте crynova-webhook.php у вебхуки проєкту.\n\nСумісність: Shopify Admin API, API v1."],

            ['slug' => 'crynova-tilda', 'name' => 'Tilda', 'root' => 'crynova-tilda',
             'description' => 'PHP-міст для приймання криптоплатежів на Tilda через Crynova.',
             'long' => "Міст Crynova × Tilda (self-hosted PHP).\n\nВстановлення: завантажте теку на PHP-хостинг, заповніть config.php → спрямуйте кнопку/форму оплати Tilda на pay.php (amount, orderid) → додайте webhook.php у вебхуки проєкту.\n\nСумісність: Tilda, API v1."],

            ['slug' => 'crynova-flute', 'name' => 'Flute CMS', 'root' => 'Crynova',
             'description' => 'Модуль приёма криптоплатежей для Flute CMS на базе Omnipay и API Crynova.',
             'long' => "Офіційний модуль Crynova для Flute CMS.\n\nПобудований на платіжній системі Flute (Omnipay): створення рахунку, редірект і перевірка колбеку обробляються ядром.\n\nВстановлення: Admin Panel → Modules → завантажте архів → активуйте «Crynova» → Payment Systems → Add → Crynova → вкажіть API Key і Webhook Secret → додайте Notification URL у вебхуки проєкту.\n\nСтруктура: module.json, Providers/, Drivers/, Gateway/ (Omnipay), Resources/views.\nСумісність: Flute CMS, API v1."],

            ['slug' => 'crynova-getcourse', 'name' => 'GetCourse', 'root' => 'crynova-getcourse',
             'description' => 'PHP-міст для приймання криптоплатежів у GetCourse через Crynova.',
             'long' => "Міст Crynova × GetCourse (self-hosted PHP).\n\nВстановлення: завантажте теку на PHP-хостинг, заповніть config.php (ключі Crynova + GetCourse API) → створіть кастомну платіжну систему в GetCourse, Payment URL → pay.php → додайте webhook.php у вебхуки проєкту.\n\nПісля оплати угода GetCourse автоматично позначається оплаченою.\nСумісність: GetCourse Deals API, API v1."],
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
            DB::table('integration_modules')->updateOrInsert(
                ['slug' => $m['slug']],
                array_filter([
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
                ], fn ($v) => $v !== null)
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
