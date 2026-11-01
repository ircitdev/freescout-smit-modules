<?php

namespace Modules\AidaSessions\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class AidaSessionsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerViews();
        $this->registerRoutes();
        $this->hooks();
    }

    public function register()
    {
        //
    }

    protected function registerViews()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'aidasessions');
    }

    protected function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__.'/../Http/routes.php');
    }

    protected function hooks()
    {
        // Add menu item under "Управление" (Manage)
        \Eventy::addAction('menu.manage.after_mailboxes', function () {
            if (auth()->user() && auth()->user()->isAdmin()) {
                echo \View::make('aidasessions::partials/menu')->render();
            }
        }, 35);

        // Кнопка «AI-сессия» в сайдбаре тикета (рядом с «Пинг роутера»)
        \Eventy::addAction('conversation.before_prev_convs', function ($customer, $conversation, $mailbox) {
            try {
                $aidaSession = DB::table('aida_conversations')
                    ->where('ticket_id', $conversation->id)
                    ->where('ticket_created', 1)
                    ->first();

                if (!$aidaSession) {
                    return;
                }

                $aidaId = $aidaSession->id;
                $isVoice = $aidaSession->is_voice ? '🎤 Голосовой' : '💬 Текстовый';
                $msgCount = $aidaSession->messages_count ?: 0;
                $url = route('aidasessions.show', $aidaId);

                echo '<div>'
                   . '<a href="' . $url . '" target="_blank" '
                   . 'style="display:inline-flex;align-items:center;gap:5px;'
                   . 'background:linear-gradient(135deg,#7b1fa2 0%,#4a148c 100%);'
                   . 'color:#fff;text-decoration:none;padding:5px 14px;border-radius:4px;'
                   . 'font-size:12px;font-weight:600;transition:all .3s ease;'
                   . 'box-shadow:0 2px 6px rgba(74,20,140,.3)">'
                   . '📋 AI-сессия #' . $aidaId . '</a>'
                   . '<div style="font-size:11px;color:#888;margin-top:3px;">'
                   . $isVoice . ' &bull; ' . $msgCount . ' сообщ.</div>'
                   . '</div>';
            } catch (\Exception $e) {
                \Log::error('[AidaSessions] Sidebar button error: ' . $e->getMessage());
            }
        }, 9, 3);
    }
}
