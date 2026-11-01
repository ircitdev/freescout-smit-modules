<?php

namespace Modules\VoiceMail\Listeners;

use App\Conversation;
use App\Customer;
use App\Follower;
use App\Thread;
use App\Mailbox;
use GuzzleHttp\Client;
use Modules\CustomFields\Entities\CustomField;
use Modules\Tags\Entities\Tag;

class VoiceMailProcessor
{
    const BILLING_API_URL = 'http://legacy-billing.example.com/static/cassa_pay/phone.php'; // legacy, не используется
    const SMIT_BILLING_API = 'https://billing.example.com/rest_api/v2';
    const VOICEMAIL_MAILBOX_ID = 7;  // "Звонки"
    const SUPPORT_MAILBOX_ID = 1;     // "Поддержка клиентов"
    const CONNECTION_MAILBOX_ID = 5;  // "Подключение"
    const CONNECTION_ASSIGNEE_ID = 9;  // ответственный за подключения
    const VOICEMAIL_CHANNEL_ID = 200; // Лейбл "55-35-59"
    const DEBUG_LOG = '/tmp/voicemail_processor_debug.log';

    // OpenAI GPT для нормализации
    const OPENAI_API_KEY = '';  // задаётся в .env: OPENAI_API_KEY
    const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';

    // AmoCRM: AMOCRM_DOMAIN и AMOCRM_ACCESS_TOKEN — в .env; ниже — номера воронок и этапов вашего аккаунта
    const AMOCRM_PIPELINE_ID = 0; // Воронка B2C
    const AMOCRM_STAGE_ID = 0;  // Этап "Первичный контакт"
    const AMOCRM_RESPONSIBLE_USER_ID = 0;  // ID ответственного в AmoCRM
    const AMOCRM_TASK_TYPE_CALL = 1;  // Тип задачи "Связаться"

    // Destination phone numbers
    const PHONE_MAIN = '70000000001';      // основной номер B2C — замените на свой (основной номер; с лидами AmoCRM)
    const PHONE_SUPPORT = '70000000002';   // номер поддержки — замените на свой (основной номер; с лидами AmoCRM)
    const PHONE_B2B = '70000000003';       // B2B/корпоративные клиенты — замените на свой
    const PHONE_ROBOR = '00-00-02';        // ещё один номер (приходит в коротком формате) — замените на свой

    // AmoCRM B2B pipeline settings
    const AMOCRM_B2B_PIPELINE_ID = 0;  // Воронка "B2B/Корп"
    const AMOCRM_B2B_STAGE_ID = 0;     // Этап "Запрос"

    // SMSAero settings
    const SMSAERO_API_URL = 'https://gate.smsaero.ru/v2/sms/send';
    const SMS_ENABLED = true;  // Включить/выключить SMS уведомления

    
    /**
     * Отправка SMS уведомления клиенту через SMSAero
     */
    protected function sendSmsNotification($phone, $ticketNumber, $type = 'support')
    {
        if (!self::SMS_ENABLED) {
            $this->debug("SMS disabled, skipping");
            return false;
        }

        // Защита от дублирования SMS (SMSAero блокирует "multi send on numbers")
        $cacheKey = "sms_sent_{$phone}_{$ticketNumber}";
        if (\Cache::has($cacheKey)) {
            $this->debug("SMS already sent to this phone for this ticket", [
                "phone" => $phone,
                "ticket" => $ticketNumber
            ]);
            \Log::debug("[VoiceMail] SMS пропущено - уже отправлено", [
                "phone" => $phone,
                "ticket" => $ticketNumber
            ]);
            return true; // Считаем успешным, так как SMS уже было отправлено
        }

        $email = env("SMSAERO_EMAIL");
        $apiKey = env("SMSAERO_API_KEY");

        if (empty($email) || empty($apiKey)) {
            $this->debug("SMS credentials not configured");
            \Log::warning("[VoiceMail] SMS: credentials not configured");
            return false;
        }

        // Форматируем номер телефона (убираем +7 или 8, оставляем 10 цифр)
        $cleanPhone = preg_replace("/[^0-9]/", "", $phone);
        if (strlen($cleanPhone) == 11 && ($cleanPhone[0] == "7" || $cleanPhone[0] == "8")) {
            $cleanPhone = substr($cleanPhone, 1);
        }
        if (strlen($cleanPhone) != 10) {
            $this->debug("Invalid phone format", ["phone" => $phone, "clean" => $cleanPhone]);
            return false;
        }
        $cleanPhone = "7" . $cleanPhone;  // SMSAero требует формат 7XXXXXXXXXX

        // Текст SMS в зависимости от типа тикета
        if ($type === 'connection') {
            $message = "СМИТ. Ваша заявка на подключение №{$ticketNumber} принята. Ожидайте звонка.";
        } else {
            $message = "СМИТ. Ваше обращение №{$ticketNumber} принято. Ожидайте ответ специалиста.";
        }

        $this->debug("Sending SMS", ["phone" => $cleanPhone, "message" => $message]);

        try {
            $client = new Client();
            $response = $client->request("POST", self::SMSAERO_API_URL, [
                "auth" => [$email, $apiKey],
                "json" => [
                    "number" => $cleanPhone,
                    "text" => $message,
                    "sign" => "SMS Aero",
                    "channel" => "INFO",
                ],
                "timeout" => 10,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result["success"]) && $result["success"] === true) {
                $this->debug("SMS sent successfully", $result);
                \Log::debug("[VoiceMail] SMS отправлен", [
                    "phone" => $cleanPhone,
                    "ticket" => $ticketNumber,
                    "message_id" => $result["data"]["id"] ?? null,
                ]);
                // Кешируем на 30 минут чтобы избежать повторной отправки
                \Cache::put($cacheKey, true, 1800);
                return true;
            } else {
                $this->debug("SMS send failed", $result);
                \Log::warning("[VoiceMail] SMS не отправлен", $result);
                return false;
            }
        } catch (\Exception $e) {
            $this->debug("SMS error: " . $e->getMessage());
            \Log::error("[VoiceMail] SMS ошибка: " . $e->getMessage());
            return false;
        }
    }

    
    /**
     * Сокращает адрес для темы тикета
     * город -> г., улица -> ул., квартира -> кв.
     */
    protected function shortenAddress($address)
    {
        if (empty($address)) return 'Новое подключение';
        $short = $address;
        $short = str_replace('город ', 'г. ', $short);
        $short = str_replace('улица ', 'ул. ', $short);
        $short = str_replace('квартира ', 'кв. ', $short);
        return $short;
    }

    /**
     * Проверяет, содержит ли текст валидный адрес
     * Адрес считается валидным если содержит ключевые слова улицы/дома
     */
    protected function isValidAddress($address)
    {
        if (empty($address)) return false;
        
        $address_lower = mb_strtolower($address);
        
        // Ключевые слова, указывающие на реальный адрес
        $address_keywords = [
            'улица', 'ул.', 'ул ', 
            'проспект', 'пр.', 'пр-т',
            'переулок', 'пер.', 
            'бульвар', 'б-р',
            'шоссе', 'ш.',
            'проезд',
            'набережная', 'наб.',
            'площадь', 'пл.',
            'дом ', 'д.', 'д ',
            'квартира', 'кв.', 'кв ',
            'корпус', 'корп.', 'к.',
            'строение', 'стр.',
        ];
        
        foreach ($address_keywords as $keyword) {
            if (mb_strpos($address_lower, $keyword) !== false) {
                return true;
            }
        }
        
        // Проверяем паттерн название + число (например Посёлок 12)
        if (preg_match('/[а-яё]+\s*\d+/iu', $address)) {
            // Но исключаем мусорные фразы
            $garbage_patterns = [
                'не помню', 'не знаю', 'подробно опишите', 'слушаю', 'здравствуйте', 'алло',
                'да так', 'ну да', 'короче', 'типа'
            ];
            foreach ($garbage_patterns as $garbage) {
                if (mb_strpos($address_lower, $garbage) !== false) {
                    return false;
                }
            }
            return true;
        }
        
        return false;
    }

    
    /**
     * Обновляет тело тикета с ссылками на AmoCRM
     */
    
    /**
     * Создать задачу в AmoCRM для лида
     * @param int $lead_id ID лида
     * @param string $address Адрес подключения
     */
    protected function createAmoCRMTask($lead_id, $address)
    {
        try {
            $client = new \GuzzleHttp\Client();

            // Время выполнения - через 1 час от текущего момента
            $complete_till = time() + 3600;

            $task_data = [
                [
                    "task_type_id" => self::AMOCRM_TASK_TYPE_CALL,  // Связаться
                    "text" => "Уточнить информацию, согласовать дату",
                    "complete_till" => $complete_till,
                    "entity_id" => $lead_id,
                    "entity_type" => "leads",
                    "responsible_user_id" => self::AMOCRM_RESPONSIBLE_USER_ID,  // ответственный
                ]
            ];

            $response = $client->post("https://" . env('AMOCRM_DOMAIN', 'your-account.amocrm.ru') . "/api/v4/tasks", [
                "headers" => [
                    "Authorization" => "Bearer " . env('AMOCRM_ACCESS_TOKEN', ''),
                    "Content-Type" => "application/json"
                ],
                "json" => $task_data
            ]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $result = json_decode($response->getBody()->getContents(), true);
                $task_id = $result["_embedded"]["tasks"][0]["id"] ?? null;
                \Log::info("[VoiceMail] Задача создана в AmoCRM", [
                    "lead_id" => $lead_id,
                    "task_id" => $task_id,
                    "responsible" => "ответственный",
                    "complete_till" => date("Y-m-d H:i:s", $complete_till)
                ]);
            } else {
                \Log::error("[VoiceMail] Ошибка создания задачи", [
                    "status" => $response->getStatusCode(),
                    "lead_id" => $lead_id
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("[VoiceMail] Exception при создании задачи: " . $e->getMessage());
        }
    }

    protected function updateTicketWithAmoCRMLinks($conversation_id, $lead_url, $contact_url, $original_message)
    {
        \Log::debug("[VoiceMail DEBUG] updateTicketWithAmoCRMLinks called", [
            "conversation_id" => $conversation_id,
            "lead_url" => $lead_url,
            "contact_url" => $contact_url
        ]);
        try {
            $conversation = \App\Conversation::find($conversation_id);
            if (!$conversation) {
                $this->debug("Conversation not found for AmoCRM links update", ["id" => $conversation_id]);
                return;
            }

            // Находим первый thread
            $thread = $conversation->threads()->first();
            if (!$thread) {
                $this->debug("Thread not found for AmoCRM links update");
                return;
            }

            // Формируем новое тело с ссылками
            $new_body = "📞 Голосовое сообщение: " . $original_message . "\n\n";
            $new_body .= "---\n\n";
            $new_body .= "🔗 AmoCRM Лид: " . $lead_url . "\n";
            $new_body .= "👤 AmoCRM Контакт: " . $contact_url;

            $thread->body = $new_body;
            $thread->save();

            $this->debug("Ticket updated with AmoCRM links", [
                "conversation_id" => $conversation_id,
                "lead_url" => $lead_url,
                "contact_url" => $contact_url
            ]);
        } catch (\Exception $e) {
            \Log::warning("[VoiceMail] Failed to update ticket with AmoCRM links: " . $e->getMessage());
        }
    }

    private function debug($message, $data = []) {
        if (!env('VOICEMAIL_DEBUG', false)) {
            return;  // отладочный журнал содержит телефоны и тексты — включать только осознанно
        }
        $timestamp = date('Y-m-d H:i:s');
        $line = "[$timestamp] $message";
        if (!empty($data)) {
            $line .= ': ' . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        $line .= "\n";
        @file_put_contents(self::DEBUG_LOG, $line, FILE_APPEND);
    }

    public function handle($event)
    {
        $this->debug('=== НАЧАЛО ОБРАБОТКИ ===');

        try {
            $conversation = $event->conversation;
            $this->debug('Conversation loaded', ['id' => $conversation->id]);

            $thread = $event->threads->first() ?? $conversation->threads()->first();
            $this->debug('Thread loaded', ['exists' => !is_null($thread)]);

            // Обрабатываем только письма из mailbox "Звонки"
            if ($conversation->mailbox_id != self::VOICEMAIL_MAILBOX_ID) {
                $this->debug('SKIP: wrong mailbox', ['mailbox_id' => $conversation->mailbox_id]);
                return;
            }
            $this->debug('✓ Mailbox check passed');

            // Пропускаем уже обработанные тикеты (channel установлен)
            if (!is_null($conversation->channel)) {
                $this->debug('SKIP: already processed', ['channel' => $conversation->channel]);
                return;
            }
            $this->debug('✓ Channel check passed (not processed yet)');

            if (!$thread || $thread->type != Thread::TYPE_CUSTOMER) {
                $this->debug('SKIP: no thread or wrong type', [
                    'has_thread' => !is_null($thread),
                    'thread_type' => $thread ? $thread->type : null
                ]);
                return;
            }
            $this->debug('✓ Thread check passed');

            \Log::info('[VoiceMail] Processing conversation', [
                'conversation_id' => $conversation->id,
                'subject' => $conversation->subject,
            ]);

            // Извлекаем номер телефона
            $this->debug('Extracting phone...');
            $phone = $this->extractPhoneNumber($conversation, $thread);
            if (!$phone) {
                $this->debug('ERROR: no phone found');
                \Log::warning('[VoiceMail] Не удалось извлечь номер телефона', [
                    'conversation_id' => $conversation->id,
                ]);
                return;
            }
            $this->debug('✓ Phone extracted', ['phone' => $phone]);

            // Определяем номер назначения (куда звонили) - ПЕРЕД проверкой расшифровки
            $this->debug('Extracting destination number EARLY...');
            $destinationNumber = $this->extractDestinationNumber($conversation, $thread);
            $this->debug('✓ Destination number (early)', ['number' => $destinationNumber]);


            // Читаем расшифровку из TXT вложения
            $this->debug('Extracting transcription...');
            $transcription = $this->extractTranscriptionFromAttachment($thread);
            
            // Для номера 70000000002 без расшифровки - закрываем тикет без создания заявки
            if (!$transcription) {
                $this->debug('No transcription found');
                
                if ($destinationNumber === self::PHONE_SUPPORT) {
                    // Номер 70000000002 без расшифровки - просто закрываем voicemail
                    $this->debug('PHONE_SUPPORT without transcription - closing voicemail only');
                    \Log::info('[VoiceMail] Номер 70000000002 без расшифровки - закрываем без создания тикета', [
                        'conversation_id' => $conversation->id,
                    ]);
                    $this->closeVoicemailTicket($conversation);
                    return;
                }
                
                // Для других номеров - старое поведение (skip)
                $this->debug('ERROR: no transcription found (main phone)');
                \Log::warning('[VoiceMail] Не найдена расшифровка в вложении', [
                    'conversation_id' => $conversation->id,
                ]);
                return;
            }
            $this->debug('✓ Transcription extracted', ['length' => strlen($transcription)]);

            \Log::debug('[VoiceMail] Расшифровка извлечена', [
                'phone' => $phone,
                'transcription' => substr($transcription, 0, 100),
            ]);

            // Нормализуем текст через OpenAI GPT
            $this->debug('Calling normalizeWithGPT...');
            $normalized = $this->normalizeWithGPT($transcription);
            $this->debug('✓ Normalized', ['address' => $normalized['address'], 'problem' => substr($normalized['problem'], 0, 50)]);

            // destinationNumber уже извлечён выше (EARLY)

            // Определяем тип обращения
            $this->debug('Detecting request type...');
            $type = $this->detectRequestType($normalized['problem']);
            $this->debug('✓ Type detected', ['type' => $type]);

            \Log::debug('[VoiceMail DEBUG] Определён тип обращения', [
                'type' => $type,
                'destination' => $destinationNumber,
            ]);

            // ВАЖНО: Помечаем исходный тикет как обработанный ДО создания нового,
            // чтобы при ошибке он не обрабатывался повторно (защита от дублей)
            $this->debug('Closing voicemail ticket BEFORE processing...');
            $this->closeVoicemailTicket($conversation);
            $this->debug('✓ Voicemail ticket closed (pre-processing)');

            // Логика обработки одинаковая для всех номеров (55-35-59 и 70000000002)
            // Для 70000000002 в AmoCRM лиде будет UTM Medium = 70000000002
            if ($type === 'garbage') {
                // Мусорное сообщение - просто закрываем без создания тикетов/лидов
                $this->debug('GARBAGE message - closing without action');
                \Log::debug('[VoiceMail] Мусорное сообщение, закрываем без обработки', [
                    'phone' => $phone,
                    'text' => substr($normalized['problem'], 0, 50)
                ]);
            } elseif ($type === 'support') {
                $this->debug('Creating support ticket...');
                $this->createSupportTicket($phone, $normalized, $conversation, $destinationNumber);
                $this->debug('✓ Support ticket created');
            } else {
                // Тип connection - с лидом AmoCRM
                $this->debug('Creating connection lead...');
                $this->createConnectionLead($phone, $normalized, $conversation, $destinationNumber);
                $this->debug('✓ Connection lead created');
            }

            $this->debug('=== ОБРАБОТКА ЗАВЕРШЕНА УСПЕШНО ===');

        } catch (\Throwable $e) {
            $this->debug('FATAL ERROR', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Если тикет ещё не был помечен — помечаем, чтобы не создавать дубли
            try {
                if (is_null($conversation->channel)) {
                    $conversation->channel = self::VOICEMAIL_CHANNEL_ID;
                    $conversation->status = Conversation::STATUS_CLOSED;
                    $conversation->closed_at = now();
                    $conversation->save();
                    $this->debug('Voicemail marked as processed despite error');
                    \Log::warning('[VoiceMail] Тикет помечен как обработанный после ошибки', [
                        'conversation_id' => $conversation->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } catch (\Exception $markError) {
                \Log::error('[VoiceMail] Не удалось пометить тикет после ошибки: ' . $markError->getMessage());
            }

            throw $e;
        }
    }

    protected function extractPhoneNumber($conversation, $thread)
    {
        $text = $conversation->subject . ' ' . strip_tags($thread->body);

        // ПРИОРИТЕТ: Ищем телефон отправителя (после "от абонента с номером")
        if (preg_match('/от абонента с номером\s*(\+?[78][\s\-]?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2})/', $text, $matches)) {
            $this->debug('Phone found after "от абонента с номером"', ['raw' => $matches[1]]);
            return $this->normalizePhone($matches[1]);
        }

        // Запасной вариант - любой телефон
        $patterns = [
            '/\+7[\s\-]?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}/',
            '/8[\s\-]?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}/',
            '/\d{11}/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $this->debug('Phone found (fallback pattern)', ['raw' => $matches[0]]);
                return $this->normalizePhone($matches[0]);
            }
        }

        $this->debug('No phone found in text', ['text_length' => strlen($text)]);
        return null;
    }

    protected function normalizePhone($phone)
    {
        // Safety check: ensure $phone is a string
        if (is_array($phone)) {
            $this->debug('ERROR: normalizePhone received array', ['phone' => $phone]);
            return null;
        }
        if (!is_string($phone)) {
            $this->debug('ERROR: normalizePhone received non-string', ['type' => gettype($phone)]);
            return null;
        }

        $phone = preg_replace('/\D/', '', $phone);
        if (substr($phone, 0, 1) === '8' && strlen($phone) === 11) {
            $phone = '7' . substr($phone, 1);
        }
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }
        return $phone;
    }



    /**
     * Add star (follower) for all active users to conversation
     */
    protected function addStarToConversation($conversation_id)
    {
        $active_users = \App\User::where('status', 1)->where('id', '>', 1)->get();
        foreach ($active_users as $user) {
            // Check if not already following
            $exists = Follower::where('conversation_id', $conversation_id)
                ->where('user_id', $user->id)
                ->exists();
            if (!$exists) {
                Follower::create([
                    'conversation_id' => $conversation_id,
                    'user_id' => $user->id
                ]);
            }
        }
        $this->debug('Added star for B2B ticket', ['conversation_id' => $conversation_id]);
    }


    /**
     * Get short phone number for display
     */
    protected function getShortNumber($destinationNumber)
    {
        switch ($destinationNumber) {
            case self::PHONE_B2B:
                return '00-00-01';
            case self::PHONE_MAIN:
                return '55-35-59';
            case self::PHONE_SUPPORT:
            default:
                return $destinationNumber ?: '55-35-59';
        }
    }

    /**
     * Назначить теги тикету по номеру назначения:
     * 00-00-02 → "робор"
     * 00-00-01 → "смит" + "бизнес"
     * остальные → "смит"
     */
    protected function attachTagsByDestination($conversationId, $destinationNumber)
    {
        $shortNumber = $this->getShortNumber($destinationNumber);

        if ($shortNumber === '00-00-02' || $destinationNumber === self::PHONE_ROBOR) {
            Tag::attachByName('робор', $conversationId);
            $this->debug('Tag "робор" attached (00-00-02)', ['id' => $conversationId]);
        } elseif ($destinationNumber === self::PHONE_B2B || $shortNumber === '00-00-01') {
            Tag::attachByName('смит', $conversationId);
            Tag::attachByName('бизнес', $conversationId);
            $this->debug('Tags "смит"+"бизнес" attached (00-00-01 B2B)', ['id' => $conversationId]);
        } else {
            Tag::attachByName('смит', $conversationId);
            $this->debug('Tag "смит" attached', ['id' => $conversationId]);
        }

        // Тег с номером назначения (55-35-59, 00-00-01, 00-00-02 и т.д.)
        if ($shortNumber) {
            Tag::attachByName($shortNumber, $conversationId);
        }
    }

    /**
     * Phase 1.2: Classify problem type from text and attach tags.
     */
    protected function classifyAndTagProblemType($conversationId, $problemText)
    {
        if (empty($problemText)) {
            return;
        }

        $text = ' ' . mb_strtolower($problemText) . ' ';

        $rules = [
            'нет-интернета' => ['нет инета', 'нет интернета', 'пропал интернет', 'не работает интернет', 'интернет не работает', 'нету интернета', 'отключился интернет', 'перестал работать интернет'],
            'скорость' => ['низкая скорость', 'медленно', 'тормозит', 'скорость упала', 'маленькая скорость', 'лагает', 'медленный интернет'],
            'los' => ['горит los', 'обрыв оптики', ' los ', 'лос горит', 'красная лампочка', 'обрыв волокна'],
            'роутер' => ['настройка роутера', 'проблема с роутером', 'wi-fi', 'вай-фай', 'wifi', 'роутер не работает', 'роутер', 'маршрутизатор'],
            'оплата' => ['баланс', 'оплата', 'задолженность', 'обещанный платеж', 'обещанный платёж', 'долг', 'как оплатить'],
        ];

        foreach ($rules as $tagName => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($text, $keyword) !== false) {
                    Tag::attachByName($tagName, $conversationId);
                    $this->debug("Problem type tag '$tagName' attached", ['id' => $conversationId]);
                    break;
                }
            }
        }
    }

    /**
     * Get UTM Medium value based on destination phone number
     */
    protected function getUtmMedium($destinationNumber)
    {
        switch ($destinationNumber) {
            case self::PHONE_B2B:
                return '00-00-01';
            case self::PHONE_SUPPORT:
                return '70000000002';
            case self::PHONE_MAIN:
            default:
                return '70000000001';
        }
    }

    /**
     * Извлекает номер назначения (куда звонили) из письма
     * @return string|null Номер без + (например 70000000001 или 70000000002)
     */
    protected function extractDestinationNumber($conversation, $thread)
    {
        $text = $conversation->subject . ' ' . strip_tags($thread->body);

        // Формат 1: sip:user1@vpbx... ( 70000000002 )
        if (preg_match('/на номер sip:[^(]+\(\s*(\d{11})\s*\)/', $text, $matches)) {
            $this->debug('Destination found (SIP format)', ['number' => $matches[1]]);
            return $matches[1];
        }

        // Формат 2: на номер +70000000001
        if (preg_match('/на номер \+?(\d{11})/', $text, $matches)) {
            $this->debug('Destination found (direct format)', ['number' => $matches[1]]);
            return $matches[1];
        }

        $this->debug('Destination number not found');
        return null;
    }

    protected function extractTranscriptionFromAttachment($thread)
    {
        $attachments = $thread->attachments;
        $this->debug('Checking attachments', ['count' => count($attachments)]);

        foreach ($attachments as $attachment) {
            $this->debug('Checking attachment', [
                'filename' => $attachment->file_name,
                'mime_type' => $attachment->mime_type
            ]);

            // Ищем TXT файл с расшифровкой
            if (strpos($attachment->mime_type, 'text') !== false ||
                strpos($attachment->file_name, '.txt') !== false) {

                $storagePath = storage_path('app/attachment/' . $attachment->file_dir . $attachment->file_name);
                $this->debug('Found text attachment', ['path' => $storagePath]);

                if (file_exists($storagePath)) {
                    $content = file_get_contents($storagePath);
                    $this->debug('Read attachment content', ['length' => strlen($content)]);

                    // Извлекаем только текст расшифровки (после "следующего содержания:")
                    if (preg_match('/следующего содержания:\s*(.+)$/is', $content, $matches)) {
                        $this->debug('Extracted transcription after marker');
                        return trim($matches[1]);
                    }

                    $this->debug('Returning full content (no marker found)');
                    return trim($content);
                } else {
                    $this->debug('File not found at path', ['path' => $storagePath]);
                }
            }
        }

        $this->debug('No text attachment found');
        return null;
    }

    protected function normalizeWithGPT($text)
    {
        $this->debug('normalizeWithGPT START', ['text_length' => strlen($text)]);

        $openai_key = env('OPENAI_API_KEY', self::OPENAI_API_KEY);

        if (empty($openai_key)) {
            $this->debug('OpenAI API key not configured, using fallback');
            \Log::warning('[VoiceMail] OpenAI API key не настроен');
            return [
                'address' => $text,
                'problem' => $text,
                'original' => $text
            ];
        }

        try {
            $prompt = "Нормализуй следующий текст голосового сообщения:\n\n" .
                     "1. Преобразуй словесные числительные в цифры (например: 'двадцать один' -> '21', 'тридцать семь а' -> '37а')\n" .
                     "2. Извлеки адрес если есть\n" .
                     "3. Извлеки описание проблемы или запроса\n\n" .
                     "Текст: {$text}\n\n" .
                     "Верни результат в формате JSON:\n" .
                     "{\n" .
                     "  \"address\": \"нормализованный адрес или пустая строка\",\n" .
                     "  \"problem\": \"нормализованное описание проблемы\"\n" .
                     "}";

            $this->debug('Sending request to OpenAI...');

            // Suppress PHP 8.4 deprecation warnings in Guzzle 6.x
            $prevErrorLevel = error_reporting(E_ALL & ~E_DEPRECATED);
            $client = new Client(['timeout' => 30]);
            $response = $client->post(self::OPENAI_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $openai_key,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'model' => 'gpt-4',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Ты помощник для нормализации текстов голосовых сообщений. Отвечай только в формате JSON.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.3
                ]
            ]);

            error_reporting($prevErrorLevel); // Restore error reporting
            $this->debug('OpenAI response received', ['status' => $response->getStatusCode()]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $content = $data['choices'][0]['message']['content'] ?? '';
                $this->debug('OpenAI content', ['content' => substr($content, 0, 200)]);

                // Парсим JSON из ответа
                $normalized = json_decode($content, true);

                if ($normalized && isset($normalized['address']) && isset($normalized['problem'])) {
                    $this->debug('normalizeWithGPT SUCCESS');
                    return [
                        'address' => $normalized['address'] ?: $text,
                        'problem' => $normalized['problem'] ?: $text,
                        'original' => $text
                    ];
                } else {
                    $this->debug('normalizeWithGPT: invalid JSON structure');
                }
            } else {
                $this->debug('OpenAI request failed', [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getBody()->getContents()
                ]);
            }
        } catch (\Throwable $e) {
            if (isset($prevErrorLevel)) {
                error_reporting($prevErrorLevel);
            }
            $this->debug('normalizeWithGPT EXCEPTION', [
                'message' => $e->getMessage()
            ]);
            \Log::error('[VoiceMail] Ошибка OpenAI нормализации: ' . $e->getMessage());
        }

        $this->debug('normalizeWithGPT: using fallback');
        return [
            'address' => $text,
            'problem' => $text,
            'original' => $text
        ];
    }

    /**
     * Проверяет, является ли сообщение мусорным (не содержит полезной информации)
     */
    protected function isGarbageMessage($text)
    {
        $text_lower = mb_strtolower(trim($text));
        $text_clean = preg_replace('/[^а-яёa-z0-9\s]/u', '', $text_lower);

        // Слишком короткое сообщение
        if (mb_strlen($text_clean) < 5) {
            return true;
        }

        // Сначала проверяем есть ли полезные ключевые слова - тогда это НЕ мусор
        $useful_keywords = [
            'интернет', 'интернета', 'роутер', 'wifi', 'вайфай', 'вай-фай',
            'не работает', 'отключился', 'пропал', 'нет связи', 'нету',
            'подключ', 'тариф', 'баланс', 'долг',
            'проблема', 'помогите', 'помощь', 'настройк', 'дозвониться', 'дозвонить',
            'улица', 'ул ', 'дом ', 'квартира', 'город', 'район',
            'волгоград', 'саратов', 'краснодар'
        ];
        
        foreach ($useful_keywords as $keyword) {
            if (mb_strpos($text_lower, $keyword) !== false) {
                return false;  // Есть полезное слово - не мусор
            }
        }

        // Односложные бессмысленные ответы
        $garbage_phrases = [
            'да', 'нет', 'а', 'ага', 'угу', 'ну', 'что', 'алло', 'але',
            'спасибо', 'спаси', 'пока', 'до свидания', 'всего хорошего',
            'ок', 'окей', 'хорошо', 'ладно', 'понял', 'понятно',
            'ну да', 'да так', 'так да', 'ну конечно', 'конечно',
            'повторюсь', 'пока а а', 'а а а',
            'робот', 'абонент не отвечает', 'временно недоступ',
            'не помню', 'ничего не', 'нигде не', 'не это',
            // Медицинские/нерелевантные
            'доктор', 'врач', 'больниц', 'поликлиник', 'аптек',
            'метро', 'автобус', 'такси', 'маршрут',
            'магазин', 'ресторан', 'кафе'
        ];

        foreach ($garbage_phrases as $phrase) {
            if ($text_clean === $phrase || mb_strpos($text_clean, $phrase) !== false) {
                return true;
            }
        }

        // Слишком мало значимых слов (меньше 3)
        $words = preg_split('/\s+/', $text_clean);
        $word_count = count(array_filter($words, function($w) { return mb_strlen($w) > 2; }));

        if ($word_count < 3) {
            return true;
        }

        return false;
    }

    protected function detectRequestType($text)
    {
        $this->debug('detectRequestType START');

        $text_lower = mb_strtolower($text);

        // Сначала проверяем на мусор
        if ($this->isGarbageMessage($text)) {
            $this->debug('detectRequestType: GARBAGE MESSAGE detected');
            return 'garbage';
        }

        // Расширенный список ключевых слов для поддержки
        $support_keywords = [
            // Проблемы с интернетом
            'проблема', 'не работает', 'пропал', 'нет интернета', 'нету интернета',
            'отключился', 'перестал', 'сломал', 'ошибка',
            'медленн', 'тормоз', 'глюч', 'барахл', 'лаг',

            // Оборудование
            'роутер', 'маршрутизатор', 'модем', 'приставк',
            'wi-fi', 'wifi', 'вай-фай', 'вайфай', 'вай фай',
            'кабель', 'провод', 'разъём', 'порт',

            // Настройки
            'настройк', 'настроить', 'логин', 'пароль',
            'мак адрес', 'mac', 'ip адрес',

            // Связь/дозвон
            'не могу дозвониться', 'не дозвонить', 'не отвечает',
            'недоступ', 'занят',

            // Финансы
            'долг', 'задолженность', 'баланс', 'оплат', 'платёж',
            'минус', 'заблокир', 'блокировк',

            // Жалобы
            'жалоба', 'претензи', 'перерасчёт', 'перерасчет',
            'возврат', 'компенсац',

            // Отключение (противоположность подключению!)
            'отключиться', 'хочу отключ', 'расторг', 'расторжен',
            'прекратить', 'закрыть договор',

            // Технические проблемы
            'перезагруж', 'перезапуст', 'мигает', 'горит',
            'помогите', 'помощь', 'срочно'
        ];

        // Ключевые слова для подключения (новая заявка)
        $connection_keywords = [
            'подключ', 'хочу интернет', 'провести интернет',
            'установить интернет', 'провайдер',
            'новый абонент', 'стать клиент',
            'узнать о тариф', 'какие тариф', 'сколько стоит подключ'
        ];

        $support_score = 0;
        $connection_score = 0;

        foreach ($support_keywords as $keyword) {
            if (mb_strpos($text_lower, $keyword) !== false) {
                $support_score++;
            }
        }

        foreach ($connection_keywords as $keyword) {
            if (mb_strpos($text_lower, $keyword) !== false) {
                $connection_score++;
            }
        }

        // Если есть адрес И нет слов поддержки - скорее всего подключение
        $words = explode(' ', $text_lower);
        $has_address = (mb_strpos($text_lower, 'улица') !== false ||
                        mb_strpos($text_lower, 'ул ') !== false ||
                        mb_strpos($text_lower, 'ул.') !== false ||
                        mb_strpos($text_lower, 'проспект') !== false ||
                        mb_strpos($text_lower, 'переулок') !== false);
        $has_house = (mb_strpos($text_lower, 'дом ') !== false ||
                      mb_strpos($text_lower, 'д ') !== false ||
                      mb_strpos($text_lower, 'д.') !== false ||
                      preg_match('/дом\s*\d/u', $text_lower));

        // Бонус за адрес только если НЕТ слов поддержки
        if ($support_score == 0 && ($has_address || $has_house) && count($words) <= 20) {
            $connection_score += 3;
        }

        // Определяем результат
        if ($support_score > 0 && $support_score >= $connection_score) {
            $result = 'support';
        } elseif ($connection_score > 0) {
            $result = 'connection';
        } else {
            // Нет явных признаков - по умолчанию support (безопаснее)
            $result = 'support';
        }

        $this->debug('detectRequestType RESULT', [
            'result' => $result,
            'support_score' => $support_score,
            'connection_score' => $connection_score,
            'has_address' => $has_address,
            'has_house' => $has_house
        ]);

        return $result;
    }

    /**
     * Создаёт тикет поддержки для звонков на номер 70000000002
     * БЕЗ создания лида в AmoCRM
     */
    protected function createSupportTicketFromSupportPhone($phone, $normalized, $voicemail_conversation, $destinationNumber)
    {
        $this->debug('createSupportTicketFromSupportPhone START');
        \Log::info('[VoiceMail] Создание тикета поддержки с номера 70000000002 (без AmoCRM)');

        // Получаем данные из биллинга
        $this->debug('Getting billing data...');
        $billing_data = $this->getBillingData($phone);
        $this->debug('Billing data received', ['has_data' => !is_null($billing_data)]);

        // Создаём или находим клиента
        $this->debug('Finding/creating customer...');
        $customer = $this->findOrCreateCustomer($phone, $billing_data, '', $destinationNumber);
        $this->debug('Customer ready', ['customer_id' => $customer->id]);

        // Генерируем AI заголовок с префиксом для номера 70000000002
        $this->debug('Generating subject...');
        $ai_subject = $this->generateAISubject($normalized['problem']);
        $contract = $billing_data['contract'] ?? null;
        $subject = $ai_subject;
        $this->debug('Subject generated', ['subject' => $subject]);

        // Форматируем тело тикета с пометкой про номер
        $body = $this->formatSupportPhoneTicketBody($normalized, $phone, $billing_data, $destinationNumber);

        // Создаём тикет в mailbox "Поддержка клиентов"
        $this->debug('Creating conversation...');
        $result = Conversation::create(
            [
                'type' => Conversation::TYPE_EMAIL,
                'subject' => $subject,
                'mailbox_id' => self::SUPPORT_MAILBOX_ID,
                'status' => Conversation::STATUS_ACTIVE,
                'state' => Conversation::STATE_PUBLISHED,
                'source_type' => Conversation::SOURCE_TYPE_WEB,
                'channel' => self::VOICEMAIL_CHANNEL_ID,
            ],
            [
                [
                    'customer_id' => $customer->id,
                    'type' => Thread::TYPE_CUSTOMER,
                    'status' => Thread::STATUS_ACTIVE,
                    'state' => Thread::STATE_PUBLISHED,
                    'body' => $body,
                    'source_via' => Thread::PERSON_CUSTOMER,
                    'source_type' => Thread::SOURCE_TYPE_WEB,
                ]
            ],
            $customer
        );
        $support_conversation = $result['conversation'];
        $this->debug('Conversation created', ['id' => $support_conversation->id]);

        // Автоматическое тегирование тикета по номеру назначения
        $this->attachTagsByDestination($support_conversation->id, $destinationNumber);

        // Phase 1.2: classify problem type from subject + transcription
        $this->classifyAndTagProblemType($support_conversation->id, $subject . ' ' . ($normalized['problem'] ?? ''));

        // Сохраняем номер назначения в meta для отображения плашки
        $support_conversation->setMeta('voicemail_destination', $this->getShortNumber($destinationNumber));
        $support_conversation->save();

        // Устанавливаем custom field "Тип" в зависимости от номера назначения
        // 1 = Физ.лицо (55-35-59, 70000000002), 2 = Юр.лицо (00-00-01 B2B)
        $typeValue = ($destinationNumber === self::PHONE_B2B) ? '2' : '1';
        \Modules\CustomFields\Entities\ConversationCustomField::create([
            'conversation_id' => $support_conversation->id,
            'custom_field_id' => 23,
            'value' => $typeValue
        ]);
        $this->debug('Custom field Тип set', ['value' => $typeValue]);

        // Устанавливаем Срочность: Юр.лицо = Высокая (2), остальные = Обычная (1)
        $urgencyValue = ($typeValue === '2') ? '2' : '1';
        \Modules\CustomFields\Entities\ConversationCustomField::create([
            'conversation_id' => $support_conversation->id,
            'custom_field_id' => 24,
            'value' => $urgencyValue
        ]);
        $this->debug('Custom field Срочность set', ['value' => $urgencyValue]);

        // Для Юр.лиц (B2B) добавляем звёздочку всем активным пользователям
        if ($typeValue === '2') {
            try {
                $this->addStarToConversation($support_conversation->id);
            } catch (\Exception $e) {
                \Log::warning('[VoiceMail] Ошибка добавления звёздочки: ' . $e->getMessage());
            }
        }

        \Log::info('[VoiceMail] Тикет поддержки создан (70000000002, без AmoCRM)', [
            'conversation_id' => $support_conversation->id,
            'subject' => $subject,
            'customer_id' => $customer->id,
        ]);

        // Отправляем SMS уведомление клиенту
        $ticketNumber = $support_conversation->number ?? $support_conversation->id;
        $this->sendSmsNotification($phone, $ticketNumber);

        $this->debug('createSupportTicketFromSupportPhone END');
    }

    /**
     * Форматирует тело тикета для звонков на номер 70000000002
     */
    protected function formatSupportPhoneTicketBody($normalized, $phone, $billing_data, $destinationNumber)
    {
        $lines = [];
        $lines[] = '<p><strong>📞 Звонок на номер: ' . $destinationNumber . '</strong></p>';
        $lines[] = '<p><strong>Телефон клиента:</strong> ' . $phone . '</p>';
        
        if (!empty($billing_data)) {
            $status_icon = ($billing_data['status'] ?? 'active') === 'active' ? '✅' : '🔴';
            $status_label = ($billing_data['status'] ?? 'active') === 'active' ? 'Активен' : 'Заблокирован';
            if (!empty($billing_data['disabled_date'])) {
                $status_label .= ' с ' . $billing_data['disabled_date'];
            }
            if (!empty($billing_data['fullname'])) {
                $lines[] = '<p><strong>👤 ФИО:</strong> ' . htmlspecialchars($billing_data['fullname']) . '</p>';
            }
            if (!empty($billing_data['contract'])) {
                $lines[] = '<p><strong>📄 Договор:</strong> ' . $billing_data['contract'] . '</p>';
            }
            $lines[] = '<p><strong>⚡ Статус:</strong> ' . $status_icon . ' ' . $status_label . '</p>';
            if (!empty($billing_data['balance'])) {
                $lines[] = '<p><strong>💰 Баланс:</strong> ' . $billing_data['balance'] . ' руб.</p>';
            }
            if (!empty($billing_data['tariff'])) {
                $lines[] = '<p><strong>📦 Тариф:</strong> ' . htmlspecialchars($billing_data['tariff']) . '</p>';
            }
            if (!empty($billing_data['address'])) {
                $lines[] = '<p><strong>🏠 Адрес:</strong> ' . htmlspecialchars($billing_data['address']) . '</p>';
            }
            if (!empty($billing_data['services'])) {
                $svc_parts = [];
                foreach ($billing_data['services'] as $svc) {
                    $svc_parts[] = ($svc['enabled'] ? '✅' : '❌') . ' ' . htmlspecialchars($svc['name']);
                }
                $lines[] = '<p><strong>🔧 Услуги:</strong> ' . implode(', ', $svc_parts) . '</p>';
            }
            if (!empty($billing_data['last_payment'])) {
                $lp = $billing_data['last_payment'];
                $lp_str = $lp['amount'] . ' руб. ' . $lp['date'];
                if (!empty($lp['descr'])) {
                    $lp_str .= ' (' . htmlspecialchars($lp['descr']) . ')';
                }
                $lines[] = '<p><strong>💳 Последняя операция:</strong> ' . $lp_str . '</p>';
            }
        }

        $lines[] = '<hr>';
        $lines[] = '<p><strong>📝 Обращение:</strong></p>';
        $lines[] = '<p>' . nl2br(htmlspecialchars($normalized['problem'])) . '</p>';
        
        if (!empty($normalized['address'])) {
            $lines[] = '<p><strong>📍 Адрес из сообщения:</strong> ' . htmlspecialchars($normalized['address']) . '</p>';
        }
        
        $lines[] = '<hr>';
        $lines[] = '<p><em>Оригинал расшифровки:</em></p>';
        $lines[] = '<p><small>' . nl2br(htmlspecialchars($normalized['original'])) . '</small></p>';
        
        return implode("
", $lines);
    }


    protected function createSupportTicket($phone, $normalized, $voicemail_conversation, $destinationNumber)
    {
        $this->debug('createSupportTicket START');
        \Log::info('[VoiceMail] Создание тикета поддержки');

        // Получаем данные из биллинга
        $this->debug('Getting billing data...');
        $billing_data = $this->getBillingData($phone);
        $this->debug('Billing data received', ['has_data' => !is_null($billing_data)]);

        // Создаём или находим клиента
        $this->debug('Finding/creating customer...');
        $customer = $this->findOrCreateCustomer($phone, $billing_data, $normalized['original'], $destinationNumber);
        $this->debug('Customer ready', ['customer_id' => $customer->id]);

        // Генерируем AI заголовок с префиксом ☎ [Договор]:
        $this->debug('Generating subject...');
        $ai_subject = $this->generateAISubject($normalized['problem']);
        $contract = $billing_data['contract'] ?? null;
        $subject = $ai_subject;
        $this->debug('Subject generated', ['subject' => $subject]);

        // Форматируем тело тикета
        $body = $this->formatSupportTicketBody($normalized, $phone, $billing_data);

        // Создаём тикет в mailbox "Поддержка клиентов"
        $this->debug('Creating conversation with Conversation::create(data, threads, customer)...');
        $result = Conversation::create(
            [
                'type' => Conversation::TYPE_EMAIL,
                'subject' => $subject,
                'mailbox_id' => self::SUPPORT_MAILBOX_ID,
                'status' => Conversation::STATUS_ACTIVE,
                'state' => Conversation::STATE_PUBLISHED,
                'source_type' => Conversation::SOURCE_TYPE_WEB,
                'channel' => self::VOICEMAIL_CHANNEL_ID,
            ],
            [
                [
                    'customer_id' => $customer->id,  // Тикет от клиента
                    'type' => Thread::TYPE_CUSTOMER,  // Сообщение от клиента, не Note
                    'status' => Thread::STATUS_ACTIVE,
                    'state' => Thread::STATE_PUBLISHED,
                    'body' => $body,
                    'source_via' => Thread::PERSON_CUSTOMER,  // От клиента
                    'source_type' => Thread::SOURCE_TYPE_WEB,
                    'customer_id' => $customer->id,  // ID клиента
                ]
            ],
            $customer
        );
        $support_conversation = $result['conversation'];
        $this->debug('Conversation and thread created', ['id' => $support_conversation->id]);

        // Автоматическое тегирование тикета по номеру назначения
        $this->attachTagsByDestination($support_conversation->id, $destinationNumber);

        // Phase 1.2: classify problem type from subject + transcription
        $this->classifyAndTagProblemType($support_conversation->id, $subject . ' ' . ($normalized['problem'] ?? ''));

        // Сохраняем номер назначения в meta для отображения плашки
        $support_conversation->setMeta('voicemail_destination', $this->getShortNumber($destinationNumber));
        $support_conversation->save();

        // Устанавливаем custom field "Тип" в зависимости от номера назначения
        // 1 = Физ.лицо (55-35-59, 70000000002), 2 = Юр.лицо (00-00-01 B2B)
        $typeValue = ($destinationNumber === self::PHONE_B2B) ? '2' : '1';
        \Modules\CustomFields\Entities\ConversationCustomField::create([
            'conversation_id' => $support_conversation->id,
            'custom_field_id' => 23,
            'value' => $typeValue
        ]);
        $this->debug('Custom field Тип set', ['value' => $typeValue]);

        // Устанавливаем Срочность: Юр.лицо = Высокая (2), остальные = Обычная (1)
        $urgencyValue = ($typeValue === '2') ? '2' : '1';
        \Modules\CustomFields\Entities\ConversationCustomField::create([
            'conversation_id' => $support_conversation->id,
            'custom_field_id' => 24,
            'value' => $urgencyValue
        ]);
        $this->debug('Custom field Срочность set', ['value' => $urgencyValue]);

        // Для Юр.лиц (B2B) добавляем звёздочку всем активным пользователям
        if ($typeValue === '2') {
            try {
                $this->addStarToConversation($support_conversation->id);
            } catch (\Exception $e) {
                \Log::warning('[VoiceMail] Ошибка добавления звёздочки: ' . $e->getMessage());
            }
        }

        \Log::info('[VoiceMail] Тикет поддержки создан', [
            'conversation_id' => $support_conversation->id,
            'subject' => $subject,
            'customer_id' => $customer->id,
        ]);

        // Отправляем SMS уведомление клиенту
        $ticketNumber = $support_conversation->number ?? $support_conversation->id;
        $this->sendSmsNotification($phone, $ticketNumber);

        $this->debug('createSupportTicket END');
    }

    protected function createConnectionLead($phone, $normalized, $voicemail_conversation, $destinationNumber = null)
    {
        $this->debug('createConnectionLead START');
        \Log::debug('[VoiceMail DEBUG] Создание лида на подключение');

        // Получаем данные биллинга
        $this->debug('Getting billing data...');
        $billing_data = $this->getBillingData($phone);
        $this->debug('Billing data received', ['has_data' => !is_null($billing_data)]);

        // Создаём или находим клиента в FreeScout
        $this->debug('Finding/creating customer...');
        $customer = $this->findOrCreateCustomer($phone, $billing_data, '', $destinationNumber);
        $this->debug('Customer ready', ['customer_id' => $customer->id]);

        // СНАЧАЛА создаём тикет подключения в FreeScout чтобы получить ID
        $this->debug('Creating connection ticket FIRST...');
        $connection_conversation_id = $this->createConnectionTicket($phone, $normalized, $customer, $voicemail_conversation, $destinationNumber);
        $this->debug('Connection ticket created', ['conversation_id' => $connection_conversation_id]);
        
        $freescout_url = 'https://support.example.com';
        $helpdesk_url = $connection_conversation_id ? $freescout_url . '/conversation/' . $connection_conversation_id : '';

        // Проверяем валидность адреса перед созданием лида в AmoCRM
        if (!$this->isValidAddress($normalized['address'])) {
            $this->debug('SKIP AmoCRM: Invalid address', ['address' => $normalized['address']]);
            \Log::debug('[VoiceMail] Пропускаем создание лида в AmoCRM - невалидный адрес', [
                'address' => $normalized['address'],
                'phone' => $phone,
            ]);
            // Voicemail уже помечен как обработанный в основном flow
            return;
        }

        // Создаём контакт в AmoCRM
        $this->debug('Creating AmoCRM contact...');
        $contact_id = $this->createAmoCRMContact($phone, $billing_data, $destinationNumber);

        if (!$contact_id) {
            $this->debug('ERROR: Failed to create AmoCRM contact');
            \Log::error('[VoiceMail] Не удалось создать контакт в AmoCRM');
            return;
        }
        $this->debug('AmoCRM contact created', ['contact_id' => $contact_id]);

        // Создаём лид с custom_fields
        $this->debug('Creating AmoCRM lead with custom fields...');
        $lead_data = [
            'name' => 'Заявка ☎: ' . $this->shortenAddress($normalized['address']),
            'pipeline_id' => ($destinationNumber === self::PHONE_B2B) ? self::AMOCRM_B2B_PIPELINE_ID : self::AMOCRM_PIPELINE_ID,
            'status_id' => ($destinationNumber === self::PHONE_B2B) ? self::AMOCRM_B2B_STAGE_ID : self::AMOCRM_STAGE_ID,
            '_embedded' => [
                'contacts' => [
                    ['id' => $contact_id]
                ]
            ],
            'custom_fields_values' => [
                [
                    'field_id' => 0,  // ID поля в вашем AmoCRM  // Адрес подключения
                    'values' => [['value' => $normalized['address'] ?: $normalized['original']]]
                ],
                [
                    'field_id' => 0,  // ID поля в вашем AmoCRM  // Номер тикета FreeScout
                    'values' => [['value' => (int)$connection_conversation_id]]
                ],
                [
                    'field_id' => 0,  // ID поля в вашем AmoCRM  // UTM Medium - источник звонка
                    'values' => [['value' => $this->getUtmMedium($destinationNumber)]]
                ]
            ]
        ];

        try {
            $prevErrorLevel = error_reporting(E_ALL & ~E_DEPRECATED);
            $client = new Client();
            $response = $client->post('https://' . env('AMOCRM_DOMAIN', 'your-account.amocrm.ru') . '/api/v4/leads', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('AMOCRM_ACCESS_TOKEN', ''),
                    'Content-Type' => 'application/json'
                ],
                'json' => [$lead_data]
            ]);

            error_reporting($prevErrorLevel);
            $this->debug('AmoCRM lead response', ['status' => $response->getStatusCode()]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $lead_id = $data['_embedded']['leads'][0]['id'];
                $this->debug('AmoCRM lead created', ['lead_id' => $lead_id]);

                // Добавляем примечание
                $this->debug('Adding note to lead...');
                $note_data = [[
                    'note_type' => 'common',
                    'params' => [
                        'text' => $normalized['address'] .
                                  "\n\nТелефон: " . $phone .
                                  "\n\nОригинал: " . $normalized['original']
                    ]
                ]];

                $client->post('https://' . env('AMOCRM_DOMAIN', 'your-account.amocrm.ru') . '/api/v4/leads/' . $lead_id . '/notes', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . env('AMOCRM_ACCESS_TOKEN', ''),
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $note_data
                ]);
                $this->debug('Note added');

                // Создаём задачу для ответственный
                $this->createAmoCRMTask($lead_id, $normalized['address'] ?: $normalized['original']);
                $this->debug('Task created for owner');

                // Обновляем клиента - добавляем адрес если пустой
                $this->debug('Updating customer...');
                $lead_url = 'https://' . env('AMOCRM_DOMAIN', 'your-account.amocrm.ru') . '/leads/detail/' . $lead_id;
                if (!$customer->address || trim($customer->address) === '') {
                    $customer->address = $normalized['address'] ?: $normalized['original'];
                }
                $customer->save();
                $this->debug('Customer updated');

                // Тикет уже создан выше

                \Log::debug('[VoiceMail DEBUG] Лид создан в AmoCRM', [
                    'lead_id' => $lead_id,
                    'contact_id' => $contact_id,
                    'lead_url' => $lead_url,
                ]);

                // Обновляем тело тикета - добавляем ссылки на AmoCRM
                $contact_url = 'https://' . env('AMOCRM_DOMAIN', 'your-account.amocrm.ru') . '/contacts/detail/' . $contact_id;
                $this->updateTicketWithAmoCRMLinks($connection_conversation_id, $lead_url, $contact_url, $normalized['original']);
            } else {
                $this->debug('AmoCRM lead creation failed', [
                    'status' => $response->getStatusCode(),
                    'body' => substr($response->getBody()->getContents(), 0, 500)
                ]);
                \Log::error('[VoiceMail] Ошибка создания лида', [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getBody()->getContents()
                ]);
            }
        } catch (\Throwable $e) {
            if (isset($prevErrorLevel)) {
                error_reporting($prevErrorLevel);
            }
            $this->debug('createConnectionLead EXCEPTION', [
                'message' => $e->getMessage()
            ]);
            \Log::error('[VoiceMail] Ошибка создания лида AmoCRM: ' . $e->getMessage());
        }

        $this->debug('createConnectionLead END');
    }

    protected function createAmoCRMContact($phone, $billing_data, $destinationNumber = null)
    {
        $this->debug('createAmoCRMContact START');

        $contact_data = [[
            'name' => !empty($billing_data)
                ? trim(($billing_data['name'] ?? '') . ' ' . ($billing_data['surname'] ?? ''))
                : $phone,
            'custom_fields_values' => [
                [
                    'field_code' => 'PHONE',
                    'values' => [
                        [
                            'value' => $phone,
                            'enum_code' => 'WORK'
                        ]
                    ]
                ],
                [
                    'field_id' => 0,  // ID поля в вашем AmoCRM  // UTM Source
                    'values' => [['value' => 'call']]
                ],
                [
                    'field_id' => 0,  // ID поля в вашем AmoCRM  // UTM Medium
                    'values' => [['value' => $this->getUtmMedium($destinationNumber)]]
                ]
            ]
        ]];

        try {
            $prevErrorLevel = error_reporting(E_ALL & ~E_DEPRECATED);
            $client = new Client();
            $response = $client->post('https://' . env('AMOCRM_DOMAIN', 'your-account.amocrm.ru') . '/api/v4/contacts', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('AMOCRM_ACCESS_TOKEN', ''),
                    'Content-Type' => 'application/json'
                ],
                'json' => $contact_data
            ]);

            error_reporting($prevErrorLevel);
            $this->debug('AmoCRM contact response', ['status' => $response->getStatusCode()]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $contact_id = $data['_embedded']['contacts'][0]['id'];
                $this->debug('createAmoCRMContact SUCCESS', ['contact_id' => $contact_id]);
                return $contact_id;
            } else {
                $this->debug('AmoCRM contact creation failed', [
                    'status' => $response->getStatusCode(),
                    'body' => substr($response->getBody()->getContents(), 0, 500)
                ]);
                \Log::error('[VoiceMail] Ошибка создания контакта', [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getBody()->getContents()
                ]);
            }
        } catch (\Throwable $e) {
            if (isset($prevErrorLevel)) {
                error_reporting($prevErrorLevel);
            }
            $this->debug('createAmoCRMContact EXCEPTION', [
                'message' => $e->getMessage()
            ]);
            \Log::error('[VoiceMail] Ошибка создания контакта AmoCRM: ' . $e->getMessage());
        }

        return null;
    }

    protected function getBillingData($phone)
    {
        $this->debug('getBillingData START', ['phone' => $phone]);

        // SMIT Billing API: sms__contains с 10 цифрами (без +7)
        $normalized = preg_replace('/\D/', '', $phone);
        if (strlen($normalized) === 12 && substr($normalized, 0, 1) === '7') {
            $phone_10 = substr($normalized, 2);
        } elseif (strlen($normalized) === 11 && substr($normalized, 0, 1) === '7') {
            $phone_10 = substr($normalized, 1);
        } else {
            $phone_10 = $normalized;
        }

        $post_headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . env('AIDA_API_KEY', ''),
        ];

        try {
            $prevErrorLevel = error_reporting(E_ALL & ~E_DEPRECATED);
            $httpClient = new Client(['timeout' => 10]);

            // 1. Абонент: ФИО, договор, тариф, статус, адрес
            $abon_payload = 'method1=objects.filter'
                . '&arg1={"sms__contains":"' . $phone_10 . '"}'
                . '&fields=["name","contract_number","tarif","account_id","sms","email",'
                . '"enabled","disabled_date","home","a_home_number","home_entrance"]';

            $response = $httpClient->post(self::SMIT_BILLING_API . '/Abonents/', [
                'body'    => $abon_payload,
                'headers' => $post_headers,
            ]);

            error_reporting($prevErrorLevel);
            $this->debug('SMIT Billing Abonents response', ['status' => $response->getStatusCode()]);

            if ($response->getStatusCode() != 200) {
                $this->debug('getBillingData: bad status');
                return null;
            }

            $data    = json_decode($response->getBody()->getContents(), true);
            $results = $data['result'] ?? [];

            if (empty($results) || !is_array($results)) {
                $this->debug('getBillingData: not found');
                return null;
            }

            $abon_pk    = $results[0]['pk'];
            $abon       = $results[0]['fields'];
            $fullname   = $abon['name'] ?? '';
            $contract   = $abon['contract_number'] ?? null;
            $account_id = $abon['account_id'] ?? null;
            $tariff_obj = $abon['tarif'] ?? [];
            $tariff     = $tariff_obj['fields']['name'] ?? null;
            $enabled    = $abon['enabled'] ?? true;
            $disabled_date = $abon['disabled_date'] ?? null;

            // Адрес из home + квартира
            $home_f  = $abon['home']['fields'] ?? [];
            $address_parts = array_filter([
                $home_f['city']     ?? '',
                $home_f['street']   ?? '',
                $home_f['s_number'] ?? '',
            ]);
            $address = implode(', ', $address_parts);
            $apt = $abon['a_home_number'] ?? ($abon['home_entrance'] ?? null);
            if ($apt) {
                $address .= ' кв.' . $apt;
            }

            // 2. Баланс из AdminAccounts
            $balance = '0.00';
            if ($account_id) {
                try {
                    $bal_payload = 'method1=objects.get'
                        . '&arg1={"id":"' . $account_id . '"}'
                        . '&fields=["balance","limit","limit_warning","credit"]';
                    $bal_resp = $httpClient->post(self::SMIT_BILLING_API . '/AdminAccounts/', [
                        'body'    => $bal_payload,
                        'headers' => $post_headers,
                    ]);
                    $bal_data = json_decode($bal_resp->getBody()->getContents(), true);
                    $balance  = $bal_data['result']['fields']['balance'] ?? '0.00';
                } catch (\Throwable $be) {
                    $this->debug('getBillingData: balance fetch failed', ['msg' => $be->getMessage()]);
                }
            }

            // 3. Подключённые услуги
            $services = [];
            try {
                $svc_payload = 'method1=objects.filter'
                    . '&arg1={"abonent_id":"' . $abon_pk . '"}'
                    . '&fields=["usluga","enabled","next_date"]';
                $svc_resp = $httpClient->post(self::SMIT_BILLING_API . '/UsersUsluga/', [
                    'body'    => $svc_payload,
                    'headers' => $post_headers,
                ]);
                $svc_data = json_decode($svc_resp->getBody()->getContents(), true);
                foreach (($svc_data['result'] ?? []) as $item) {
                    $f        = $item['fields'] ?? [];
                    $svc_name = $f['usluga']['fields']['name'] ?? '';
                    $svc_on   = $f['enabled'] ?? false;
                    if ($svc_name) {
                        $services[] = ['name' => $svc_name, 'enabled' => $svc_on];
                    }
                }
            } catch (\Throwable $se) {
                $this->debug('getBillingData: services fetch failed', ['msg' => $se->getMessage()]);
            }

            // 4. Последняя финансовая операция
            $last_payment = null;
            try {
                $fin_payload = 'method1=objects.filter'
                    . '&arg1={"abonent_id":"' . $abon_pk . '"}'
                    . '&fields=["op_summa","op_date","descr","op_type_id"]'
                    . '&order_by=-op_date&limit=1';
                $fin_resp = $httpClient->post(self::SMIT_BILLING_API . '/FinanceOperations/', [
                    'body'    => $fin_payload,
                    'headers' => $post_headers,
                ]);
                $fin_data = json_decode($fin_resp->getBody()->getContents(), true);
                $fin_list = $fin_data['result'] ?? [];
                if (!empty($fin_list)) {
                    $lf = $fin_list[0]['fields'];
                    $last_payment = [
                        'amount' => $lf['op_summa'] ?? '',
                        'date'   => substr($lf['op_date'] ?? '', 0, 10),
                        'descr'  => $lf['descr'] ?? '',
                    ];
                }
            } catch (\Throwable $fe) {
                $this->debug('getBillingData: finance fetch failed', ['msg' => $fe->getMessage()]);
            }

            $name_parts = explode(' ', $fullname, 3);
            $result = [
                'name'         => $name_parts[0] ?? '',   // Фамилия
                'surname'      => $name_parts[1] ?? '',   // Имя
                'fullname'     => $fullname,
                'contract'     => $contract,
                'balance'      => number_format((float)$balance, 2, '.', ''),
                'tariff'       => $tariff,
                'address'      => $address,
                'status'       => $enabled ? 'active' : 'blocked',
                'disabled_date' => $disabled_date ? substr($disabled_date, 0, 10) : null,
                'services'     => $services,
                'last_payment' => $last_payment,
            ];

            $this->debug('getBillingData SUCCESS', [
                'contract' => $contract,
                'balance'  => $balance,
                'status'   => $result['status'],
                'services' => count($services),
            ]);
            return $result;

        } catch (\Throwable $e) {
            if (isset($prevErrorLevel)) {
                error_reporting($prevErrorLevel);
            }
            $this->debug('getBillingData EXCEPTION', ['message' => $e->getMessage()]);
            \Log::error('[VoiceMail] Ошибка биллинга SMIT: ' . $e->getMessage());
        }

        $this->debug('getBillingData: no data');
        return null;
    }

    protected function findOrCreateCustomer($phone, $billing_data, $transcription = '', $destinationNumber = null)
    {
        $this->debug('findOrCreateCustomer START', ['phone' => $phone]);

        // Генерируем email для поиска
        $email = str_replace('+', '', $phone) . '@voicemail.example.com';
        $this->debug('findOrCreateCustomer: checking email', ['email' => $email]);

        // Сначала ищем по email (быстрее и надёжнее)
        $existingEmail = \App\Email::where('email', $email)->first();
        if ($existingEmail && $existingEmail->customer_id) {
            $customer = Customer::find($existingEmail->customer_id);
            if ($customer) {
                $this->debug('findOrCreateCustomer: found by email', ['customer_id' => $customer->id]);
                return $customer;
            }
        }

        // Если не нашли по email, ищем по телефону
        $customers = Customer::all();
        foreach ($customers as $customer) {
            $customer_phones = $customer->getPhones();
            foreach ($customer_phones as $customer_phone) {
                $phone_value = is_array($customer_phone) ? ($customer_phone['value'] ?? '') : $customer_phone;
                if ($this->normalizePhone($phone_value) === $phone) {
                    $this->debug('findOrCreateCustomer: found by phone', ['customer_id' => $customer->id]);
                    return $customer;
                }
            }
        }

        $this->debug('findOrCreateCustomer: creating new customer');

        // Создаём нового клиента
        $customer = new Customer();

        if (!empty($billing_data)) {
            $customer->first_name = $billing_data['name'] ?? '';
            $customer->last_name = $billing_data['surname'] ?? '';
            $customer->address = $billing_data['address'] ?? '';
            $customer->country = 'Russia';
        } else {
            $customer->first_name = $phone;
            $customer->last_name = '';
            $customer->address = '';  // Адрес заполняется из биллинга или вручную
        }

        $customer->save();

        $customer->addPhone($phone);

        // Проверяем, не существует ли уже email
        $emailExists = \App\Email::where('email', $email)->exists();
        if (!$emailExists) {
            $customer->addEmail($email);
        }
        $customer->save();

        // Заполняем поле "Чей": PHONE_ROBOR (00-00-02) → РОБОР (2), остальные → СМИТ (1)
        try {
            $cheyValue = ($destinationNumber === self::PHONE_ROBOR) ? '2' : '1';
            \DB::table('customer_customer_field')->insert([
                'customer_id' => $customer->id,
                'customer_field_id' => 2,
                'value' => $cheyValue,
            ]);
            $this->debug('Customer field "Чей" set', ['value' => $cheyValue]);
        } catch (\Exception $e) {
            $this->debug('Failed to set Чей field: ' . $e->getMessage());
        }

        $this->debug('findOrCreateCustomer: new customer created', ['customer_id' => $customer->id]);
        return $customer;
    }
    
    /**
     * Извлечь краткую проблему из нормализованного текста
     */
    protected 
    // Helper function for multibyte ucfirst
    function mb_ucfirst($str) {
        return mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);
    }
    function extractShortProblem($problem)
    {
        $problem = trim($problem);
        $keywords = [
            'нет интернета' => 'Нет интернета',
            'не работает интернет' => 'Не работает интернет',
            'медленн' => 'Медленный интернет',
            'скорость' => 'Проблема со скоростью',
            'роутер' => 'Проблема с роутером',
            'пропал' => 'Пропал интернет',
            'отключ' => 'Отключился интернет',
        ];

        $problem_lower = mb_strtolower($problem);
        foreach ($keywords as $key => $value) {
            if (mb_strpos($problem_lower, $key) !== false) {
                return $value;
            }
        }

        // Берём последнюю часть после адреса
        if (preg_match('/[.,]\s*([^.,]+)$/u', $problem, $matches)) {
            $short = trim($matches[1]);
            if (mb_strlen($short) >= 3) {
                return mb_ucfirst($short);
            }
        }

        return $problem;
    }

    protected function generateAISubject($problem)
    {
        // Извлекаем краткую суть проблемы (последняя значимая часть)
        $problem = trim($problem);

        // Ищем ключевые фразы проблемы
        $keywords = [
            'нет интернета' => 'Нет интернета',
            'не работает интернет' => 'Не работает интернет',
            'медленн' => 'Медленный интернет',
            'скорость' => 'Проблема со скоростью',
            'роутер' => 'Проблема с роутером',
            'wi-fi' => 'Проблема с Wi-Fi',
            'wifi' => 'Проблема с Wi-Fi',
            'вай-фай' => 'Проблема с Wi-Fi',
            'вайфай' => 'Проблема с Wi-Fi',
            'пропал' => 'Пропал интернет',
            'отключ' => 'Отключился интернет',
            'не подключ' => 'Не подключается',
            'настро' => 'Помощь с настройкой',
            'оплат' => 'Вопрос по оплате',
            'баланс' => 'Вопрос по балансу',
            'тариф' => 'Вопрос по тарифу',
        ];

        $problem_lower = mb_strtolower($problem);
        foreach ($keywords as $key => $value) {
            if (mb_strpos($problem_lower, $key) !== false) {
                return $value;
            }
        }

        // Если ключевые слова не найдены, берём последние слова после адреса
        // Пытаемся найти проблему после точки или запятой
        if (preg_match('/[.,]\s*([^.,]+)$/u', $problem, $matches)) {
            $short = trim($matches[1]);
            if (mb_strlen($short) >= 5 && mb_strlen($short) <= 50) {
                return mb_ucfirst($short);
            }
        }

        // Fallback - первые 50 символов
        return mb_substr($problem, 0, 50) ?: 'Обращение клиента';
    }

    protected function formatSupportTicketBody($normalized, $phone, $billing_data)
    {
        // Первая строка: нормализованный адрес + проблема
        $address = $billing_data['address'] ?? $normalized['address'] ?? '';
        $problem = $this->extractShortProblem($normalized['problem']);

        $body = "";
        if ($address) {
            $body .= $address . ". " . $problem . "\n\n";
        } else {
            $body .= $problem . "\n\n";
        }

        // Расшифровка звонка
        $body .= "☎ Расшифровка звонка: " . $normalized['original'] . "\n\n";

        // Телефон
        $body .= "📞 Телефон: " . $phone . "\n\n";

        // Разделитель и данные клиента
        $body .= "---\n";

        if (!empty($billing_data)) {
            $name = trim(($billing_data['fullname'] ?? '') ?: (($billing_data['name'] ?? '') . ' ' . ($billing_data['surname'] ?? '')));
            $status_icon  = ($billing_data['status'] ?? 'active') === 'active' ? '✅' : '🔴';
            $status_label = ($billing_data['status'] ?? 'active') === 'active' ? 'Активен' : 'Заблокирован';
            if (!empty($billing_data['disabled_date'])) {
                $status_label .= ' с ' . $billing_data['disabled_date'];
            }
            $body .= "👤 ФИО: " . ($name ?: 'Н/Д') . "\n";
            $body .= "📄 Договор: " . ($billing_data['contract'] ?? 'Н/Д') . "\n";
            $body .= "⚡ Статус: " . $status_icon . " " . $status_label . "\n";
            $body .= "💰 Баланс: " . ($billing_data['balance'] ?? '0.00') . " руб.\n";
            $body .= "📦 Тариф: " . ($billing_data['tariff'] ?? 'Н/Д') . "\n";
            $body .= "🏠 Адрес: " . ($billing_data['address'] ?: 'Н/Д') . "\n";
            if (!empty($billing_data['services'])) {
                $svc_parts = [];
                foreach ($billing_data['services'] as $svc) {
                    $svc_parts[] = ($svc['enabled'] ? '✅' : '❌') . ' ' . $svc['name'];
                }
                $body .= "🔧 Услуги: " . implode(', ', $svc_parts) . "\n";
            }
            if (!empty($billing_data['last_payment'])) {
                $lp = $billing_data['last_payment'];
                $lp_str = $lp['amount'] . ' руб. ' . $lp['date'];
                if (!empty($lp['descr'])) {
                    $lp_str .= ' (' . $lp['descr'] . ')';
                }
                $body .= "💳 Последняя операция: " . $lp_str . "\n";
            }
        } else {
            $body .= "Клиент не найден в биллинге\n";
        }

        return $body;
    }

    protected function createConnectionTicket($phone, $normalized, $customer, $voicemail_conversation, $destinationNumber = null)
    {
        $this->debug('createConnectionTicket START');
        \Log::info('[VoiceMail] Создание тикета подключения в mailbox/5');

        // Генерируем заголовок тикета
        $subject = $this->shortenAddress($normalized['address']);

        // Форматируем тело тикета
        // Упрощённое тело - только голосовое сообщение
        $body = "📞 Голосовое сообщение: " . $normalized['original'];

        // Создаём тикет в mailbox "Подключение"
        $this->debug('Creating connection conversation with Conversation::create(data, threads, customer)...');
        $result = Conversation::create(
            [
                'type' => Conversation::TYPE_EMAIL,
                'subject' => $subject,
                'mailbox_id' => self::CONNECTION_MAILBOX_ID,
                'status' => Conversation::STATUS_ACTIVE,
                'state' => Conversation::STATE_PUBLISHED,
                'source_type' => Conversation::SOURCE_TYPE_WEB,
                'channel' => self::VOICEMAIL_CHANNEL_ID,
            ],
            [
                [
                    'customer_id' => $customer->id,  // Тикет от клиента
                    'type' => Thread::TYPE_CUSTOMER,  // Сообщение от клиента, не Note
                    'status' => Thread::STATUS_ACTIVE,
                    'state' => Thread::STATE_PUBLISHED,
                    'body' => $body,
                    'source_via' => Thread::PERSON_CUSTOMER,  // От клиента
                    'source_type' => Thread::SOURCE_TYPE_WEB,
                    'customer_id' => $customer->id,  // ID клиента
                ]
            ],
            $customer
        );
        $connection_conversation = $result['conversation'];
        $this->debug('Connection conversation and thread created', ['id' => $connection_conversation->id]);

        // Автоматическое тегирование тикета по номеру назначения
        $this->attachTagsByDestination($connection_conversation->id, $destinationNumber);

        \Log::info('[VoiceMail] Тикет подключения создан', [
            'conversation_id' => $connection_conversation->id,
            'subject' => $subject,
            'customer_id' => $customer->id,
        ]);

        // Устанавливаем meta для плашки с номером телефона
        $connection_conversation->setMeta('voicemail_destination', $this->getShortNumber($destinationNumber));
        $connection_conversation->save();
        // Назначаем ответственного — ответственный за подключения
        $connection_conversation->user_id = self::CONNECTION_ASSIGNEE_ID;
        $connection_conversation->save();
        \Log::info("[VoiceMail] Тикет подключения назначен на ответственный за подключения", ["id" => $connection_conversation->id]);

        // Заполняем custom fields
        try {
            // Адрес подключения (custom_field_id = 17)
            $address = $normalized['address'] ?: $normalized['original'];
            CustomField::setValue($connection_conversation->id, 17, $address);

            // Телефон (custom_field_id = 19)
            CustomField::setValue($connection_conversation->id, 19, $phone);

            $this->debug('Custom fields saved', ['address' => $address, 'phone' => $phone]);
        } catch (\Exception $e) {
            \Log::warning('[VoiceMail] Failed to save custom fields: ' . $e->getMessage());
        }

        // Отправляем SMS уведомление клиенту
        $ticketNumber = $connection_conversation->number ?? $connection_conversation->id;
        $this->sendSmsNotification($phone, $ticketNumber, 'connection');

        $this->debug('createConnectionTicket END');

        return $connection_conversation->id;
    }

    protected function closeVoicemailTicket($conversation)
    {
        $this->debug('closeVoicemailTicket START', ['conversation_id' => $conversation->id]);

        $conversation->status = Conversation::STATUS_CLOSED;
        $conversation->closed_by_user_id = 1; // System
        $conversation->closed_at = now();
        $conversation->channel = self::VOICEMAIL_CHANNEL_ID;  // Set channel
        $conversation->save();

        $this->debug('closeVoicemailTicket: conversation saved');

        \Log::info('[VoiceMail] Исходный тикет закрыт', [
            'conversation_id' => $conversation->id,
        ]);

        $this->debug('closeVoicemailTicket END');
    }
}
