<?php

namespace Modules\VoiceMail\Console\Commands;

use Illuminate\Console\Command;
use App\Conversation;
use Modules\VoiceMail\Listeners\VoiceMailProcessor;

class ProcessVoicemail extends Command
{
    protected $signature = 'voicemail:process';
    protected $description = 'Обработка голосовых сообщений из mailbox/7';

    const VOICEMAIL_MAILBOX_ID = 7;

    public function handle()
    {
        $this->info('[VoiceMail] Начало обработки...');

        // Находим все необработанные тикеты из mailbox/7
        // Необработанные = channel NULL (независимо от status — mailbox может ставить closed)
        $conversations = Conversation::where('mailbox_id', self::VOICEMAIL_MAILBOX_ID)
            ->whereNull('channel')
            ->orderBy('id', 'asc')
            ->get();

        if ($conversations->isEmpty()) {
            $this->info('[VoiceMail] Нет необработанных тикетов');
            return 0;
        }

        $this->info(sprintf('[VoiceMail] Найдено тикетов: %d', $conversations->count()));

        $processed = 0;
        $errors = 0;

        foreach ($conversations as $conversation) {
            try {
                $this->info(sprintf('[VoiceMail] Обработка тикета #%d (status=%d)...', $conversation->id, $conversation->status));

                $threads = $conversation->threads;
                
                // Создаем event объект
                $event = new \stdClass();
                $event->conversation = $conversation;
                $event->threads = $threads;

                // Запускаем processor
                $processor = new VoiceMailProcessor();
                $processor->handle($event);

                $processed++;
                $this->info(sprintf('[VoiceMail] ✅ Тикет #%d обработан', $conversation->id));

            } catch (\Exception $e) {
                $errors++;
                // Mark as processed to prevent infinite retry loop
                try { $conversation->channel = 200; $conversation->save(); } catch (\Exception $e2) {}
                $this->error(sprintf('[VoiceMail] ❌ Ошибка обработки тикета #%d: %s', 
                    $conversation->id, 
                    $e->getMessage()
                ));
                \Log::error('[VoiceMail] Ошибка обработки тикета', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->info(sprintf('[VoiceMail] Завершено. Обработано: %d, Ошибок: %d', $processed, $errors));

        return 0;
    }
}
