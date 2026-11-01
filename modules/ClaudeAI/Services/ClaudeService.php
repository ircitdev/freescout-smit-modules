<?php

namespace Modules\ClaudeAI\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ClaudeService
{
    const API_URL = 'https://api.anthropic.com/v1/messages';
    const API_VERSION = '2023-06-01';

    protected $apiKey;
    protected $model;
    protected $maxTokens;
    protected $temperature;

    public function __construct()
    {
        $this->apiKey     = \Option::get('claudeai_api_key') ?: config('claudeai.api_key');
        $this->model      = \Option::get('claudeai_model') ?: config('claudeai.model');
        $this->maxTokens  = (int)(\Option::get('claudeai_max_tokens') ?: config('claudeai.max_tokens'));
        $this->temperature = (float)(\Option::get('claudeai_temperature') ?: config('claudeai.temperature'));
    }

    /**
     * Send message to Claude API with tool use.
     */
    public function chat(string $systemPrompt, array $messages, array $tools = []): array
    {
        if (empty($this->apiKey)) {
            return ['error' => 'API ключ не настроен', 'success' => false];
        }

        $payload = [
            'model'       => $this->model,
            'max_tokens'  => $this->maxTokens,
            'temperature' => $this->temperature,
            'system'      => $systemPrompt,
            'messages'    => $messages,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = ['type' => 'auto'];
        }

        $response = $this->request($payload);

        if (isset($response['error'])) {
            return ['error' => $response['error']['message'] ?? 'Claude API error', 'success' => false];
        }

        return $this->processResponse($response);
    }

    /**
     * Generate operator hint for a ticket.
     */
    public function operatorHint(string $ticketSubject, string $ticketBody, array $customerData = []): array
    {
        $system = $this->buildOperatorSystemPrompt($customerData);

        $userMessage = "Тема тикета: {$ticketSubject}\n\nСообщение клиента:\n{$ticketBody}";
        if (!empty($customerData)) {
            $userMessage .= "\n\nДанные абонента: " . json_encode($customerData, JSON_UNESCAPED_UNICODE);
        }

        return $this->chat($system, [
            ['role' => 'user', 'content' => $userMessage],
        ]);
    }

    /**
     * Generate auto-reply draft for customer.
     */
    public function autoReplyDraft(string $ticketSubject, string $ticketBody, array $customerData = [], array $history = []): array
    {
        $system = $this->buildCustomerSystemPrompt($customerData);

        $messages = [];
        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg['from_customer'] ? 'user' : 'assistant',
                'content' => $msg['body'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $ticketBody];

        return $this->chat($system, $messages, $this->getTools());
    }

    /**
     * Process tool calls in response — execute billing functions.
     */
    protected function processResponse(array $response): array
    {
        $content = $response['content'] ?? [];
        $textParts = [];
        $toolResults = [];

        foreach ($content as $block) {
            if ($block['type'] === 'text') {
                $textParts[] = $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $result = $this->executeTool($block['name'], $block['input'] ?? []);
                $toolResults[] = [
                    'tool_name' => $block['name'],
                    'result'    => $result,
                ];
            }
        }

        // If there were tool calls, send results back to Claude for final response
        if (!empty($toolResults) && isset($response['id'])) {
            return $this->continueWithToolResults($response, $toolResults);
        }

        $text = implode("\n", $textParts);
        return [
            'success'  => true,
            'response' => $text,
            'model'    => $response['model'] ?? $this->model,
            'usage'    => $response['usage'] ?? [],
            'stop_reason' => $response['stop_reason'] ?? null,
        ];
    }

    /**
     * Continue conversation after tool execution.
     */
    protected function continueWithToolResults(array $originalResponse, array $toolResults): array
    {
        // Build tool_result messages
        $content = $originalResponse['content'] ?? [];
        $toolResultMessages = [];

        $toolIndex = 0;
        foreach ($content as $block) {
            if ($block['type'] === 'tool_use' && isset($toolResults[$toolIndex])) {
                $toolResultMessages[] = [
                    'type'       => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content'    => json_encode($toolResults[$toolIndex]['result'], JSON_UNESCAPED_UNICODE),
                ];
                $toolIndex++;
            }
        }

        // Re-send to Claude with tool results
        $payload = [
            'model'       => $this->model,
            'max_tokens'  => $this->maxTokens,
            'temperature' => $this->temperature,
            'messages'    => [
                ['role' => 'assistant', 'content' => $content],
                ['role' => 'user', 'content' => $toolResultMessages],
            ],
        ];

        $response = $this->request($payload);
        if (isset($response['error'])) {
            return ['error' => $response['error']['message'] ?? 'Tool continuation error', 'success' => false];
        }

        $text = '';
        foreach (($response['content'] ?? []) as $block) {
            if ($block['type'] === 'text') {
                $text .= $block['text'];
            }
        }

        return [
            'success'  => true,
            'response' => $text,
            'model'    => $response['model'] ?? $this->model,
            'usage'    => $response['usage'] ?? [],
            'tools_used' => array_column($toolResults, 'tool_name'),
        ];
    }

    /**
     * Execute a tool call against billing API.
     */
    protected function executeTool(string $name, array $input): array
    {
        $billingUrl = \Option::get('claudeai_billing_url') ?: config('claudeai.billing_url');

        switch ($name) {
            case 'check_subscriber':
                return $this->billingApiCall($billingUrl, '/rest_api/v2/Abonents/', [
                    'qf' => $input['contract_number'] ?? $input['address'] ?? '',
                ]);

            case 'check_session':
                // RADIUS sessions
                return ['status' => 'not_implemented', 'message' => 'Проверка сессий пока не реализована'];

            case 'check_mass_outage':
                return ['active_outages' => [], 'message' => 'Массовых аварий не обнаружено'];

            case 'create_ticket':
                return ['created' => false, 'message' => 'Тикет должен создать оператор'];

            case 'activate_promise_pay':
                $contract = $input['contract_number'] ?? '';
                return $this->billingApiCall($billingUrl, "/rest_api/v2/promise_pay/{$contract}/", [], 'POST');

            default:
                return ['error' => "Unknown tool: {$name}"];
        }
    }

    /**
     * Call Smit Billing REST API.
     */
    public function billingApiCall(string $baseUrl, string $path, array $params = [], string $method = 'GET'): array
    {
        if (empty($baseUrl)) {
            return ['error' => 'Billing URL not configured'];
        }

        $url = rtrim($baseUrl, '/') . $path;
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
            }
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code >= 200 && $code < 300) {
                return json_decode($body, true) ?: ['raw' => $body];
            }
            return ['error' => "HTTP {$code}", 'body' => substr($body, 0, 500)];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get tool definitions for Claude API.
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'check_subscriber',
                'description' => 'Проверить абонента по номеру договора или адресу. Возвращает: баланс, тариф, статус, адрес.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'contract_number' => ['type' => 'string', 'description' => 'Номер договора абонента'],
                        'address' => ['type' => 'string', 'description' => 'Адрес подключения'],
                    ],
                ],
            ],
            [
                'name' => 'check_session',
                'description' => 'Проверить текущую RADIUS-сессию абонента. Возвращает: статус (active/inactive), трафик, длительность.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'contract_number' => ['type' => 'string', 'description' => 'Номер договора'],
                    ],
                    'required' => ['contract_number'],
                ],
            ],
            [
                'name' => 'check_mass_outage',
                'description' => 'Проверить массовую аварию в указанном районе.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => ['type' => 'string', 'description' => 'Населённый пункт или район'],
                    ],
                    'required' => ['location'],
                ],
            ],
            [
                'name' => 'activate_promise_pay',
                'description' => 'Активировать обещанный платёж для абонента. Только при отрицательном балансе.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'contract_number' => ['type' => 'string', 'description' => 'Номер договора'],
                    ],
                    'required' => ['contract_number'],
                ],
            ],
        ];
    }

    /**
     * Build system prompt for customer-facing chat.
     */
    public function buildCustomerSystemPrompt(array $customerData = []): string
    {
        $prompt = file_get_contents(__DIR__ . '/../Resources/prompts/customer.txt');

        if (!empty($customerData)) {
            $dataBlock = '';
            foreach ($customerData as $k => $v) {
                if (!empty($v)) {
                    $dataBlock .= "{$k}: {$v}\n";
                }
            }
            $prompt = str_replace('{CUSTOMER_DATA}', $dataBlock, $prompt);
        } else {
            $prompt = str_replace('{CUSTOMER_DATA}', 'Данные абонента не загружены — запроси номер договора.', $prompt);
        }

        // Add KB articles if enabled
        if (\Option::get('claudeai_use_kb') ?? config('claudeai.use_kb')) {
            $kb = $this->getKBContext();
            if ($kb) {
                $prompt .= "\n\n<knowledge_base>\n{$kb}\n</knowledge_base>";
            }
        }

        return $prompt;
    }

    /**
     * Build system prompt for operator assistant.
     */
    public function buildOperatorSystemPrompt(array $customerData = []): string
    {
        $prompt = file_get_contents(__DIR__ . '/../Resources/prompts/operator.txt');

        if (!empty($customerData)) {
            $dataBlock = json_encode($customerData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $prompt = str_replace('{CUSTOMER_DATA}', $dataBlock, $prompt);
        }

        return $prompt;
    }

    /**
     * Get KB articles for context.
     * Loads from claudeai_kb_text option in DB (pre-imported from Excel/HTML).
     */
    protected function getKBContext(): string
    {
        return Cache::remember('claudeai_kb_context', 3600, function () {
            $text = \Option::get('claudeai_kb_text');
            if ($text && mb_strlen($text) > 100) {
                return $text;
            }
            return '';
        });
    }

    /**
     * Test API connection.
     */
    public function testConnection(): array
    {
        return $this->chat(
            'Ответь одним словом: работает',
            [['role' => 'user', 'content' => 'Тест']],
        );
    }

    /**
     * HTTP request to Claude API.
     */
    protected function request(array $payload): array
    {
        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . self::API_VERSION,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error("[ClaudeAI] CURL error: {$error}");
            return ['error' => ['message' => "Connection error: {$error}"]];
        }

        $data = json_decode($body, true);
        if ($httpCode >= 400) {
            Log::error("[ClaudeAI] API error {$httpCode}: " . substr($body, 0, 500));
            return ['error' => ['message' => $data['error']['message'] ?? "HTTP {$httpCode}"]];
        }

        // Log usage for cost tracking
        if (isset($data['usage'])) {
            Log::info('[ClaudeAI] Usage: input=' . ($data['usage']['input_tokens'] ?? 0)
                . ' output=' . ($data['usage']['output_tokens'] ?? 0)
                . ' model=' . ($data['model'] ?? $this->model));
        }

        return $data;
    }
}
