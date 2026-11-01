<?php

namespace Modules\SatRatingsChat\Providers;

use App\Thread;
use App\Mailbox;
use Illuminate\Support\ServiceProvider;

class SatRatingsChatServiceProvider extends ServiceProvider
{
    const CHANNEL_VK = 10;
    const CHANNEL_TELEGRAM = 11;

    const RATING_GREAT = 1;
    const RATING_OKAY = 2;
    const RATING_BAD = 3;

    const VK_BRIDGE_URL = 'http://localhost:8901';

    // Don't ask more often than this (hours)
    const ASK_COOLDOWN_HOURS = 24;

    public function boot()
    {
        $this->registerConfig();
        $this->registerRoutes();
        $this->hooks();

        // Register middleware to intercept Telegram callback_query
        $this->app['router']->pushMiddlewareToGroup('web',
            \Modules\SatRatingsChat\Http\Middleware\ChatRatingInterceptor::class
        );
    }

    public function register()
    {
        //
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'satratingschat');
    }

    protected function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
    }

    public function hooks()
    {
        // Hook AFTER operator reply is sent (priority 25, after Telegram/VK at 20)
        \Eventy::addAction('chat_conversation.send_reply', function ($conversation, $replies, $customer) {
            $this->handleReply($conversation, $replies);
        }, 25, 3);

        // VK uses conversation.send_reply (not chat_conversation)
        \Eventy::addAction('conversation.send_reply', function ($conversation, $threads, $customer) {
            if ((int) $conversation->channel === self::CHANNEL_VK) {
                $this->handleReply($conversation, $threads);
            }
        }, 25, 3);
    }

    /**
     * Handle operator reply — decide whether to send rating request.
     */
    protected function handleReply($conversation, $replies)
    {
        $channel = (int) $conversation->channel;

        if (!in_array($channel, [self::CHANNEL_VK, self::CHANNEL_TELEGRAM])) {
            return;
        }

        // Get the latest thread (operator's reply)
        $thread = null;
        if ($replies instanceof \Illuminate\Support\Collection) {
            $thread = $replies->sortByDesc('id')->first();
        } else {
            $thread = is_array($replies) ? end($replies) : $replies;
        }

        if (!$thread) {
            return;
        }

        // Reload from DB to have fresh data
        if (method_exists($thread, 'fresh')) {
            $thread = $thread->fresh();
        }

        if (!$thread) {
            return;
        }

        // Only on actual operator replies (not notes, not system)
        if ($thread->type != Thread::TYPE_MESSAGE) {
            return;
        }

        // Must be created by a user (operator), not auto/system
        if (!$thread->created_by_user_id) {
            return;
        }

        // Check cooldown — don't ask more than once per 24 hours
        $lastAsked = $conversation->getMeta('sat_rating_last_asked');
        if ($lastAsked) {
            try {
                $lastTime = \Carbon\Carbon::parse($lastAsked);
                if ($lastTime->diffInHours(now()) < self::ASK_COOLDOWN_HOURS) {
                    return;
                }
            } catch (\Exception $e) {
                // Ignore parse errors
            }
        }

        // Check if already rated in this conversation
        $alreadyRated = \DB::table('threads')
            ->where('conversation_id', $conversation->id)
            ->where('rating', '>', 0)
            ->exists();

        if ($alreadyRated) {
            return;
        }

        // Send rating request
        try {
            if ($channel === self::CHANNEL_TELEGRAM) {
                $this->sendTelegramRatingRequest($conversation, $thread);
            } elseif ($channel === self::CHANNEL_VK) {
                $this->sendVkRatingRequest($conversation, $thread);
            }

            // Mark that we asked
            $conversation->setMeta('sat_rating_last_asked', now()->toIso8601String());
            $conversation->setMeta('sat_rating_thread_id', $thread->id);
        } catch (\Exception $e) {
            \Log::error('[SatRatingsChat] Error: ' . $e->getMessage(), [
                'conversation_id' => $conversation->id,
                'channel' => $channel,
            ]);
        }
    }

    /**
     * Send Telegram inline keyboard with rating buttons.
     */
    protected function sendTelegramRatingRequest($conversation, $thread)
    {
        // Get chat_id from customer
        $customer = $conversation->customer;
        if (!$customer) {
            $customer = \DB::table('customers')->where('id', $conversation->customer_id)->first();
        }

        $chatId = null;
        if (is_object($customer)) {
            $chatId = $customer->channel_id ?? null;
        }

        if (!$chatId) {
            \Log::warning('[SatRatingsChat] No Telegram chat_id', [
                'conversation_id' => $conversation->id,
            ]);
            return;
        }

        // Get bot token from mailbox meta
        $botToken = $this->getTelegramBotToken($conversation->mailbox_id);

        if (!$botToken) {
            \Log::error('[SatRatingsChat] No Telegram bot token found');
            return;
        }

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => "\u{2705} Отлично", 'callback_data' => 'satrate:' . $thread->id . ':1'],
                    ['text' => "\u{1F44D} Нормально", 'callback_data' => 'satrate:' . $thread->id . ':2'],
                    ['text' => "\u{1F44E} Плохо", 'callback_data' => 'satrate:' . $thread->id . ':3'],
                ],
            ],
        ];

        $data = [
            'chat_id' => $chatId,
            'text' => 'Пожалуйста, оцените качество ответа:',
            'reply_markup' => json_encode($keyboard),
        ];

        $this->callTelegramApi($botToken, 'sendMessage', $data);

        \Log::info('[SatRatingsChat] Telegram rating request sent', [
            'conversation_id' => $conversation->id,
            'thread_id' => $thread->id,
            'chat_id' => $chatId,
        ]);
    }

    /**
     * Send VK message with inline keyboard.
     */
    protected function sendVkRatingRequest($conversation, $thread)
    {
        $vkUserId = $conversation->getMeta('vk_user_id');
        if (!$vkUserId) {
            \Log::warning('[SatRatingsChat] No vk_user_id', [
                'conversation_id' => $conversation->id,
            ]);
            return;
        }

        $keyboard = [
            'inline' => true,
            'buttons' => [
                [
                    [
                        'action' => [
                            'type' => 'callback',
                            'label' => 'Отлично',
                            'payload' => json_encode([
                                'action' => 'satrate',
                                'thread_id' => $thread->id,
                                'rating' => self::RATING_GREAT,
                            ]),
                        ],
                        'color' => 'positive',
                    ],
                    [
                        'action' => [
                            'type' => 'callback',
                            'label' => 'Нормально',
                            'payload' => json_encode([
                                'action' => 'satrate',
                                'thread_id' => $thread->id,
                                'rating' => self::RATING_OKAY,
                            ]),
                        ],
                        'color' => 'primary',
                    ],
                    [
                        'action' => [
                            'type' => 'callback',
                            'label' => 'Плохо',
                            'payload' => json_encode([
                                'action' => 'satrate',
                                'thread_id' => $thread->id,
                                'rating' => self::RATING_BAD,
                            ]),
                        ],
                        'color' => 'negative',
                    ],
                ],
            ],
        ];

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $response = $client->post(self::VK_BRIDGE_URL . '/send_message', [
                'json' => [
                    'user_id' => (int) $vkUserId,
                    'message' => 'Пожалуйста, оцените качество ответа:',
                    'keyboard' => json_encode($keyboard),
                ],
            ]);

            \Log::info('[SatRatingsChat] VK rating request sent', [
                'conversation_id' => $conversation->id,
                'thread_id' => $thread->id,
                'vk_user_id' => $vkUserId,
            ]);
        } catch (\Exception $e) {
            \Log::error('[SatRatingsChat] VK bridge error: ' . $e->getMessage());
        }
    }

    /**
     * Save rating via SatRatingsHelper or directly.
     */
    public static function saveRating($threadId, $rating)
    {
        $rating = (int) $rating;
        if ($rating < 1 || $rating > 3) {
            return false;
        }

        $thread = Thread::find($threadId);
        if (!$thread) {
            return false;
        }

        // Already rated
        if ($thread->rating > 0) {
            return false;
        }

        // Use SatRatingsHelper if available (fires workflows etc.)
        if (class_exists('\SatRatingsHelper')) {
            \SatRatingsHelper::rate($thread, $rating);
        } else {
            // Fallback: direct DB update
            $thread->rating = $rating;
            $thread->save();
        }

        \Log::info('[SatRatingsChat] Rating saved', [
            'thread_id' => $threadId,
            'rating' => $rating,
            'conversation_id' => $thread->conversation_id,
        ]);

        return true;
    }

    /**
     * Get Telegram bot token from mailbox meta.
     */
    protected function getTelegramBotToken($mailboxId)
    {
        $mailbox = Mailbox::find($mailboxId);
        if ($mailbox) {
            $meta = $mailbox->meta ?? [];
            $tg = $meta['telegram'] ?? [];
            if (!empty($tg['token'])) {
                return $tg['token'];
            }
        }

        // Fallback: try all mailboxes
        $mailboxes = Mailbox::all();
        foreach ($mailboxes as $mb) {
            $meta = $mb->meta ?? [];
            $tg = $meta['telegram'] ?? [];
            if (!empty($tg['token'])) {
                return $tg['token'];
            }
        }

        return null;
    }

    /**
     * Call Telegram Bot API.
     */
    public static function callTelegramApi($token, $method, $data)
    {
        $url = 'https://api.telegram.org/bot' . $token . '/' . $method;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            \Log::error('[SatRatingsChat] Telegram API error: ' . $error);
            return null;
        }

        return json_decode($result, true);
    }
}
