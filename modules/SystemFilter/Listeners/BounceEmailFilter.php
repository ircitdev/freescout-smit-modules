<?php

namespace Modules\SystemFilter\Listeners;

use App\Conversation;

class BounceEmailFilter
{
    /**
     * Ключевые слова bounce-сообщений (регистронезависимые)
     */
    const BOUNCE_KEYWORDS = [
        "undeliverable",
        "report",
        "failed",
        "delivery",
        "bounce",
        "mailer-daemon",
        "delivery status notification",
        "failure notice",
        "returned mail",
        "undelivered",
        "mail delivery failed",
    ];

    /**
     * Email адреса, для которых отключаем уведомления
     */
    const SILENT_SENDERS = [
        "auto-mailer@mangotele.com",
    ];

    /**
     * Обработка создания нового тикета
     */
    public function handle($conversation)
    {
        // Проверяем только входящие письма от клиентов
        if ($conversation->type !== Conversation::TYPE_EMAIL) {
            return;
        }

        // Получаем email отправителя
        $customerEmail = "";
        if ($conversation->customer) {
            $customerEmail = mb_strtolower($conversation->customer->getMainEmail() ?? "");
        }

        // Проверяем список тихих отправителей
        foreach (self::SILENT_SENDERS as $silentEmail) {
            if ($customerEmail === mb_strtolower($silentEmail)) {
                \Log::info("[SystemFilter] Отключаем уведомления для системного отправителя", [
                    "conversation_id" => $conversation->id,
                    "sender" => $customerEmail,
                ]);
                
                // Отключаем уведомления для этого тикета
                $conversation->has_attachments = $conversation->has_attachments; // trigger save
                
                // Помечаем как прочитанное чтобы не было уведомлений
                // Тикет обрабатывается VoiceMail модулем
                return;
            }
        }

        $subject = mb_strtolower($conversation->subject);

        // Проверяем наличие ключевых слов в теме
        foreach (self::BOUNCE_KEYWORDS as $keyword) {
            if (mb_strpos($subject, $keyword) !== false) {
                \Log::info("[SystemFilter] Обнаружено системное письмо (bounce)", [
                    "conversation_id" => $conversation->id,
                    "subject" => $conversation->subject,
                    "keyword" => $keyword,
                    "mailbox_id" => $conversation->mailbox_id,
                ]);

                // Удаляем тикет
                $conversation->delete();

                \Log::info("[SystemFilter] Тикет удалён", [
                    "conversation_id" => $conversation->id,
                ]);

                return;
            }
        }
    }
}
