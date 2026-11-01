<?php

namespace Modules\SatRatingsChat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\SatRatingsChat\Providers\SatRatingsChatServiceProvider;

/**
 * Middleware to intercept Telegram callback_query for satisfaction ratings.
 * Short-circuits the request before BotMan processes it.
 */
class ChatRatingInterceptor
{
    public function handle(Request $request, Closure $next)
    {
        // Only intercept POST requests to Telegram webhook
        if ($request->isMethod('post') && $request->is('*/telegram/webhook/*')) {
            $data = $request->json()->all();

            if (isset($data['callback_query']['data'])
                && strpos($data['callback_query']['data'], 'satrate:') === 0) {
                return $this->handleTelegramRating($data['callback_query'], $request);
            }
        }

        return $next($request);
    }

    /**
     * Handle Telegram inline button click for rating.
     */
    protected function handleTelegramRating($callbackQuery, Request $request)
    {
        $parts = explode(':', $callbackQuery['data']);
        // Format: satrate:{thread_id}:{rating}
        if (count($parts) !== 3) {
            return response('ok', 200);
        }

        $threadId = (int) $parts[1];
        $rating = (int) $parts[2];

        if ($rating < 1 || $rating > 3) {
            return response('ok', 200);
        }

        // Save the rating
        $saved = SatRatingsChatServiceProvider::saveRating($threadId, $rating);

        // Get bot token from URL segments
        $botToken = $this->extractBotToken($request);

        if ($botToken) {
            // Answer callback query (removes loading indicator on button)
            $ratingLabels = [
                1 => "\u{2705} Отлично!",
                2 => "\u{1F44D} Нормально",
                3 => "\u{1F44E} Плохо",
            ];

            $answerText = $saved
                ? 'Спасибо за оценку!'
                : 'Вы уже оценили этот ответ';

            SatRatingsChatServiceProvider::callTelegramApi($botToken, 'answerCallbackQuery', [
                'callback_query_id' => $callbackQuery['id'],
                'text' => $answerText,
                'show_alert' => false,
            ]);

            // Edit message: remove buttons, show result
            $chatId = $callbackQuery['message']['chat']['id'] ?? null;
            $messageId = $callbackQuery['message']['message_id'] ?? null;

            if ($chatId && $messageId) {
                $resultText = $saved
                    ? 'Спасибо за оценку: ' . ($ratingLabels[$rating] ?? '')
                    : 'Вы уже оценили этот ответ. Спасибо!';

                SatRatingsChatServiceProvider::callTelegramApi($botToken, 'editMessageText', [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'text' => $resultText,
                ]);
            }
        }

        return response('ok', 200);
    }

    /**
     * Extract Telegram bot token from mailbox referenced in the webhook URL.
     * URL format: /telegram/webhook/{mailbox_id}/{secret}
     */
    protected function extractBotToken(Request $request)
    {
        $path = $request->path();
        // Match: telegram/webhook/{mailbox_id}/{secret}
        if (preg_match('/telegram\/webhook\/(\d+)\//', $path, $matches)) {
            $mailboxId = (int) $matches[1];
            $mailbox = \App\Mailbox::find($mailboxId);
            if ($mailbox) {
                $meta = $mailbox->meta ?? [];
                $tg = $meta['telegram'] ?? [];
                return $tg['token'] ?? null;
            }
        }

        // Fallback: find any mailbox with telegram token
        $mailboxes = \App\Mailbox::all();
        foreach ($mailboxes as $mb) {
            $meta = $mb->meta ?? [];
            $tg = $meta['telegram'] ?? [];
            if (!empty($tg['token'])) {
                return $tg['token'];
            }
        }

        return null;
    }
}
