<?php

namespace Modules\VkIntegration\Entities;

use App\Channel;
use App\Conversation;
use App\Customer;
use App\Thread;
use GuzzleHttp\Client;

class VkChannel
{
    const CHANNEL = 10; // Custom channel ID for VK

    /**
     * VK Bridge API URL
     */
    const BRIDGE_URL = 'http://localhost:8901';

    /**
     * Send reply to VK user
     */
    public static function sendReply($conversation, $threads)
    {
        // Get the last thread (agent's reply)
        $thread = $threads->first();

        if (!$thread || $thread->type != Thread::TYPE_MESSAGE) {
            return;
        }

        // Get VK user ID from conversation meta
        $vk_user_id = $conversation->getMeta('vk_user_id');

        if (!$vk_user_id) {
            \Log::error('[VK] No VK user ID in conversation meta', [
                'conversation_id' => $conversation->id
            ]);
            return;
        }

        // Send message to VK via Python bridge
        try {
            $client = new Client([
                'timeout' => 10,
            ]);

            $response = $client->post(self::BRIDGE_URL . '/send_message', [
                'json' => [
                    'user_id' => (int)$vk_user_id,
                    'message' => $thread->getBodyAsText(),
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            if (!isset($result['success']) || !$result['success']) {
                throw new \Exception('Failed to send VK message: ' . ($result['error'] ?? 'Unknown error'));
            }

            \Log::info('[VK] Message sent successfully', [
                'conversation_id' => $conversation->id,
                'vk_user_id' => $vk_user_id,
            ]);

        } catch (\Exception $e) {
            \Log::error('[VK] Error sending message', [
                'conversation_id' => $conversation->id,
                'vk_user_id' => $vk_user_id,
                'error' => $e->getMessage()
            ]);

            // Add system note about error
            Thread::create($conversation, Thread::TYPE_NOTE, [
                'body' => __('Error sending VK message: :error', ['error' => $e->getMessage()]),
                'created_by_user_id' => $thread->created_by_user_id,
            ]);
        }
    }

    /**
     * Process incoming message from VK
     */
    public static function processIncomingMessage($data)
    {
        if (env('VK_DEBUG', false)) \Log::debug('[VK DEBUG] processIncomingMessage START', $data);
        $vk_user_id = $data['vk_user_id'] ?? null;
        $text = $data['text'] ?? '';
        $user_name = $data['user_name'] ?? 'VK User';
        $billing_data = $data['billing_data'] ?? null;

        if (!$vk_user_id) {
            \Log::error('[VK] No VK user ID in incoming message');
            return ['success' => false, 'error' => 'No VK user ID'];
        }

        // Find or create customer
        if (env('VK_DEBUG', false)) \Log::debug('[VK DEBUG] Before findOrCreateCustomer', ['vk_user_id' => $vk_user_id, 'user_name' => $user_name]);
        $customer = self::findOrCreateCustomer($vk_user_id, $user_name, $billing_data);
        if (env('VK_DEBUG', false)) \Log::debug('[VK DEBUG] After findOrCreateCustomer', ['customer_id' => $customer ? $customer->id : null]);

        if (!$customer) {
            \Log::error('[VK] Failed to create customer', ['vk_user_id' => $vk_user_id]);
            return ['success' => false, 'error' => 'Failed to create customer'];
        }

        // Find existing conversation or create new one
        if (env('VK_DEBUG', false)) \Log::debug('[VK DEBUG] Before findOrCreateConversation', ['customer_id' => $customer->id, 'vk_user_id' => $vk_user_id, 'text_length' => strlen($text)]);
        $conv_result = self::findOrCreateConversation($customer, $vk_user_id, $data, $text);
        if (env('VK_DEBUG', false)) \Log::debug('[VK DEBUG] After findOrCreateConversation', ['conversation_id' => $conv_result['conversation']->id ?? null, 'is_new' => $conv_result['is_new'] ?? null]);
        $conversation = $conv_result['conversation'];
        $is_new = $conv_result['is_new'];

        if (!$conversation) {
            \Log::error('[VK] Failed to create conversation', ['vk_user_id' => $vk_user_id]);
            return ['success' => false, 'error' => 'Failed to create conversation'];
        }

        // Add thread with message (only if conversation existed, new conversations already have first thread)
        if (!$is_new) {
            Thread::create($conversation, Thread::TYPE_CUSTOMER, [
                'body' => $text,
                'created_by_customer_id' => $customer->id,
                'source_via' => Thread::PERSON_CUSTOMER,
                'source_type' => Thread::SOURCE_TYPE_WEB,
            ]);
        }

        \Log::info('[VK] Message processed successfully', [
            'conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'vk_user_id' => $vk_user_id,
        ]);

        return [
            'success' => true,
            'conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
        ];
    }

    /**
     * Find or create customer
     */
    private static function findOrCreateCustomer($vk_user_id, $user_name, $billing_data = null)
    {
        // Try to find by channel and channel_id
        $customer = Customer::where('channel', self::CHANNEL)
            ->where('channel_id', $vk_user_id)
            ->first();

        if ($customer) {
            // Update customer data if billing info available
            if ($billing_data) {
                self::updateCustomerFromBilling($customer, $billing_data);
            }
            return $customer;
        }

        // Create new customer
        $email = 'vk_' . $vk_user_id . '@vk.placeholder';

        $names = explode(' ', $user_name, 2);
        $first_name = $names[0] ?? 'VK';
        $last_name = $names[1] ?? 'User';

        // FreeScout Customer::create() signature: create($email, $data)
        $customer = Customer::create($email, [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'channel' => self::CHANNEL,
            'channel_id' => $vk_user_id,
        ]);
        
        // Set VK user ID in meta
        $customer->setMeta('vk_user_id', $vk_user_id);

        // Update from billing if available
        if ($billing_data) {
            self::updateCustomerFromBilling($customer, $billing_data);
        }

        return $customer;
    }

    /**
     * Update customer from billing data
     */
    private static function updateCustomerFromBilling($customer, $billing_data)
    {
        // Update name if available
        if (!empty($billing_data['fullname'])) {
            $names = explode(' ', $billing_data['fullname'], 3);
            $customer->first_name = $names[1] ?? $customer->first_name; // Имя
            $customer->last_name = $names[0] ?? $customer->last_name;   // Фамилия
        }

        // Update phone if available
        if (!empty($billing_data['phone'])) {
            $customer->setPhones([$billing_data['phone']]);
        }

        // Update address if available
        if (!empty($billing_data['address'])) {
            $customer->address = [
                'country' => 'Russia',
                'address' => $billing_data['address'],
            ];
        }

        // Save billing meta
        if (!empty($billing_data['contract'])) {
            $customer->setMeta('vk_contract', $billing_data['contract']);
        }
        if (!empty($billing_data['balance'])) {
            $customer->setMeta('vk_balance', $billing_data['balance']);
        }
        if (!empty($billing_data['tariff'])) {
            $customer->setMeta('vk_tariff', $billing_data['tariff']);
        }

        $customer->save();
    }

    /**
     * Find or create conversation
     */
    private static function findOrCreateConversation($customer, $vk_user_id, $data, $text)
    {
        // Try to find existing open conversation
        $conversation = Conversation::where('customer_id', $customer->id)
            ->where('channel', self::CHANNEL)
            ->where('status', Conversation::STATUS_ACTIVE)
            ->first();

        if ($conversation) {
            return ['conversation' => $conversation, 'is_new' => false];
        }

        // Create new conversation
        $subject = self::generateSubject($data);
        $mailbox = \App\Mailbox::first(); // Get first mailbox or configure default

        $result = Conversation::create(
            [
                'type' => 5, // TYPE_SOCIAL (registered in AIFormLabel module)
                'subject' => $subject,
                'mailbox_id' => $mailbox->id,
                'channel' => self::CHANNEL,
                'source_via' => Conversation::PERSON_CUSTOMER,
                'source_type' => Conversation::SOURCE_TYPE_WEB,
            ],
            [
                [
                    'type' => Thread::TYPE_CUSTOMER,
                    'body' => $text,
                    'created_by_customer_id' => $customer->id,
                    'source_via' => Thread::PERSON_CUSTOMER,
                    'source_type' => Thread::SOURCE_TYPE_WEB,
                ]
            ],
            $customer
        );
        
        $conversation = $result['conversation'];

        // Устанавливаем custom field "Тип" = "Физ.лицо" для mailbox "Поддержка клиентов" (id=1)
        if ($mailbox->id == 1) {
            \Modules\CustomFields\Entities\ConversationCustomField::create([
                'conversation_id' => $conversation->id,
                'custom_field_id' => 23,
                'value' => '1'
            ]);
        }

        // Set VK user ID in conversation meta
        $conversation->setMeta('vk_user_id', $vk_user_id);

        // Add billing note if available
        if (!empty($data['billing_data'])) {
            self::addBillingNote($conversation, $data['billing_data']);
        }

        return ['conversation' => $conversation, 'is_new' => true];
    }

    /**
     * Generate conversation subject
     */
    private static function generateSubject($data)
    {
        $contract = $data['billing_data']['contract'] ?? '';
        $text = $data['text'] ?? '';

        if ($contract && $text) {
            // Create summary (first 60 chars)
            $summary = mb_strlen($text) > 60 ? mb_substr($text, 0, 60) . '...' : $text;
            $summary = str_replace("\n", " ", $summary);
            return $summary;
        }

        // Fallback to user name
        return $data['user_name'] ?? 'Unknown User';
    }

    /**
     * Add billing information as note
     */
    private static function addBillingNote($conversation, $billing_data)
    {
        $note = "Данные из биллинга:\n";
        $note .= "👤 Имя: " . ($billing_data['fullname'] ?? 'N/A') . "\n";
        $note .= "📄 Договор: " . ($billing_data['contract'] ?? 'N/A') . "\n";
        $note .= "💰 Баланс: " . ($billing_data['balance'] ?? 'N/A') . " руб.\n";
        $note .= "📦 Тариф: " . ($billing_data['tariff'] ?? 'N/A') . "\n";
        $note .= "📞 Телефон: " . ($billing_data['phone'] ?? 'N/A');

        Thread::create($conversation, Thread::TYPE_NOTE, [
            'body' => $note,
            'created_by_user_id' => null,
        ]);
    }
}
