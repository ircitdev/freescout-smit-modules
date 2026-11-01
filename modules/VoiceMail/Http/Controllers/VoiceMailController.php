<?php

namespace Modules\VoiceMail\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Mailbox;
use GuzzleHttp\Client;

class VoiceMailController extends Controller
{
    /**
     * Display VoiceMail settings page.
     * @return Response
     */
    public function settings(Request $request)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            abort(403);
        }
        $mailbox_id = config('voicemail.voicemail_mailbox_id');
        $mailbox = Mailbox::find($mailbox_id);

        if (!$mailbox) {
            $mailbox = Mailbox::first();
        }

        // Получаем баланс SMSAero
        $smsBalance = $this->getSmsAeroBalance();

        // Получаем статус OpenAI API
        $openaiStatus = $this->getOpenAIStatus();

        return view('voicemail::settings', [
            'mailbox' => $mailbox,
            'smsBalance' => $smsBalance,
            'openaiStatus' => $openaiStatus,
        ]);
    }

    /**
     * Получить баланс SMSAero
     * @return array|null
     */
    protected function getSmsAeroBalance()
    {
        $email = env('SMSAERO_EMAIL');
        $apiKey = env('SMSAERO_API_KEY');

        if (empty($email) || empty($apiKey)) {
            return ['error' => 'Не настроены учётные данные SMSAero'];
        }

        try {
            $client = new Client();
            $response = $client->request('GET', 'https://gate.smsaero.ru/v2/balance', [
                'auth' => [$email, $apiKey],
                'timeout' => 5,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['success']) && $result['success'] === true) {
                return [
                    'balance' => $result['data']['balance'] ?? 0,
                    'success' => true
                ];
            } else {
                return ['error' => $result['message'] ?? 'Неизвестная ошибка'];
            }
        } catch (\Exception $e) {
            return ['error' => 'Ошибка запроса: ' . $e->getMessage()];
        }
    }

    /**
     * Проверить статус OpenAI API
     * @return array
     */
    protected function getOpenAIStatus()
    {
        // Получаем API ключ из константы VoiceMailProcessor или .env
        $apiKey = defined('Modules\\VoiceMail\\Listeners\\VoiceMailProcessor::OPENAI_API_KEY')
            ? \Modules\VoiceMail\Listeners\VoiceMailProcessor::OPENAI_API_KEY
            : env('OPENAI_API_KEY');

        if (empty($apiKey)) {
            return ['error' => 'API ключ не настроен'];
        }

        try {
            $client = new Client();
            $response = $client->request('GET', 'https://api.openai.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'timeout' => 5,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['data']) && is_array($result['data'])) {
                // Проверяем наличие нужных моделей
                $models = array_column($result['data'], 'id');
                $hasWhisper = in_array('whisper-1', $models);
                $hasGpt4oMini = in_array('gpt-4o-mini', $models);

                return [
                    'success' => true,
                    'models_count' => count($result['data']),
                    'has_whisper' => $hasWhisper,
                    'has_gpt4o_mini' => $hasGpt4oMini,
                    'key_preview' => 'задан',
                ];
            } else {
                return ['error' => 'Неизвестный формат ответа'];
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();

            if ($statusCode === 401) {
                return ['error' => 'Неверный API ключ'];
            } elseif ($statusCode === 429) {
                return ['error' => 'Превышен лимит запросов'];
            } else {
                return ['error' => 'Ошибка API: ' . $statusCode];
            }
        } catch (\Exception $e) {
            return ['error' => 'Ошибка запроса: ' . $e->getMessage()];
        }
    }
}
