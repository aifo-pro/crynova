<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Seeds the two official integration modules (WooCommerce, OpenCart) into the
 * catalog: builds a downloadable ZIP from resources/modules, publishes the
 * cover image, and inserts/updates the integration_modules row.
 */
return new class extends Migration
{
    private array $modules = [
        [
            'slug'    => 'crynova-woocommerce',
            'name'    => 'WooCommerce',
            'version' => '1.0.0',
            'icon'    => 'layout',
            'wrap'    => true, // archive contains the plugin folder at root
            'description' => 'Приймайте криптоплатежі у WooCommerce — клієнт обирає валюту на хостованому checkout Crynova.',
            'long'    => "Офіційний плагін Crynova для WooCommerce (WordPress).\n\nМожливості:\n• Приймання BTC, ETH, USDT (TRC-20/ERC-20/BEP-20), TRX, LTC, DOGE.\n• Створення рахунку через API Crynova та редірект на захищену сторінку оплати.\n• Підписані вебхуки (HMAC-SHA256) — замовлення автоматично позначається оплаченим.\n\nВстановлення:\n1. Plugins → Add New → Upload Plugin → завантажте архів.\n2. Активуйте «Crynova for WooCommerce».\n3. WooCommerce → Settings → Payments → Crynova: увімкніть, вкажіть API Key і Webhook Secret.\n4. Додайте Webhook URL із налаштувань у вебхуки проєкту Crynova.\n\nСумісність: WooCommerce 5.0+, PHP 7.4+, Crynova API v1.",
        ],
        [
            'slug'    => 'crynova-opencart',
            'name'    => 'OpenCart',
            'version' => '1.0.0',
            'icon'    => 'layout',
            'wrap'    => false, // archive must expose upload/ + install.xml at root
            'description' => 'Розширення оплати криптовалютою для OpenCart 3.x на базі API Crynova.',
            'long'    => "Офіційне розширення Crynova для OpenCart 3.x.\n\nМожливості:\n• Приймання BTC, ETH, USDT, TRX, LTC, DOGE.\n• Створення рахунку через API Crynova та редірект на сторінку оплати.\n• Підписані вебхуки (HMAC-SHA256) — статус замовлення оновлюється автоматично.\n\nВстановлення:\n1. Extensions → Installer → завантажте архів.\n2. Extensions → Payments → встановіть та відредагуйте «Crynova».\n3. Вкажіть API Key, Webhook Secret і статус оплаченого замовлення.\n4. Додайте Webhook URL із налаштувань у вебхуки проєкту Crynova.\n\nСумісність: OpenCart 3.x, PHP 7.4+, Crynova API v1.",
        ],
    ];

    public function up(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            return; // can't build archives — skip gracefully
        }

        $zipDir = storage_path('app/public/modules');
        $imgDir = storage_path('app/public/modules/images');
        File::ensureDirectoryExists($zipDir);
        File::ensureDirectoryExists($imgDir);

        $sort = 0;
        foreach ($this->modules as $m) {
            $source = resource_path('modules/' . $m['slug']);
            if (! is_dir($source)) {
                continue;
            }

            // Build the ZIP (cover.svg excluded — it is the catalog image only).
            $zipRel = 'modules/' . $m['slug'] . '.zip';
            $this->buildZip($source, storage_path('app/public/' . $zipRel), $m['wrap'] ? $m['slug'] : '');

            // Publish the cover image.
            $imgRel = null;
            if (is_file($source . '/cover.svg')) {
                $imgRel = 'modules/images/' . $m['slug'] . '.svg';
                File::copy($source . '/cover.svg', storage_path('app/public/' . $imgRel));
            }

            DB::table('integration_modules')->updateOrInsert(
                ['slug' => $m['slug']],
                [
                    'name'             => $m['name'],
                    'description'      => $m['description'],
                    'long_description' => $m['long'],
                    'icon'             => $m['icon'],
                    'image_path'       => $imgRel,
                    'version'          => $m['version'],
                    'file_path'        => $zipRel,
                    'external_url'     => null,
                    'is_active'        => true,
                    'sort'             => $sort++,
                    'updated_at'       => now(),
                    'created_at'       => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        foreach ($this->modules as $m) {
            File::delete(storage_path('app/public/modules/' . $m['slug'] . '.zip'));
            File::delete(storage_path('app/public/modules/images/' . $m['slug'] . '.svg'));
            DB::table('integration_modules')->where('slug', $m['slug'])->delete();
        }
    }

    /** Zip a directory; $prefix wraps contents in a top folder (empty = contents at root). cover.svg is skipped. */
    private function buildZip(string $source, string $target, string $prefix): void
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
};
