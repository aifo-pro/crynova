<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $sort = (int) DB::table('support_templates')->max('sort') + 1;

        $rows = [
            [
                'title' => 'Недоплата', 'category' => 'Платежі',
                'body'    => "Ми отримали суму меншу за виставлену. Щоб зарахувати платіж повністю, надішліть, будь ласка, різницю на ту саму адресу до завершення терміну дії рахунку.",
                'body_en' => "We received less than the invoiced amount. To settle the payment in full, please send the remaining difference to the same address before the invoice expires.",
                'body_pl' => "Otrzymaliśmy kwotę niższą niż na fakturze. Aby w pełni rozliczyć płatność, prześlij pozostałą różnicę na ten sam adres przed wygaśnięciem faktury.",
                'body_ru' => "Мы получили сумму меньше выставленной. Чтобы зачислить платёж полностью, отправьте, пожалуйста, разницу на тот же адрес до истечения срока счёта.",
            ],
            [
                'title' => 'Переплата', 'category' => 'Платежі',
                'body'    => "Ви надіслали більше за суму рахунку. Надлишок буде зараховано на ваш баланс. Якщо потрібно повернення — повідомте адресу для повернення.",
                'body_en' => "You sent more than the invoice amount. The surplus has been credited to your balance. If you'd like a refund, please share a refund address.",
                'body_pl' => "Wysłałeś więcej niż kwota faktury. Nadwyżka została dopisana do Twojego salda. Jeśli chcesz zwrot, podaj adres do zwrotu.",
                'body_ru' => "Вы отправили больше суммы счёта. Излишек зачислен на ваш баланс. Если нужен возврат — сообщите адрес для возврата.",
            ],
            [
                'title' => 'Повернення коштів', 'category' => 'Повернення',
                'body'    => "Ми оформили запит на повернення. Кошти повернуться на вихідну адресу протягом 1–3 робочих днів залежно від мережі. Хеш транзакції надішлемо після відправлення.",
                'body_en' => "We've initiated your refund. Funds will return to the original address within 1–3 business days depending on the network. We'll share the transaction hash once sent.",
                'body_pl' => "Rozpoczęliśmy zwrot. Środki wrócą na pierwotny adres w ciągu 1–3 dni roboczych, w zależności od sieci. Hash transakcji prześlemy po wysłaniu.",
                'body_ru' => "Мы оформили возврат. Средства вернутся на исходный адрес в течение 1–3 рабочих дней в зависимости от сети. Хеш транзакции пришлём после отправки.",
            ],
            [
                'title' => 'Верифікація / KYC', 'category' => 'Акаунт',
                'body'    => "Для проходження верифікації завантажте, будь ласка, документ, що посвідчує особу, та підтвердження адреси у розділі верифікації. Перевірка займає до 24 годин.",
                'body_en' => "To complete verification, please upload a government-issued ID and proof of address in the verification section. Review takes up to 24 hours.",
                'body_pl' => "Aby zakończyć weryfikację, prześlij dokument tożsamości oraz potwierdzenie adresu w sekcji weryfikacji. Weryfikacja trwa do 24 godzin.",
                'body_ru' => "Для прохождения верификации загрузите, пожалуйста, удостоверение личности и подтверждение адреса в разделе верификации. Проверка занимает до 24 часов.",
            ],
            [
                'title' => 'Скидання 2FA', 'category' => 'Акаунт',
                'body'    => "Щоб скинути двофакторну автентифікацію, підтвердіть особу секретним словом, яке ви задали під час її увімкнення. Напишіть це слово у відповідь — і ми скинемо 2FA.",
                'body_en' => "To reset two-factor authentication, confirm your identity with the secret word you set when enabling it. Reply with that word and we'll reset your 2FA.",
                'body_pl' => "Aby zresetować uwierzytelnianie dwuskładnikowe, potwierdź tożsamość słowem tajnym ustawionym przy jego włączaniu. Odpisz tym słowem, a zresetujemy 2FA.",
                'body_ru' => "Чтобы сбросить двухфакторную аутентификацию, подтвердите личность секретным словом, заданным при её включении. Напишите это слово в ответ — и мы сбросим 2FA.",
            ],
            [
                'title' => 'Інтеграція API', 'category' => 'Інтеграція',
                'body'    => "Документація та приклади доступні у розділі «Інтеграція → API». Для створення рахунку використовуйте ваш API-ключ у заголовку запиту. Якщо бачите помилку — надішліть тіло запиту та відповідь сервера.",
                'body_en' => "Docs and examples are in “Integration → API”. Use your API key in the request header to create invoices. If you see an error, share the request body and the server response.",
                'body_pl' => "Dokumentacja i przykłady znajdują się w „Integracja → API”. Użyj klucza API w nagłówku żądania, aby tworzyć faktury. Jeśli widzisz błąd, prześlij treść żądania i odpowiedź serwera.",
                'body_ru' => "Документация и примеры — в разделе «Інтеграція → API». Для создания счёта используйте ваш API-ключ в заголовке запроса. Если видите ошибку — пришлите тело запроса и ответ сервера.",
            ],
            [
                'title' => 'Webhook не приходить', 'category' => 'Інтеграція',
                'body'    => "Перевірте, будь ласка: URL webhook публічно доступний (не localhost), відповідає кодом 200 і підпис перевіряється за секретом. У розділі рахунку видно історію доставок та кнопку повтору.",
                'body_en' => "Please check: the webhook URL is publicly reachable (not localhost), responds with 200, and the signature is verified with your secret. The invoice page shows delivery history and a retry button.",
                'body_pl' => "Sprawdź proszę: URL webhooka jest publicznie dostępny (nie localhost), zwraca 200, a podpis jest weryfikowany sekretem. Na stronie faktury widać historię dostaw i przycisk ponowienia.",
                'body_ru' => "Проверьте, пожалуйста: URL webhook публично доступен (не localhost), отвечает кодом 200 и подпись проверяется по секрету. На странице счёта видна история доставок и кнопка повтора.",
            ],
            [
                'title' => 'Виведення коштів', 'category' => 'Виплати',
                'body'    => "Запит на виведення обробляється після перевірки. Переконайтесь, що адреса виведення додана й вірна. Термін — зазвичай кілька годин залежно від мережі та обсягу.",
                'body_en' => "Withdrawal requests are processed after review. Make sure your payout address is added and correct. Processing usually takes a few hours depending on the network and volume.",
                'body_pl' => "Wnioski o wypłatę są przetwarzane po weryfikacji. Upewnij się, że adres wypłaty jest dodany i poprawny. Realizacja zwykle trwa kilka godzin, zależnie od sieci i wolumenu.",
                'body_ru' => "Запрос на вывод обрабатывается после проверки. Убедитесь, что адрес вывода добавлен и верен. Срок — обычно несколько часов в зависимости от сети и объёма.",
            ],
            [
                'title' => 'Комісії сервісу', 'category' => 'Загальне',
                'body'    => "Комісія сервісу вказана у налаштуваннях вашого проєкту та застосовується до кожного успішного платежу. Хто сплачує комісію (мерчант чи клієнт) — налаштовується там само.",
                'body_en' => "The service fee is shown in your project settings and applies to each successful payment. Who pays the fee (merchant or customer) is configured in the same place.",
                'body_pl' => "Opłata serwisowa jest widoczna w ustawieniach projektu i dotyczy każdej udanej płatności. To, kto płaci opłatę (sprzedawca czy klient), konfiguruje się w tym samym miejscu.",
                'body_ru' => "Комиссия сервиса указана в настройках вашего проекта и применяется к каждому успешному платежу. Кто платит комиссию (мерчант или клиент) — настраивается там же.",
            ],
            [
                'title' => 'Ескалація', 'category' => 'Загальне',
                'body'    => "Дякуємо за терпіння. Ми передали ваше питання профільній команді для детального розгляду й повернемося з відповіддю якнайшвидше.",
                'body_en' => "Thank you for your patience. We've escalated your case to the relevant team for a detailed review and will get back to you as soon as possible.",
                'body_pl' => "Dziękujemy za cierpliwość. Przekazaliśmy sprawę odpowiedniemu zespołowi do szczegółowej analizy i wrócimy z odpowiedzią jak najszybciej.",
                'body_ru' => "Спасибо за терпение. Мы передали ваш вопрос профильной команде для детального рассмотрения и вернёмся с ответом как можно скорее.",
            ],
            [
                'title' => 'Прохання оцінити', 'category' => 'Загальне',
                'body'    => "Якщо ваше питання вирішено — будемо вдячні за оцінку якості підтримки. Ваш відгук допомагає нам ставати кращими!",
                'body_en' => "If your issue is resolved, we'd appreciate a quick rating of our support. Your feedback helps us improve!",
                'body_pl' => "Jeśli Twój problem został rozwiązany, będziemy wdzięczni za ocenę wsparcia. Twoja opinia pomaga nam się rozwijać!",
                'body_ru' => "Если ваш вопрос решён — будем благодарны за оценку качества поддержки. Ваш отзыв помогает нам становиться лучше!",
            ],
        ];

        foreach ($rows as $row) {
            // Idempotent: skip titles already present.
            if (DB::table('support_templates')->where('title', $row['title'])->exists()) {
                continue;
            }
            DB::table('support_templates')->insert(array_merge($row, [
                'is_active'  => true,
                'sort'       => $sort++,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        // Seed-only; leave data intact.
    }
};
