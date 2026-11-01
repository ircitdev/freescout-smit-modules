<?php

namespace Modules\ClaudeAI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ClaudeAI\Services\ClaudeService;

class ClaudeAIController extends Controller
{
    /**
     * Settings page.
     */
    public function settings()
    {
        return view('claudeai::settings.index', [
            'settings' => $this->getSettings(),
            'models'   => [
                'claude-haiku-4-5-20251001'  => 'Claude Haiku 4.5 — быстрый, дешёвый ($0.80/$4 за 1M токенов)',
                'claude-sonnet-4-6-20250514' => 'Claude Sonnet 4.6 — умный, средний ($3/$15 за 1M токенов)',
                'claude-opus-4-6-20250514'   => 'Claude Opus 4.6 — максимальный ($15/$75 за 1M токенов)',
            ],
        ]);
    }

    /**
     * Save settings.
     */
    public function saveSettings(Request $request)
    {
        $fields = [
            'claudeai_api_key', 'claudeai_model', 'claudeai_max_tokens', 'claudeai_temperature',
            'claudeai_auto_reply', 'claudeai_operator_hint', 'claudeai_auto_classify',
            'claudeai_billing_url', 'claudeai_billing_token',
            'claudeai_use_kb', 'claudeai_require_review',
            'claudeai_max_auto_replies', 'claudeai_escalation_keywords',
            'claudeai_customer_prompt', 'claudeai_operator_prompt',
        ];

        foreach ($fields as $field) {
            $value = $request->input($field);
            if ($field === 'claudeai_api_key' && $value === '••••••••') {
                continue; // Don't overwrite masked key
            }
            \Option::set($field, $value ?? '');
        }

        // Checkboxes
        foreach (['claudeai_auto_reply', 'claudeai_operator_hint', 'claudeai_auto_classify', 'claudeai_use_kb', 'claudeai_require_review'] as $cb) {
            \Option::set($cb, $request->has($cb) ? '1' : '0');
        }

        \Session::flash('flash_success_floating', 'Настройки Claude AI сохранены');
        return redirect()->route('claudeai.settings');
    }

    /**
     * Test API connection.
     */
    public function testConnection()
    {
        $claude = new ClaudeService();
        $result = $claude->testConnection();

        return response()->json($result);
    }

    /**
     * Generate operator hint via AJAX.
     */
    public function operatorHint(Request $request)
    {
        $conversationId = $request->input('conversation_id');
        $ticketText = $request->input('ticket_text', '');
        $ticketSubject = $request->input('ticket_subject', '');

        // Get customer data from billing
        $customerData = [];
        $phone = $request->input('customer_phone');
        if ($phone) {
            $claude = new ClaudeService();
            $billingUrl = \Option::get('claudeai_billing_url') ?: config('claudeai.billing_url');
            if ($billingUrl) {
                $customerData = $claude->billingApiCall($billingUrl, '/rest_api/v2/Abonents/', ['qf' => $phone]);
            }
        }

        $claude = new ClaudeService();
        $result = $claude->operatorHint($ticketSubject, $ticketText, $customerData);

        if ($result['success'] ?? false) {
            // Parse structured response
            $response = $result['response'];
            return response()->json([
                'success'  => true,
                'response' => $response,
                'usage'    => $result['usage'] ?? [],
            ]);
        }

        return response()->json([
            'success' => false,
            'error'   => $result['error'] ?? 'Unknown error',
        ]);
    }

    /**
     * Generate auto-reply draft.
     */
    public function autoReply(Request $request)
    {
        $conversationId = $request->input('conversation_id');

        $conversation = \App\Conversation::find($conversationId);
        if (!$conversation) {
            return response()->json(['success' => false, 'error' => 'Тикет не найден']);
        }

        // Get conversation threads
        $threads = $conversation->threads()
            ->whereIn('type', [\App\Thread::TYPE_CUSTOMER, \App\Thread::TYPE_MESSAGE])
            ->orderBy('created_at')
            ->get();

        $history = [];
        foreach ($threads as $thread) {
            $history[] = [
                'from_customer' => $thread->type == \App\Thread::TYPE_CUSTOMER,
                'body'          => strip_tags($thread->body),
            ];
        }

        $lastThread = $threads->last();
        $ticketBody = $lastThread ? trim(strip_tags($lastThread->body)) : '';
        if (empty($ticketBody)) {
            $ticketBody = $conversation->subject ?: 'Клиент обратился с проблемой';
        }

        $claude = new ClaudeService();
        $result = $claude->autoReplyDraft(
            $conversation->subject ?: 'Обращение',
            $ticketBody,
            [],
            $history
        );

        return response()->json($result);
    }

    /**
     * Documentation page.
     */
    public function docs()
    {
        return view('claudeai::settings.docs');
    }

    /**
     * Usage statistics page.
     */
    public function stats()
    {
        // TODO: Implement usage tracking from logs
        return view('claudeai::settings.stats');
    }

    /**
     * Get current settings.
     */
    protected function getSettings(): array
    {
        return [
            'api_key'             => \Option::get('claudeai_api_key') ?: '',
            'model'               => \Option::get('claudeai_model') ?: 'claude-haiku-4-5-20251001',
            'max_tokens'          => \Option::get('claudeai_max_tokens') ?: '1024',
            'temperature'         => \Option::get('claudeai_temperature') ?: '0.3',
            'auto_reply'          => \Option::get('claudeai_auto_reply') ?: '0',
            'operator_hint'       => \Option::get('claudeai_operator_hint') ?: '1',
            'auto_classify'       => \Option::get('claudeai_auto_classify') ?: '1',
            'billing_url'         => \Option::get('claudeai_billing_url') ?: '',
            'billing_token'       => \Option::get('claudeai_billing_token') ?: '',
            'use_kb'              => \Option::get('claudeai_use_kb') ?: '1',
            'require_review'      => \Option::get('claudeai_require_review') ?: '1',
            'max_auto_replies'    => \Option::get('claudeai_max_auto_replies') ?: '3',
            'escalation_keywords' => \Option::get('claudeai_escalation_keywords') ?: 'оператор,человек,живой,менеджер',
            'customer_prompt'     => \Option::get('claudeai_customer_prompt') ?: '',
            'operator_prompt'     => \Option::get('claudeai_operator_prompt') ?: '',
        ];
    }
}
