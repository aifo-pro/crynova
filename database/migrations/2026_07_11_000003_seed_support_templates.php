<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only seed once, and never overwrite agent-authored content.
        if (DB::table('support_templates')->exists()) {
            return;
        }

        $now = now();
        $rows = [
            [
                'title'    => 'Привітання',
                'category' => 'Загальне',
                'body'     => "Вітаємо! Дякуємо за звернення. Ми вже вивчаємо ваше питання і скоро повернемося з відповіддю.",
                'body_en'  => "Hello! Thank you for reaching out. We're looking into your request and will get back to you shortly.",
                'body_pl'  => "Dzień dobry! Dziękujemy za kontakt. Analizujemy Twoje zgłoszenie i wkrótce się odezwiemy.",
                'body_ru'  => "Здравствуйте! Спасибо за обращение. Мы уже изучаем ваш вопрос и скоро вернёмся с ответом.",
            ],
            [
                'title'    => 'Затримка платежу',
                'category' => 'Платежі',
                'body'     => "Платіж підтверджується мережею блокчейн і може зайняти від кількох хвилин до 1 години. Щойно транзакція отримає потрібну кількість підтверджень, рахунок оновиться автоматично.",
                'body_en'  => "The payment is being confirmed by the blockchain network and may take from a few minutes up to 1 hour. Once the transaction receives the required confirmations, the invoice will update automatically.",
                'body_pl'  => "Płatność jest potwierdzana przez sieć blockchain i może potrwać od kilku minut do 1 godziny. Po uzyskaniu wymaganej liczby potwierdzeń faktura zaktualizuje się automatycznie.",
                'body_ru'  => "Платёж подтверждается сетью блокчейн и может занять от нескольких минут до 1 часа. Как только транзакция получит нужное количество подтверждений, счёт обновится автоматически.",
            ],
            [
                'title'    => 'Запит деталей',
                'category' => 'Загальне',
                'body'     => "Щоб швидше допомогти, надішліть, будь ласка, ідентифікатор рахунку (UUID) або хеш транзакції та скріншот проблеми.",
                'body_en'  => "To help you faster, please share the invoice ID (UUID) or transaction hash, along with a screenshot of the issue.",
                'body_pl'  => "Aby szybciej pomóc, prześlij proszę identyfikator faktury (UUID) lub hash transakcji oraz zrzut ekranu problemu.",
                'body_ru'  => "Чтобы помочь быстрее, пришлите, пожалуйста, идентификатор счёта (UUID) или хеш транзакции и скриншот проблемы.",
            ],
            [
                'title'    => 'Закриття тікета',
                'category' => 'Загальне',
                'body'     => "Раді, що змогли допомогти! Закриваємо звернення. Якщо виникнуть додаткові питання — просто відкрийте його знову.",
                'body_en'  => "Glad we could help! We're closing this ticket. If anything else comes up, just reopen it.",
                'body_pl'  => "Cieszymy się, że mogliśmy pomóc! Zamykamy zgłoszenie. W razie dodatkowych pytań po prostu otwórz je ponownie.",
                'body_ru'  => "Рады, что смогли помочь! Закрываем обращение. Если появятся вопросы — просто откройте его снова.",
            ],
        ];

        foreach ($rows as $i => $row) {
            DB::table('support_templates')->insert(array_merge($row, [
                'is_active'  => true,
                'sort'       => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        // Leave data intact on rollback of this seed-only migration.
    }
};
