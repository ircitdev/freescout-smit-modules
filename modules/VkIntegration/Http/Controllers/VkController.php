<?php

namespace Modules\VkIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\VkIntegration\Entities\VkChannel;

class VkController extends Controller
{
    /**
     * Settings page
     */
    public function settings(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->saveSettings($request);
        }

        $settings = [
            'vk_enabled' => \Option::get('vkintegration.enabled', false),
            'vk_bridge_url' => \Option::get('vkintegration.bridge_url', 'http://localhost:8901'),
            'vk_default_mailbox' => \Option::get('vkintegration.default_mailbox', '1'),
            'vk_access_token' => \Option::get('vkintegration.access_token', ''),
            'vk_group_id' => \Option::get('vkintegration.group_id', ''),
            'vk_secret_key' => \Option::get('vkintegration.secret_key', ''),
            'vk_confirmation_code' => \Option::get('vkintegration.confirmation_code', ''),
            'freescout_api_key' => \Option::get('vkintegration.freescout_api_key', ''),
            'billing_enabled' => \Option::get('vkintegration.billing_enabled', false),
            'billing_url' => \Option::get('vkintegration.billing_url', 'http://legacy-billing.example.com/static/cassa_pay'),
        ];

        $mailboxes = \App\Mailbox::all();
        $bridgeStatus = $this->checkBridgeStatus();
        $stats = $this->getStats();

        return view('vkintegration::settings', compact('settings', 'mailboxes', 'bridgeStatus', 'stats'));
    }

    /**
     * Save settings
     */
    private function saveSettings(Request $request)
    {
        $request->validate([
            'vk_bridge_url' => 'required|url',
            'vk_default_mailbox' => 'required|exists:mailboxes,id',
        ]);

        // Basic settings
        \Option::set('vkintegration.enabled', $request->has('vk_enabled') ? 1 : 0);
        \Option::set('vkintegration.bridge_url', $request->vk_bridge_url);
        \Option::set('vkintegration.default_mailbox', $request->vk_default_mailbox);

        // VK API settings
        if ($request->filled('vk_access_token')) {
            \Option::set('vkintegration.access_token', $request->vk_access_token);
        }
        if ($request->filled('vk_group_id')) {
            \Option::set('vkintegration.group_id', $request->vk_group_id);
        }
        if ($request->filled('vk_secret_key')) {
            \Option::set('vkintegration.secret_key', $request->vk_secret_key);
        }
        if ($request->filled('vk_confirmation_code')) {
            \Option::set('vkintegration.confirmation_code', $request->vk_confirmation_code);
        }
        if ($request->filled('freescout_api_key')) {
            \Option::set('vkintegration.freescout_api_key', $request->freescout_api_key);
        }

        // Billing settings
        \Option::set('vkintegration.billing_enabled', $request->has('billing_enabled') ? 1 : 0);
        if ($request->filled('billing_url')) {
            \Option::set('vkintegration.billing_url', $request->billing_url);
        }

        // Update bridge .env if needed
        $this->updateBridgeEnv();

        \Session::flash('flash_success_floating', __('Settings saved'));
        return redirect()->route('vkintegration.settings');
    }

    /**
     * Update bridge .env file with settings from FreeScout
     */
    private function updateBridgeEnv()
    {
        $envFile = '/var/www/vk-freescout/.env';
        if (!file_exists($envFile)) {
            return;
        }

        $settings = [
            'VK_TOKEN' => \Option::get('vkintegration.access_token', ''),
            'VK_GROUP_ID' => \Option::get('vkintegration.group_id', ''),
            'VK_SECRET' => \Option::get('vkintegration.secret_key', ''),
            'VK_CONFIRMATION_CODE' => \Option::get('vkintegration.confirmation_code', ''),
            'FREESCOUT_API_KEY' => \Option::get('vkintegration.freescout_api_key', ''),
            'AIDA_BILLING_ENABLED' => \Option::get('vkintegration.billing_enabled', false) ? 'true' : 'false',
            'AIDA_BILLING_URL' => \Option::get('vkintegration.billing_url', ''),
        ];

        $content = file_get_contents($envFile);
        foreach ($settings as $key => $value) {
            if (empty($value)) continue;
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }
        file_put_contents($envFile, $content);
    }

    /**
     * Check bridge status
     */
    private function checkBridgeStatus()
    {
        $bridgeUrl = \Option::get('vkintegration.bridge_url', 'http://localhost:8901');

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 5]);
            $response = $client->get($bridgeUrl . '/status');
            $data = json_decode($response->getBody(), true);
            return [
                'online' => true,
                'status' => $data['status'] ?? 'unknown',
                'uptime' => $data['uptime'] ?? null,
                'version' => $data['version'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'online' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test VK connection
     */
    public function testConnection(Request $request)
    {
        $token = \Option::get('vkintegration.access_token', '');
        $groupId = \Option::get('vkintegration.group_id', '');

        if (empty($token) || empty($groupId)) {
            return response()->json([
                'success' => false,
                'message' => 'VK Token or Group ID not configured',
            ]);
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.vk.com/method/groups.getById', [
                'query' => [
                    'group_id' => $groupId,
                    'access_token' => $token,
                    'v' => '5.131',
                ],
            ]);
            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['error']['error_msg'] ?? 'VK API Error',
                ]);
            }

            $group = $data['response'][0] ?? null;
            return response()->json([
                'success' => true,
                'message' => 'Connected to VK group: ' . ($group['name'] ?? 'Unknown'),
                'group' => $group,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Restart bridge service
     */
    public function restartBridge(Request $request)
    {
        try {
            $output = shell_exec('sudo /bin/systemctl restart vk-freescout 2>&1');
            sleep(2);
            $status = shell_exec('sudo /bin/systemctl status vk-freescout 2>&1');

            return response()->json([
                'success' => strpos($status, 'active (running)') !== false,
                'message' => strpos($status, 'active (running)') !== false ? 'Bridge restarted successfully' : 'Bridge may have failed to start',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Stop bridge service
     */
    public function stopBridge(Request $request)
    {
        try {
            $output = shell_exec('sudo /bin/systemctl stop vk-freescout 2>&1');

            return response()->json([
                'success' => true,
                'message' => 'Bridge stopped',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get config for bridge (API endpoint)
     */
    public function getConfig(Request $request)
    {
        $secretKey = $request->header('X-Secret-Key');
        $storedSecret = \Option::get('vkintegration.secret_key', '');

        if (empty($storedSecret) || !hash_equals((string) $storedSecret, (string) $secretKey)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'vk_token' => \Option::get('vkintegration.access_token', ''),
            'vk_group_id' => \Option::get('vkintegration.group_id', ''),
            'vk_confirmation_code' => \Option::get('vkintegration.confirmation_code', ''),
            'freescout_api_key' => \Option::get('vkintegration.freescout_api_key', ''),
            'freescout_url' => config('app.url'),
            'default_mailbox_id' => \Option::get('vkintegration.default_mailbox', '1'),
            'billing_enabled' => \Option::get('vkintegration.billing_enabled', false),
            'billing_url' => \Option::get('vkintegration.billing_url', ''),
        ]);
    }

    /**
     * Receive status report from bridge
     */
    public function reportStatus(Request $request)
    {
        $secretKey = $request->header('X-Secret-Key');
        $storedSecret = \Option::get('vkintegration.secret_key', '');

        if (empty($storedSecret) || !hash_equals((string) $storedSecret, (string) $secretKey)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Store bridge status
        \Option::set('vkintegration.bridge_last_status', json_encode([
            'status' => $request->input('status'),
            'timestamp' => now()->toIso8601String(),
            'uptime' => $request->input('uptime'),
            'messages_processed' => $request->input('messages_processed'),
        ]));

        return response()->json(['success' => true]);
    }

    /**
     * VK webhook handler
     */
    public function webhook(Request $request)
    {
        $data = $request->all();
        $type = $data['type'] ?? '';

        // Confirmation request
        if ($type === 'confirmation') {
            $confirmationCode = \Option::get('vkintegration.confirmation_code', '');
            return response($confirmationCode, 200)->header('Content-Type', 'text/plain');
        }

        // Verify secret
        $secret = $data['secret'] ?? '';
        $storedSecret = \Option::get('vkintegration.secret_key', '');
        if (!empty($storedSecret) && !hash_equals((string) $storedSecret, (string) $secret)) {
            \Log::warning('VK webhook: invalid secret');
            return response('ok', 200);
        }

        // Log event type for debugging (direct file log to bypass OPcache)
        if (env('VK_DEBUG', false)) {
            file_put_contents('/tmp/vk_callback.log', date('Y-m-d H:i:s') . ' type=' . $type . ' data=' . json_encode($data) . "
", FILE_APPEND);
        }

        // Forward to bridge
        $bridgeUrl = \Option::get('vkintegration.bridge_url', 'http://localhost:8901');
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 30]);
            $client->post($bridgeUrl . '/vk/callback', [
                'json' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('VK webhook forward error: ' . $e->getMessage());
        }

        // Forward lead_forms_new to aida-gpt for AMO/FreeScout integration
        if ($type === 'lead_forms_new') {
            try {
                $client2 = new \GuzzleHttp\Client(['timeout' => 10]);
                $client2->post('http://127.0.0.1:8900/webhooks/vk-lead-form', [
                    'json' => $data,
                ]);
            } catch (\Exception $e) {
                \Log::error('VK lead form forward error: ' . $e->getMessage());
            }
        }

        return response('ok', 200);
    }

    /**
     * Get statistics
     */
    private function getStats()
    {
        // Получаем ID всех VK клиентов (по email vk*@vk.com)
        $vkCustomerIds = \DB::table('emails')
            ->where('email', 'like', 'vk%@vk.com')
            ->whereNotNull('customer_id')
            ->distinct()
            ->pluck('customer_id');

        // Тикетов всего (по VK клиентам)
        $totalConversations = \App\Conversation::whereIn('customer_id', $vkCustomerIds)->count();

        // Тикетов сегодня (по VK клиентам)
        $todayConversations = \App\Conversation::whereIn('customer_id', $vkCustomerIds)
            ->whereDate('created_at', today())
            ->count();

        // VK клиентов
        $totalCustomers = $vkCustomerIds->count();

        return [
            'total_conversations' => $totalConversations,
            'today_conversations' => $todayConversations,
            'total_customers' => $totalCustomers,
        ];
    }

    /**
     * View logs
     */
    public function viewLogs(Request $request)
    {
        $lines = (int) $request->input('lines', 100);
        $logFile = '/var/www/vk-freescout/vk_bot.log';

        if (!file_exists($logFile)) {
            return response()->json(['logs' => 'Log file not found']);
        }

        $logs = shell_exec("tail -n {$lines} " . escapeshellarg($logFile));

        return response()->json(['logs' => $logs]);
    }
}
