<?php

namespace Modules\VoiceMail\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class VoiceMailServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerRoutes();
        $this->registerCommands();
        $this->hooks();
    }

    const ROBOR_MAILBOX_ID = 8; // Технический mailbox для бота @your_bot

    public function hooks()
    {
        // Автоматическое тегирование новых тикетов
        // - mailbox 8 (ROBOR) → перемещаем в mailbox 1 + тег "робор"
        // - остальные → тег "смит"
        // Пропускает: mailbox 7 (Звонки), channel 200 (VoiceMail), channel 100/101 (AI)

        // Тикеты от клиентов (email, формы, Telegram и т.д.)
        \Eventy::addAction('conversation.created_by_customer', function($conversation, $thread) {
            if ($conversation->mailbox_id == 7) {
                return; // VoiceMail служебный ящик
            }
            // Проверяем channel из БД (может быть обновлён через SQL после создания)
            $channel = (int)\DB::table('conversations')->where('id', $conversation->id)->value('channel');
            if (in_array($channel, [100, 101, 102, 103, 200])) {
                return; // AI Assistant (100=text, 101=form, 102=voice) и VoiceMail — тегируются в server.py
            }
            // Проставляем channel=1 для email-тикетов (FreeScout не ставит его автоматически)
            if (is_null($conversation->channel) && $conversation->source_type == \App\Conversation::SOURCE_TYPE_EMAIL) {
                $conversation->channel = 1;
                $conversation->save();
            }
            try {
                // Тикеты из mailbox ROBOR → перемещаем в mailbox 1 + тег "робор"
                if ($conversation->mailbox_id == self::ROBOR_MAILBOX_ID) {
                    // Получаем папку "Неназначенные" (type=1) из mailbox 1
                    $folder = \App\Folder::where('mailbox_id', 1)->where('type', \App\Folder::TYPE_UNASSIGNED)->first();
                    $conversation->mailbox_id = 1;
                    if ($folder) {
                        $conversation->folder_id = $folder->id;
                    }
                    $conversation->save();
                    \App\Mailbox::find(self::ROBOR_MAILBOX_ID)->updateFoldersCounters();
                    \App\Mailbox::find(1)->updateFoldersCounters();
                    \Modules\Tags\Entities\Tag::attachByName('робор', $conversation->id);
                    // Поле "Чей" = РОБОР (2)
                    self::setCustomerCheyField($conversation->customer_id, '2');
                    \Log::info('[AutoTag] ROBOR ticket #' . $conversation->id . ' moved to mailbox 1 folder ' . ($folder->id ?? 'null') . ', tag "робор"');
                    return;
                }
                // Если тег "робор" уже прикреплён (server.py для ROBOR-сессий) — не добавлять "смит"
                $hasRobor = \DB::table('conversation_tag')
                    ->join('tags', 'tags.id', '=', 'conversation_tag.tag_id')
                    ->where('conversation_tag.conversation_id', $conversation->id)
                    ->where('tags.name', 'робор')
                    ->exists();
                if ($hasRobor) {
                    \Log::info('[AutoTag] Skipping "смит" — "робор" already on #' . $conversation->id);
                    return;
                }
                \Modules\Tags\Entities\Tag::attachByName('смит', $conversation->id);
                // Поле "Чей" = СМИТ (1) для остальных источников (VK, email и т.д.)
                if ($conversation->customer_id) {
                    self::setCustomerCheyField($conversation->customer_id, '1');
                }
                \Log::info('[AutoTag] Tag "смит" attached to customer conversation #' . $conversation->id);
            } catch (\Exception $e) {
                \Log::error('[AutoTag] Error: ' . $e->getMessage());
            }
        }, 10, 2);

        // Тикеты, созданные оператором или через API
        \Eventy::addAction('conversation.created_by_user', function($conversation, $thread) {
            if ($conversation->mailbox_id == 7) {
                return;
            }
            // Проверяем channel из БД (может быть обновлён через SQL после создания)
            $channel = (int)\DB::table('conversations')->where('id', $conversation->id)->value('channel');
            if (in_array($channel, [100, 101, 102, 103, 200])) {
                return; // AI Assistant и VoiceMail — тегируются в server.py
            }
            try {
                if ($conversation->mailbox_id == self::ROBOR_MAILBOX_ID) {
                    $folder = \App\Folder::where('mailbox_id', 1)->where('type', \App\Folder::TYPE_UNASSIGNED)->first();
                    $conversation->mailbox_id = 1;
                    if ($folder) {
                        $conversation->folder_id = $folder->id;
                    }
                    $conversation->save();
                    \App\Mailbox::find(self::ROBOR_MAILBOX_ID)->updateFoldersCounters();
                    \App\Mailbox::find(1)->updateFoldersCounters();
                    \Modules\Tags\Entities\Tag::attachByName('робор', $conversation->id);
                    \Log::info('[AutoTag] ROBOR agent ticket #' . $conversation->id . ' moved to mailbox 1 folder ' . ($folder->id ?? 'null') . ', tag "робор"');
                    return;
                }
                // Если тег "робор" уже прикреплён (server.py для ROBOR-сессий) — не добавлять "смит"
                $hasRobor = \DB::table('conversation_tag')
                    ->join('tags', 'tags.id', '=', 'conversation_tag.tag_id')
                    ->where('conversation_tag.conversation_id', $conversation->id)
                    ->where('tags.name', 'робор')
                    ->exists();
                if ($hasRobor) {
                    \Log::info('[AutoTag] Skipping "смит" — "робор" already on #' . $conversation->id);
                    return;
                }
                \Modules\Tags\Entities\Tag::attachByName('смит', $conversation->id);
                \Log::info('[AutoTag] Tag "смит" attached to agent conversation #' . $conversation->id);
            } catch (\Exception $e) {
                \Log::error('[AutoTag] Error: ' . $e->getMessage());
            }
        }, 10, 2);

        // Блокируем мусорные письма: DMARC-отчёты, Mango auto-mailer
        \Eventy::addFilter('fetch_emails.should_save_thread', function($shouldSave, $data) {
            $from    = $data['from'] ?? '';
            $subject = $data['subject'] ?? '';

            // Mango Office — пропускаем в mailbox 7 (Звонки), блокируем в остальных
            if (stripos($from, '@mangotele.com') !== false) {
                $mailboxId = $data['mailbox_id'] ?? ($data['mailbox']->id ?? 0);
                if ($mailboxId != 7) {
                    \Log::info('[VoiceMail] Blocked Mango email in mailbox ' . $mailboxId . ': ' . $from);
                    return false;
                }
                // В mailbox 7 — пропускаем для создания тикета
                \Log::info('[VoiceMail] Allowing Mango voicemail in mailbox 7: ' . $subject);
                return $shouldSave;
            }
            // DMARC-отчёты (Google, Mail.ru, Yandex и др.)
            if (stripos($from, 'noreply-dmarc-support@google.com') !== false
                || stripos($from, 'dmarc') !== false
                || stripos($subject, 'Report domain:') !== false
                || stripos($subject, 'DMARC') !== false
            ) {
                \Log::info('[VoiceMail] Blocked DMARC report: ' . $from . ' / ' . $subject);
                return false;
            }
            // Bounce-письма (Mail Delivery Failed, mailer-daemon)
            if (stripos($from, 'mailer-daemon') !== false
                || stripos($from, 'mail-daemon') !== false
                || stripos($from, 'postmaster@') !== false
                || stripos($subject, 'Mail delivery failed') !== false
                || stripos($subject, 'Undelivered Mail') !== false
                || stripos($subject, 'Delivery Status Notification') !== false
            ) {
                \Log::info('[VoiceMail] Blocked bounce email: ' . $from . ' / ' . $subject);
                return false;
            }

            return $shouldSave;
        }, 5, 2);

        // Скрываем служебные ящики из меню и dashboard для всех пользователей
        // Видимые: Поддержка клиентов (1), Подключение (5)
        $hidden_mailboxes = [2, 3, 4, 6, 7, 8]; // Технический отдел, Биллинг, Маркетинг/Партнёры, В ожидании, Звонки, ROBOR

        \Eventy::addFilter('menu.mailboxes', function($mailboxes) use ($hidden_mailboxes) {
            return $mailboxes->filter(function($mailbox) use ($hidden_mailboxes) {
                return !in_array($mailbox->id, $hidden_mailboxes);
            });
        }, 20);

        \Eventy::addFilter('dashboard.mailboxes', function($mailboxes) use ($hidden_mailboxes) {
            return $mailboxes->filter(function($mailbox) use ($hidden_mailboxes) {
                return !in_array($mailbox->id, $hidden_mailboxes);
            });
        }, 20);

        // Скрываем поле "Тип" и страну клиента через CSS
        \Eventy::addAction('javascript', function() {
            echo 'document.addEventListener("DOMContentLoaded", function() {';
            // Поле custom-field-23 удалено
            // Скрываем строку "Russia" / страну в карточке клиента
            echo '  document.querySelectorAll(".customer-country, .conv-customer-country").forEach(function(e){e.style.display="none";});';
            // Скрываем "Chats" в боковом меню ящиков
            echo '  document.querySelectorAll("#folders-sidebar a, .mailbox-sidebar a").forEach(function(a){if(a.textContent.trim()==="Chats"){var li=a.closest("li");if(li)li.style.display="none";}});';
            echo '});';
        });

        // Календарь: переименование кнопок + ограничения для монтажников
        \Eventy::addAction('javascript', function() {
            $userId = auth()->check() ? auth()->user()->id : 0;
            $isAdmin = auth()->check() && auth()->user()->isAdmin();
            echo 'document.addEventListener("DOMContentLoaded", function() {';
            // Переименование кнопок
            echo '  var _ri = setInterval(function() {';
            echo '    var cb = document.getElementById("create-copy-button");';
            echo '    var lb = document.getElementById("copy-permalink-button");';
            echo '    if (cb) cb.textContent = "Копировать";';
            echo '    if (lb) lb.textContent = "Ссылка";';
            echo '    if (cb && lb) clearInterval(_ri);';
            echo '  }, 500);';
            $isMontazh = auth()->check() && \DB::table('user_user_field')
                ->where('user_id', $userId)->where('user_field_id', 5)->where('value', '2')->exists();
            if ($isMontazh) {
                // Для монтажников: скрыть кнопки если не автор, скрыть "Created by" и "Календарь"
                echo '  var _uid = ' . $userId . ';';
                echo '  setInterval(function() {';
                echo '    var modal = document.getElementById("event-details-modal");';
                echo '    if (!modal || modal.style.display === "none" || modal.style.display === "") return;';
                // Скрываем "Created by"
                echo '    var cbg = modal.querySelector(".event-details-created-by-form-group");';
                echo '    if (cbg) cbg.style.display = "none";';
                // Скрываем "Календарь"
                echo '    var ecal = document.getElementById("event-details-calendar");';
                echo '    if (ecal && ecal.parentNode && ecal.parentNode.classList.contains("form-group")) ecal.parentNode.style.display = "none";';
                // Проверяем автора события
                echo '    var cfEl = document.getElementById("hidden-event-details-custom-fields");';
                echo '    var db = document.getElementById("delete-button");';
                echo '    var ccb = document.getElementById("create-copy-button");';
                echo '    if (cfEl && cfEl.value) {';
                echo '      try {';
                echo '        var cf = JSON.parse(cfEl.value);';
                echo '        var own = cf.author_id && cf.author_id == _uid;';
                echo '        if (db) db.style.display = own ? "" : "none";';
                echo '        if (ccb) ccb.style.display = own ? "" : "none";';
                echo '      } catch(e) {}';
                echo '    }';
                echo '  }, 300);';
                // Скрываем элементы интерфейса тикета для монтажников
                echo '  var s=document.createElement("style");s.textContent="';
                echo '.form-horizontal.kn-card-form{display:none!important}';
                echo '#conv-toolbar{display:none!important}';
                echo '.conv-top-block.clearfix{display:none!important}';
                echo '#time-tracking{display:none!important}';
                echo '.dropdown.customer-trigger{display:none!important}';
                echo '.conv-subj-block{display:none!important}';
                echo '";document.head.appendChild(s);';
            }
            echo '});';
        }, 25);

        // При удалении тикета — удалять связанное событие из календаря
        \Eventy::addAction('conversation.deleted', function($conversation, $user) {
            try {
                $items = \DB::table('calendar_items')
                    ->where('custom_fields', 'LIKE', '%"conversation_id":' . $conversation->id . '%')
                    ->orWhere('custom_fields', 'LIKE', '%"conversation_id": ' . $conversation->id . '%')
                    ->get(['id']);
                foreach ($items as $item) {
                    \DB::table('calendar_items')->where('id', $item->id)->delete();
                    \Log::info('[Calendar] Удалено событие #' . $item->id . ' (тикет #' . $conversation->id . ' удалён)');
                }
            } catch (\Exception $e) {
                \Log::error('[Calendar] Ошибка удаления события: ' . $e->getMessage());
            }
        }, 20, 2);

        \Eventy::addAction('conversations.before_delete_forever', function($conversationIds) {
            try {
                foreach ($conversationIds as $convId) {
                    $items = \DB::table('calendar_items')
                        ->where('custom_fields', 'LIKE', '%"conversation_id":' . $convId . '%')
                        ->orWhere('custom_fields', 'LIKE', '%"conversation_id": ' . $convId . '%')
                        ->get(['id']);
                    foreach ($items as $item) {
                        \DB::table('calendar_items')->where('id', $item->id)->delete();
                        \Log::info('[Calendar] Удалено событие #' . $item->id . ' (тикет #' . $convId . ' удалён навсегда)');
                    }
                }
            } catch (\Exception $e) {
                \Log::error('[Calendar] Ошибка удаления событий: ' . $e->getMessage());
            }
        }, 20);

        $channel_id = 200;

        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['voicemail'] = ['folder'];
            return $menu;
        }, 30);

        // Обработка входящих писем голосовой почты
        \Eventy::addAction('conversation.created_by_customer', function($conversation, $thread) {
            try {
                $processor = new \Modules\VoiceMail\Listeners\VoiceMailProcessor();
                $event = new \stdClass();
                $event->conversation = $conversation;
                $event->threads = collect([$thread]);
                $processor->handle($event);
            } catch (\Exception $e) {
                \Log::error('[VoiceMail] Error: ' . $e->getMessage());
            }
        }, 20, 2);

        \Eventy::addAction('menu.manage.after_mailboxes', function() {
            if (auth()->user()->isAdmin()) {
                echo \View::make('voicemail::partials/menu')->render();
            }
        }, 35);

        // === CHANNEL BADGES (единственный источник — все остальные модули очищены) ===

        // Плашки каналов в списке тикетов
        \Eventy::addAction('conversations_table.before_subject', function($conversation) {
            if (self::labelsHidden()) return;
            $channel = (int)$conversation->channel;
            $badge = '';
            switch ($channel) {
                case 200:
                    $badge = '<span class="fs-tag pull-left" style="background-color: #546E7A;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-earphone"></small> Phone</span></span>';
                    break;
                case 100:
                    $badge = '<span class="fs-tag pull-left" style="background-color: #28a745;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-comment"></small> AI</span></span>';
                    break;
                case 101:
                    $badge = '<span class="fs-tag pull-left" style="background-color: #1565C0;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-globe"></small> Форма</span></span>';
                    break;
                case 102:
                    $badge = '<span class="fs-tag pull-left" style="background-color: #17a2b8;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-headphones"></small> AI voice</span></span>';
                    break;
                case 103:
                    $badge = '<span class="fs-tag pull-left" style="background-color: #546E7A;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-briefcase"></small> Billing</span></span>';
                    break;
                case 10:
                    $badge = '<span class="fs-tag pull-left" style="background-color: #4a76a8;"><span class="fs-tag-name" style="color: white;">VK</span></span>';
                    break;
            }
            if ($badge) echo $badge;
        }, 20);

        // Плашки каналов в просмотре тикета
        \Eventy::addAction('conversation.after_subject', function($conversation, $mailbox) {
            if (self::labelsHidden()) return;
            $channel = (int)$conversation->channel;
            $badge = '';
            switch ($channel) {
                case 200:
                    $badge = '<span class="fs-tag fs-tag-md" style="background-color: #546E7A;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-earphone"></small> Phone</span></span>';
                    break;
                case 100:
                    $badge = '<span class="fs-tag fs-tag-md" style="background-color: #28a745;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-comment"></small> AI</span></span>';
                    break;
                case 101:
                    $badge = '<span class="fs-tag fs-tag-md" style="background-color: #1565C0;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-globe"></small> Форма</span></span>';
                    break;
                case 102:
                    $badge = '<span class="fs-tag fs-tag-md" style="background-color: #17a2b8;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-headphones"></small> AI voice</span></span>';
                    break;
                case 103:
                    $badge = '<span class="fs-tag fs-tag-md" style="background-color: #546E7A;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-briefcase"></small> Billing</span></span>';
                    break;
                case 10:
                    $badge = '<span class="fs-tag fs-tag-md" style="background-color: #4a76a8;"><span class="fs-tag-name" style="color: white;">VK</span></span>';
                    break;
            }
            if ($badge) echo $badge;
        }, 20, 2);

    }

    protected function registerRoutes()
    {
        $routesPath = __DIR__.'/../Http/routes.php';
        if (file_exists($routesPath)) {
            require $routesPath;
        }
    }

    protected function registerCommands()
    {
        $this->commands([
            \Modules\VoiceMail\Console\Commands\ProcessVoicemail::class,
        ]);
    }

    /**
     * Сотрудник скрыл лейблы в своём профиле FreeScout (переключатель SmitUI).
     * Ключ опции: smit_hide_labels_{userId}
     */
    public static function labelsHidden()
    {
        $uid = auth()->id();
        return $uid ? (bool) \Option::get('smit_hide_labels_' . $uid) : false;
    }

    /**
     * Устанавливает поле "Чей" для клиента, только если оно ещё не заполнено.
     * customer_field_id=2: 1=СМИТ, 2=РОБОР
     */
    public static function setCustomerCheyField($customer_id, $value)
    {
        if (!$customer_id) return;
        $exists = \DB::table('customer_customer_field')
            ->where('customer_id', $customer_id)
            ->where('customer_field_id', 2)
            ->exists();
        if (!$exists) {
            \DB::table('customer_customer_field')->insert([
                'customer_id' => $customer_id,
                'customer_field_id' => 2,
                'value' => $value,
            ]);
        }
    }

    public function register()
    {
        $this->registerConfig();
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('voicemail.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'voicemail'
        );
    }

    protected function registerViews()
    {
        $viewPath = resource_path('views/modules/voicemail');
        $sourcePath = __DIR__.'/../Resources/views';
        $this->publishes([$sourcePath => $viewPath],'views');
        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/voicemail';
        }, \Config::get('view.paths')), [$sourcePath]), 'voicemail');
    }

    protected function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(__DIR__.'/../Database/factories');
        }
    }

    public function provides()
    {
        return [];
    }
}
