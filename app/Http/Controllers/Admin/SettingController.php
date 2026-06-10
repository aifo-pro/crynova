<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\ApiIpListService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Single source of truth for all editable platform settings.
     */
    public static function schema(): array
    {
        return [
            'site' => [
                'label' => 'Основні налаштування',
                'hint' => 'Назва, опис, URL сайту та доступність реєстрації.',
                'icon'  => 'globe',
                'fields' => [
                    'site_name' => ['type' => 'text', 'label' => 'Назва сайту', 'default' => 'Crynova'],
                    'site_description' => ['type' => 'text', 'label' => 'Опис сайту', 'default' => 'Приймати платежі може бути легко та швидко!'],
                    'site_keywords' => ['type' => 'text', 'label' => 'Ключові слова', 'default' => 'Інтернет еквайринг, прийом платежів, криптоплатежі'],
                    'site_url' => ['type' => 'url', 'label' => 'URL сайту', 'default' => config('app.url')],
                    'google_site_verification' => ['type' => 'text', 'label' => 'Google Search Console — код підтвердження', 'default' => '', 'help' => 'Встав вміст content із <meta name="google-site-verification" ...> (тільки код, без тегу). Для верифікації сайту в Google Search Console.'],
                    'site_version' => ['type' => 'text', 'label' => 'Версія сайту', 'default' => '1.0.0'],
                    'registration_enabled' => ['type' => 'bool', 'label' => 'Дозволити реєстрацію нових користувачів', 'default' => '1', 'help' => 'Якщо вимкнено, нові акаунти не можна створити через стандартну форму.'],
                    'email_verification_enabled' => ['type' => 'select', 'label' => 'Підтвердження електронної пошти', 'default' => '0', 'options' => ['1' => 'Включено', '0' => 'Вимкнено']],
                    'default_site_language' => ['type' => 'select', 'label' => 'Мова сайту', 'default' => 'uk', 'options' => ['uk' => 'Українська', 'en' => 'English']],
                ],
            ],
            'social' => [
                'label' => 'Посилання на соцмережі',
                'hint' => 'Показуються у футері, меню користувача та schema.org sameAs.',
                'icon'  => 'link',
                'fields' => [
                    'telegram_support_url' => ['type' => 'url', 'label' => 'Telegram (канал / підтримка)', 'default' => ''],
                    'youtube_url' => ['type' => 'url', 'label' => 'YouTube', 'default' => ''],
                    'telegram_bot_url' => ['type' => 'url', 'label' => 'Telegram-бот (меню користувача)', 'default' => ''],
                ],
            ],
            'social_auth' => [
                'label' => 'Google / Telegram авторизація',
                'hint' => 'Вхід і реєстрація через Google OAuth2 та Telegram Login Widget.',
                'icon'  => 'user',
                'fields' => [
                    'google_auth_enabled' => ['type' => 'bool', 'label' => 'Увімкнути вхід через Google', 'default' => '0'],
                    'google_client_id' => ['type' => 'text', 'label' => 'Google Client ID', 'default' => ''],
                    'google_client_secret' => ['type' => 'password', 'label' => 'Google Client Secret', 'default' => '', 'encrypted' => true, 'placeholder' => 'Залиште порожнім, щоб не змінювати'],
                    'google_redirect_uri' => ['type' => 'url', 'label' => 'Google Redirect URI', 'default' => url('/auth/google/callback'), 'help' => 'Додайте цей URL у Google Cloud Console в Authorized redirect URIs.'],
                    'telegram_auth_enabled' => ['type' => 'bool', 'label' => 'Увімкнути вхід через Telegram', 'default' => '0'],
                    'telegram_login_bot_username' => ['type' => 'text', 'label' => 'Telegram bot username', 'default' => '', 'help' => 'Наприклад: crynova_bot. Без @. Домен сайту має бути дозволений у BotFather /setdomain.'],
                    'telegram_login_bot_token' => ['type' => 'password', 'label' => 'Telegram bot token для авторизації', 'default' => '', 'encrypted' => true, 'placeholder' => 'Залиште порожнім, щоб не змінювати'],
                ],
            ],
            'api_ips' => [
                'label' => 'Налаштування файлу IPS.JSON',
                'hint' => 'Глобальний whitelist IP для API. Якщо список порожній, глобальне обмеження не застосовується.',
                'icon'  => 'shield',
                'fields' => [
                    'api_ips_json' => [
                        'type' => 'textarea',
                        'label' => 'Значення файлу (JSON формат)',
                        'default' => "{\n  \"list\": \"\"\n}",
                        'wide' => true,
                        'rows' => 7,
                        'help' => 'Приклад: {"list":"77.83.102.155"} або {"list":"77.83.102.155, 192.168.1.1"}. Підтримуються CIDR-мережі, наприклад 10.0.0.0/24.',
                    ],
                ],
            ],
            'maintenance' => [
                'label' => 'Режим обслуговування',
                'hint' => 'Коли увімкнено, користувачі бачать сторінку обслуговування. Адмін-розділ і логін залишаються доступними.',
                'icon'  => 'settings',
                'fields' => [
                    'maintenance_mode' => ['type' => 'bool', 'label' => 'Увімкнути режим обслуговування', 'default' => '0'],
                    'maintenance_message' => ['type' => 'textarea', 'label' => "Текст повідомлення (необов'язково)", 'default' => 'Сайт тимчасово не працює.'],
                ],
            ],
            'telegram_user' => [
                'label' => 'Telegram-бот для користувачів',
                'hint' => 'Надсилає користувачам сповіщення про вхід, оплату рахунку та зарахування на баланс.',
                'icon'  => 'user',
                'fields' => [
                    'telegram_user_bot_token' => ['type' => 'password', 'label' => 'Токен бота для користувачів', 'default' => '', 'encrypted' => true, 'placeholder' => 'Залиште порожнім, щоб не змінювати'],
                    'telegram_user_notifications_enabled' => ['type' => 'bool', 'label' => 'Увімкнути Telegram-сповіщення користувачам', 'default' => '0'],
                ],
            ],
            'telegram_admin' => [
                'label' => 'Telegram-бот для адміністратора',
                'hint' => 'Окремий бот для подій: реєстрація, новий мерчант, виплата, платіж.',
                'icon'  => 'shield',
                'fields' => [
                    'telegram_admin_bot_token' => ['type' => 'password', 'label' => 'Токен бота для адміністратора', 'default' => '', 'encrypted' => true, 'placeholder' => 'Залиште порожнім, щоб не змінювати'],
                    'telegram_admin_ids' => ['type' => 'text', 'label' => 'Telegram ID адміністраторів', 'default' => '', 'help' => 'Можна вказати декілька ID через кому.'],
                    'telegram_admin_language' => ['type' => 'select', 'label' => 'Мова сповіщень для адміністратора', 'default' => 'uk', 'options' => ['uk' => 'Українська', 'en' => 'English']],
                    'telegram_admin_notifications_enabled' => ['type' => 'bool', 'label' => 'Увімкнути Telegram-сповіщення адміністраторам', 'default' => '0'],
                ],
            ],
            'recaptcha' => [
                'label' => 'Google reCAPTCHA v3',
                'hint' => 'Захист від ботів на сторінках входу, реєстрації та відновлення пароля.',
                'icon'  => 'shield-check',
                'fields' => [
                    'recaptcha_enabled' => ['type' => 'bool', 'label' => 'Увімкнути Google reCAPTCHA v3', 'default' => '0'],
                    'recaptcha_site_key' => ['type' => 'text', 'label' => 'Публічний ключ (Site Key)', 'default' => ''],
                    'recaptcha_secret_key' => ['type' => 'password', 'label' => 'Секретний ключ (Secret Key)', 'default' => '', 'encrypted' => true, 'placeholder' => 'Залиште порожнім, щоб не змінювати'],
                    'recaptcha_min_score' => ['type' => 'number', 'label' => 'Мінімальний score', 'default' => '0.5', 'step' => '0.1'],
                ],
            ],
            'mail' => [
                'label' => 'SMTP / Email',
                'hint' => 'SMTP для системних листів, відновлення пароля, чеків та розсилок.',
                'icon'  => 'message-circle',
                'fields' => [
                    'mail_enabled' => ['type' => 'bool', 'label' => 'Увімкнути відправку email', 'default' => '0'],
                    'mail_mailer' => ['type' => 'select', 'label' => 'Mailer', 'default' => 'smtp', 'options' => ['smtp' => 'SMTP', 'log' => 'Log only']],
                    'mail_host' => ['type' => 'text', 'label' => 'SMTP host', 'default' => '127.0.0.1'],
                    'mail_port' => ['type' => 'number', 'label' => 'SMTP port', 'default' => '587'],
                    'mail_encryption' => ['type' => 'select', 'label' => 'Encryption', 'default' => 'tls', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None']],
                    'mail_username' => ['type' => 'text', 'label' => 'SMTP username', 'default' => ''],
                    'mail_password' => ['type' => 'password', 'label' => 'SMTP password', 'default' => '', 'encrypted' => true, 'placeholder' => 'Залиште порожнім, щоб не змінювати'],
                    'mail_from_address' => ['type' => 'email', 'label' => 'From email', 'default' => 'no-reply@crynova.io'],
                    'mail_from_name' => ['type' => 'text', 'label' => 'From name', 'default' => 'Crynova'],
                    'mail_reply_to' => ['type' => 'email', 'label' => 'Reply-To', 'default' => 'support@crynova.io'],
                ],
            ],
            'fees' => [
                'label' => 'Комісії',
                'hint' => 'Параметри комісій платформи.',
                'icon'  => 'banknote',
                'fields' => [
                    'default_fee_percent' => ['type' => 'number', 'label' => 'Комісія сервісу за замовчуванням, %', 'default' => '1.00', 'step' => '0.01'],
                    'exchange_fee_percent' => ['type' => 'number', 'label' => 'Комісія обміну, %', 'default' => '0.5', 'step' => '0.01'],
                    'min_withdrawal_usd' => ['type' => 'number', 'label' => 'Мін. вивід, USD', 'default' => '10'],
                ],
            ],
            'limits' => [
                'label' => 'Ліміти та рахунки',
                'hint' => 'Обмеження рахунків та поведінка checkout.',
                'icon'  => 'layers',
                'fields' => [
                    'max_invoice_amount_usd' => ['type' => 'number', 'label' => 'Макс. сума рахунку, USD (0 - без ліміту)', 'default' => '50000'],
                    'invoice_ttl_minutes' => ['type' => 'number', 'label' => 'Термін життя рахунку, хв', 'default' => '30'],
                    'require_exact_amount' => ['type' => 'bool', 'label' => 'Вимагати точну суму', 'default' => '0'],
                ],
            ],
            'risk' => [
                'label' => 'Ризик і безпека',
                'hint' => 'AML та ручні перевірки.',
                'icon'  => 'shield',
                'fields' => [
                    'require_2fa_for_withdrawals' => ['type' => 'bool', 'label' => 'Вимагати 2FA для виводів', 'default' => '1'],
                    'manual_withdrawal_review' => ['type' => 'bool', 'label' => 'Ручна перевірка виводів', 'default' => '1'],
                    'aml_default_enabled' => ['type' => 'bool', 'label' => 'AML за замовчуванням для нових проєктів', 'default' => '0'],
                ],
            ],
            'webhooks' => [
                'label' => 'Webhooks',
                'hint' => 'Параметри доставки webhook-подій.',
                'icon'  => 'bell',
                'fields' => [
                    'webhook_timeout_seconds' => ['type' => 'number', 'label' => 'Таймаут webhook, сек', 'default' => '10'],
                    'webhook_max_attempts' => ['type' => 'number', 'label' => 'Макс. спроб доставки', 'default' => '5'],
                ],
            ],
            'blockchain' => [
                'label' => 'HD-гаманці (xpub)',
                'hint' => 'Account-level extended PUBLIC keys для генерації депозитних адрес. Приватні ключі не зберігайте в Crynova. Після збереження: php artisan crynova:generate-addresses',
                'icon'  => 'wallet',
                'fields' => [
                    'hd_xpub_btc'  => ['type' => 'password', 'label' => 'BTC zpub/xpub (m/84\'/0\'/0\')', 'default' => '', 'encrypted' => true, 'placeholder' => 'zpub…'],
                    'hd_xpub_ltc'  => ['type' => 'password', 'label' => 'LTC zpub (m/84\'/2\'/0\')', 'default' => '', 'encrypted' => true, 'placeholder' => 'zpub…'],
                    'hd_xpub_doge' => ['type' => 'password', 'label' => 'DOGE xpub (m/44\'/3\'/0\')', 'default' => '', 'encrypted' => true, 'placeholder' => 'dgub…'],
                    'hd_xpub_eth'  => ['type' => 'password', 'label' => 'ETH/BSC xpub (m/44\'/60\'/0\')', 'default' => '', 'encrypted' => true, 'placeholder' => 'xpub…'],
                    'hd_xpub_tron' => ['type' => 'password', 'label' => 'TRON xpub (m/44\'/195\'/0\')', 'default' => '', 'encrypted' => true, 'placeholder' => 'xpub…'],
                    'etherscan_api_key' => ['type' => 'password', 'label' => 'Etherscan API key (ETH платежі)', 'default' => '', 'encrypted' => true, 'placeholder' => ''],
                    'bscscan_api_key'   => ['type' => 'password', 'label' => 'BscScan API key (BEP-20)', 'default' => '', 'encrypted' => true, 'placeholder' => ''],
                ],
            ],
        ];
    }

    public function index()
    {
        $schema = self::schema();
        $values = [];
        $configured = [];

        foreach ($schema as $group) {
            foreach ($group['fields'] as $key => $field) {
                $stored = Setting::get($key, $field['default'] ?? null);

                // Encrypted secrets are never echoed back into the form, but we
                // still flag whether a value is stored so the UI can show it.
                $values[$key] = ($field['encrypted'] ?? false) ? '' : $stored;
                $configured[$key] = is_string($stored) ? trim($stored) !== '' : ! empty($stored);
            }
        }

        return view('admin.settings.index', compact('schema', 'values', 'configured'));
    }

    public function update(Request $request, ApiIpListService $apiIps)
    {
        $schema = self::schema();

        foreach ($schema as $groupKey => $group) {
            foreach ($group['fields'] as $key => $field) {
                if ($field['type'] === 'bool') {
                    Setting::set($key, $request->boolean($key) ? '1' : '0', false, 'bool', $groupKey, $field['label'] ?? null);

                    continue;
                }

                if (($field['encrypted'] ?? false) && ! $request->filled($key)) {
                    continue;
                }

                $value = (string) $request->input($key, $field['default'] ?? '');

                if ($key === ApiIpListService::SETTING_KEY) {
                    $value = $apiIps->validateAndNormalize($value);
                }

                Setting::set(
                    $key,
                    $value,
                    (bool) ($field['encrypted'] ?? false),
                    in_array($field['type'], ['bool'], true) ? $field['type'] : 'string',
                    $groupKey,
                    $field['label'] ?? null,
                );
            }
        }

        AuditLog::record('settings.updated');

        return back()->with('success', 'Налаштування збережено.');
    }
}
