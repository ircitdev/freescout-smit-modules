<?php

namespace Modules\SystemFilter\Providers;

use App\Conversation;
use App\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class SystemFilterServiceProvider extends ServiceProvider
{
    // Паттерны системных писем - автоматически удаляются
    private static $systemSubjectPatterns = [
        '/^Mail delivery failed/i',
        '/^Undelivered Mail Returned/i',
        '/^Delivery Status Notification/i',
        '/^failure notice/i',
        '/^Returned mail/i',
        '/^Автоматический ответ/i',
        '/^Auto:/i',
        '/^Out of Office/i',
        '/^Automatic reply/i',
        '/^Undeliverable:/i',
        '/^NDR:/i',
        '/^Недоставленное:/i',
        // DMARC отчёты
        '/^Report domain:/i',
        '/Report-ID:/i',
        '/DMARC/i',
        '/Submitter:.*google\.com/i',
    ];

    // Системные email адреса - автоматически удаляются
    private static $systemEmails = [
        'mailer-daemon@',
        'postmaster@',
        'noreply@',
        'no-reply@',
        'donotreply@',
        'daemon@',
        'noreply-dmarc-support@google.com',
        'dmarc@',
    ];

    public function boot()
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerMenu();
        $this->hooks();
    }

    public function register()
    {
        //
    }

    protected function registerRoutes()
    {
        Route::group([
            'middleware' => ['web', 'auth'],
            'prefix'     => \Helper::getSubdirectory(),
            'namespace'  => 'Modules\SystemFilter\Http\Controllers'
        ], function() {
            Route::get('/systemfilter/settings', 'SystemFilterController@settings')->name('systemfilter.settings');
        });
    }

    protected function registerViews()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'systemfilter');
    }

    protected function registerMenu()
    {
        // Добавляем пункт в меню "Управление" только для админов
        \Eventy::addAction('menu.manage.after_mailboxes', function() {
            if (Auth::user() && Auth::user()->isAdmin()) {
                echo View::make('systemfilter::partials.menu')->render();
            }
        }, 40);
    }

    public function hooks()
    {
        // Перехватываем создание тикета и удаляем системные
        \Eventy::addAction('conversation.created_by_customer', function($conversation, $thread, $customer) {
            if ($this->isSystemMessage($conversation, $customer)) {
                \Log::info('[SYSTEM-FILTER] Удаление системного тикета #' . $conversation->number . ': ' . $conversation->subject);

                // Удаляем threads и conversation
                $conversationId = $conversation->id;
                $conversationNumber = $conversation->number;

                try {
                    // Удаляем все threads этого разговора
                    \DB::table('threads')->where('conversation_id', $conversationId)->delete();

                    // Удаляем сам разговор
                    $conversation->delete();

                    \Log::info('[SYSTEM-FILTER] Тикет #' . $conversationNumber . ' удалён');
                } catch (\Exception $e) {
                    \Log::error('[SYSTEM-FILTER] Ошибка удаления тикета #' . $conversationNumber . ': ' . $e->getMessage());

                    // Fallback: просто закрываем
                    $conversation->status = Conversation::STATUS_CLOSED;
                    $conversation->closed_at = now();
                    $conversation->save();
                }
            }
        }, 1, 3); // priority 1 - выполняется самым первым
    }

    /**
     * Проверяет, является ли сообщение системным
     */
    protected function isSystemMessage($conversation, $customer): bool
    {
        // Проверяем тему письма
        $subject = $conversation->subject ?? '';
        foreach (self::$systemSubjectPatterns as $pattern) {
            if (preg_match($pattern, $subject)) {
                return true;
            }
        }

        // Проверяем email отправителя
        if ($customer && $customer->emails) {
            $emails = is_string($customer->emails) ? json_decode($customer->emails, true) : $customer->emails;
            if (is_array($emails)) {
                foreach ($emails as $emailData) {
                    $email = is_array($emailData) ? ($emailData['value'] ?? '') : $emailData;
                    $emailLower = strtolower($email);
                    foreach (self::$systemEmails as $systemEmail) {
                        if (strpos($emailLower, $systemEmail) !== false) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
