<?php

namespace Modules\SmitBilling\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SmitBillingController extends Controller
{
    const SMIT_BILLING_API = 'https://billing.example.com/rest_api/v2';
    const AIDA_API   = 'http://127.0.0.1:8900';

    /**
     * Проверка состояния подключения клиента через RADIUS-сессии SMIT Billing
     */
    public function ping(Request $request, $conversationId)
    {
        try {
            $conversation = \App\Conversation::findOrFail($conversationId);

            // Получаем телефон клиента через DB (Watson Rememberable bug workaround)
            $customerId = $conversation->customer_id;
            $phonesJson = \DB::table('customers')->where('id', $customerId)->value('phones');
            $phone = null;
            if ($phonesJson) {
                $phones = json_decode($phonesJson, true);
                if (!empty($phones[0]['value'])) {
                    $phone = $phones[0]['value'];
                }
            }

            if (!$phone) {
                return response()->json(['success' => false, 'error' => 'Телефон клиента не найден']);
            }

            // Ищем абонента в SMIT Billing по телефону
            $clean = preg_replace('/\D/', '', $phone);
            if (strlen($clean) == 11) {
                $clean = substr($clean, 1);
            }

            $client = new \GuzzleHttp\Client(['timeout' => 15.0]);

            // Запрашиваем диагностику через AIDA
            $resp = $client->get(self::AIDA_API . '/api/voice-billing-check?phone=' . urlencode($clean));
            $billing = json_decode($resp->getBody()->getContents(), true);

            if (!$billing || !($billing['found'] ?? false)) {
                return response()->json(['success' => false, 'error' => 'Клиент не найден в биллинге']);
            }

            $status = $billing['status'] ?? 'unknown';
            $balance = $billing['balance'] ?? 0;
            $online = ($status === 'active');

            return response()->json([
                'success' => true,
                'online'  => $online,
                'message' => $online
                    ? '✅ Статус: Активен, баланс: ' . $balance . ' ₽'
                    : '❌ Статус: Заблокирован, баланс: ' . $balance . ' ₽',
            ]);

        } catch (\Exception $e) {
            \Log::error('[SmitBilling] Ping error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Ошибка проверки: ' . $e->getMessage()]);
        }
    }

    /**
     * Проксирование запроса AI Подсказки к AIDA API (обход CSP connect-src)
     */
    public function aiAssist(Request $request)
    {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 30.0]);
            $resp = $client->post(self::AIDA_API . '/api/operator-assist', [
                'json' => $request->only(['conversation_id', 'customer_phone', 'ticket_text', 'ticket_subject']),
            ]);

            $data = json_decode($resp->getBody()->getContents(), true);
            return response()->json($data);

        } catch (\Exception $e) {
            \Log::error('[SmitBilling] AI Assist error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Ошибка AI: ' . $e->getMessage()]);
        }
    }

    /**
     * Сброс кеша биллинга для конкретного тикета
     */
    public function refresh(Request $request, $conversationId)
    {
        try {
            $conversation = \App\Conversation::findOrFail($conversationId);
            $customerId = $conversation->customer_id;

            if ($customerId) {
                $row = \DB::table('customers')->where('id', $customerId)->first(['phones', 'emails']);

                if ($row && $row->phones) {
                    $phones = json_decode($row->phones, true);
                    if (!empty($phones[0]['value'])) {
                        $clean = preg_replace('/\D/', '', $phones[0]['value']);
                        $phone10 = (strlen($clean) == 11) ? substr($clean, 1) : $clean;
                        \Cache::forget('smitbilling_phone_' . $phone10);
                    }
                }

                if ($row && $row->emails) {
                    $emails = json_decode($row->emails, true);
                    if (!empty($emails[0]['value'])) {
                        \Cache::forget('smitbilling_email_' . md5($emails[0]['value']));
                    }
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
