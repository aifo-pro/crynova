<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class Doctor extends Command
{
    protected $signature = 'crynova:doctor {--json : Output as JSON}';

    protected $description = 'Check VPS/production requirements for Crynova (PHP, Laravel, assets, DB, permissions)';

    /** @var list<array{level: string, check: string, message: string}> */
    private array $results = [];

    public function handle(): int
    {
        $this->checkPhp();
        $this->checkExtensions();
        $this->checkEnv();
        $this->checkDatabase();
        $this->checkMigrations();
        $this->checkPermissions();
        $this->checkAssets();
        $this->checkStorageLink();
        $this->checkCacheState();

        if ($this->option('json')) {
            $this->line(json_encode([
                'ok'      => ! $this->hasFailures(),
                'checks'  => $this->results,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $this->hasFailures() ? self::FAILURE : self::SUCCESS;
        }

        $this->newLine();
        $this->info('Crynova production doctor');
        $this->line(str_repeat('─', 60));

        foreach ($this->results as $row) {
            $icon = match ($row['level']) {
                'ok'      => '<fg=green>✓</>',
                'warn'    => '<fg=yellow>!</>',
                default   => '<fg=red>✗</>',
            };
            $this->line(" {$icon} {$row['check']}: {$row['message']}");
        }

        $this->newLine();

        if ($this->hasFailures()) {
            $this->error('Found blocking issues. Fix them and run: php artisan crynova:doctor');
            $this->line('See deploy/PRODUCTION-CHECKLIST.md and deploy/FASTPANEL.md');

            return self::FAILURE;
        }

        if ($this->hasWarnings()) {
            $this->warn('No blockers, but some optional services may be missing (queue/cron).');
        } else {
            $this->info('All checks passed.');
        }

        return self::SUCCESS;
    }

    private function checkPhp(): void
    {
        $version = PHP_VERSION;
        if (version_compare($version, '8.3.0', '>=')) {
            $this->recordPass('PHP', "version {$version}");
        } else {
            $this->recordFail('PHP', "need 8.3+, got {$version}");
        }
    }

    private function checkExtensions(): void
    {
        $required = [
            'bcmath'    => 'crypto amounts & balances',
            'curl'      => 'HTTP / blockchain APIs',
            'fileinfo'  => 'file uploads',
            'json'      => 'API',
            'mbstring'  => 'strings',
            'openssl'   => 'encryption',
            'pdo_mysql' => 'MySQL database',
            'tokenizer' => 'Laravel',
            'xml'       => 'Laravel',
            'zip'       => 'Composer packages',
        ];

        $recommended = [
            'gd'   => 'QR codes (2FA)',
            'intl' => 'locales',
        ];

        foreach ($required as $ext => $why) {
            if (extension_loaded($ext)) {
                $this->recordPass("ext:{$ext}", 'loaded');
            } else {
                $this->recordFail("ext:{$ext}", "missing — required for {$why}");
            }
        }

        foreach ($recommended as $ext => $why) {
            if (extension_loaded($ext)) {
                $this->recordPass("ext:{$ext}", 'loaded');
            } else {
                $this->warnCheck("ext:{$ext}", "missing — recommended for {$why}");
            }
        }
    }

    private function checkEnv(): void
    {
        if (filled(config('app.key'))) {
            $this->recordPass('APP_KEY', 'set');
        } else {
            $this->recordFail('APP_KEY', 'empty — run: php artisan key:generate');
        }

        $url = (string) config('app.url');
        if ($url !== '' && ! str_contains($url, 'your-domain')) {
            $this->recordPass('APP_URL', $url);
        } else {
            $this->recordFail('APP_URL', 'set APP_URL=https://crynova.io in .env');
        }

        if (config('app.debug')) {
            $this->warnCheck('APP_DEBUG', 'true — set false in production');
        } else {
            $this->recordPass('APP_DEBUG', 'false');
        }
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $this->recordPass('Database', config('database.default').' connected');
        } catch (\Throwable $e) {
            $this->recordFail('Database', $e->getMessage());
        }
    }

    private function checkMigrations(): void
    {
        try {
            if (! Schema::hasTable('migrations')) {
                $this->recordFail('Migrations', 'table missing — run: php artisan migrate --force');

                return;
            }

            Artisan::call('migrate:status', ['--no-ansi' => true]);
            $output = Artisan::output();

            if (str_contains($output, 'Pending')) {
                $this->recordFail('Migrations', 'pending migrations — run: php artisan migrate --force');
            } else {
                $this->recordPass('Migrations', 'up to date');
            }

            foreach (['sessions', 'jobs', 'cache'] as $table) {
                if (Schema::hasTable($table)) {
                    $this->recordPass("table:{$table}", 'exists');
                } else {
                    $this->recordFail("table:{$table}", 'missing — run migrate');
                }
            }
        } catch (\Throwable $e) {
            $this->recordFail('Migrations', $e->getMessage());
        }
    }

    private function checkPermissions(): void
    {
        foreach (['storage', 'bootstrap/cache'] as $path) {
            $full = base_path($path);
            if (is_writable($full)) {
                $this->recordPass("writable:{$path}", 'ok');
            } else {
                $this->recordFail("writable:{$path}", 'not writable by PHP user');
            }
        }
    }

    private function checkAssets(): void
    {
        $manifestPath = public_path('build/manifest.json');

        if (! File::exists($manifestPath)) {
            $this->recordFail('Vite build', 'public/build/manifest.json missing — run: npm ci && npm run build');

            return;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        $css = $manifest['resources/css/app.css']['file'] ?? null;
        $js = $manifest['resources/js/app.js']['file'] ?? null;

        if ($css && File::exists(public_path('build/'.$css))) {
            $this->recordPass('Vite CSS', $css);
        } else {
            $this->recordFail('Vite CSS', 'built file missing in public/build/');
        }

        if ($js && File::exists(public_path('build/'.$js))) {
            $this->recordPass('Vite JS', $js.' (Alpine.js UI)');
        } else {
            $this->recordFail('Vite JS', 'built file missing — toggles/tabs will not work');
        }

        if (File::exists(storage_path('framework/views'))) {
            $cachedViews = count(File::files(storage_path('framework/views')));
            if ($cachedViews > 0) {
                $this->warnCheck('View cache', "{$cachedViews} cached views — after npm run build run: php artisan view:clear");
            }
        }
    }

    private function checkStorageLink(): void
    {
        if (File::exists(public_path('storage')) || is_link(public_path('storage'))) {
            $this->recordPass('storage:link', 'ok');
        } else {
            $this->warnCheck('storage:link', 'missing — run: php artisan storage:link');
        }
    }

    private function checkCacheState(): void
    {
        if (File::exists(base_path('bootstrap/cache/config.php'))) {
            $this->recordPass('config:cache', 'active');
        } else {
            $this->warnCheck('config:cache', 'not cached — run: php artisan config:cache');
        }

        if (config('queue.default') === 'database') {
            $this->warnCheck('Queue worker', 'QUEUE_CONNECTION=database — install supervisor (deploy/supervisor/)');
        }

        $this->warnCheck('Scheduler', 'add cron: * * * * * php artisan schedule:run (payments, webhooks)');
    }

    private function recordPass(string $check, string $message): void
    {
        $this->results[] = ['level' => 'ok', 'check' => $check, 'message' => $message];
    }

    private function warnCheck(string $check, string $message): void
    {
        $this->results[] = ['level' => 'warn', 'check' => $check, 'message' => $message];
    }

    private function recordFail(string $check, string $message): void
    {
        $this->results[] = ['level' => 'fail', 'check' => $check, 'message' => $message];
    }

    private function hasFailures(): bool
    {
        return collect($this->results)->contains(fn ($r) => $r['level'] === 'fail');
    }

    private function hasWarnings(): bool
    {
        return collect($this->results)->contains(fn ($r) => $r['level'] === 'warn');
    }
}
