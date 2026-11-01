<?php

namespace Modules\AIFormLabel\Providers;

use Illuminate\Support\ServiceProvider;
use App\Conversation;

class AIFormLabelServiceProvider extends ServiceProvider
{
    // Custom types: 4 = AI, 5 = Social (VK, Telegram)
    const TYPE_AI = 4;      // Conversation::TYPE_CUSTOM
    const TYPE_SOCIAL = 5;

    // Channel sub-labels for AI type
    const CHANNEL_AI = 100;
    const CHANNEL_FORM = 101;
    const CHANNEL_VOICE = 102;

    public function boot()
    {
        // Register type 5 (social) in Conversation::$types so it appears in filters
        Conversation::$types[self::TYPE_SOCIAL] = 'social';

        $this->hooks();
    }

    public function hooks()
    {
        // Type names for dropdown and display
        \Eventy::addFilter("conversation.type_name", function ($name) {
            if ($name === self::TYPE_AI || $name === 4) {
                return "AI";
            }
            if ($name === self::TYPE_SOCIAL || $name === 5) {
                return "Соцсети";
            }
            return $name;
        }, 20);

        // Channel names (used by isChat() conversations and internal lookups)
        \Eventy::addFilter("channel.name", function ($name, $channel) {
            $map = [
                self::CHANNEL_AI    => "AI",
                self::CHANNEL_FORM  => "Форма",
                self::CHANNEL_VOICE => "AI voice",
            ];
            return $map[$channel] ?? $name;
        }, 20, 2);

        // Register channels in list
        \Eventy::addFilter("channels.list", function ($channels) {
            $channels[self::CHANNEL_AI]    = "AI";
            $channels[self::CHANNEL_FORM]  = "Форма";
            $channels[self::CHANNEL_VOICE] = "AI voice";
            return $channels;
        }, 20);

        // Labels in conversation LIST view
        \Eventy::addAction("conversations_table.after_subject", function ($conversation) {
            $this->renderLabel($conversation, false);
        }, 20);

        // Labels in conversation DETAIL view
        \Eventy::addAction("conversation.after_subject", function ($conversation) {
            $this->renderLabel($conversation, true);
        }, 20, 2);

        // Mobile header links (Календарь для монтажников, Поддержка+Подкл для остальных)
        \Eventy::addAction("javascript", function () {
            $user = auth()->user();
            if (!$user) return;

            // Проверяем поле "Монтаж" (user_field_id=5, value=2 = "Да")
            $isMontazh = \DB::table('user_user_field')
                ->where('user_id', $user->id)
                ->where('user_field_id', 5)
                ->where('value', 2)
                ->exists();

            if ($isMontazh) {
                $links = '<a href="/calendar" style="color:#fff;font-size:13px;font-weight:600;margin-right:8px;text-decoration:none">Календарь</a>';
            } else {
                $links = '<a href="/mailbox/1/51" style="color:#fff;font-size:13px;font-weight:600;margin-right:8px;text-decoration:none">Поддержка</a>'
                       . '<a href="/kanban?kn%5Bboard_id%5D=1" style="color:#fff;font-size:13px;font-weight:600;text-decoration:none">Подключения</a>';
            }

            $html = '<div class="mobile-quick-links" style="display:none;position:absolute;left:60px;top:16px;white-space:nowrap">'
                  . $links . '</div>';

            $css = '@media(max-width:767px){.mobile-quick-links{display:flex!important;align-items:center}}';

            echo 'var _mqls=document.createElement("style");_mqls.textContent=' . json_encode($css) . ';document.head.appendChild(_mqls);';
            echo '$(function(){$(".navbar-header").append(' . json_encode($html) . ');';
            // Скрыть "Управление" и "Клиенты" для монтажников
            if ($isMontazh) {
                echo '$("#app-navbar-collapse .nav.navbar-nav:first > li.dropdown > a").each(function(){var t=$(this).text().trim();if(t==="Управление"||t==="Manage")$(this).parent().hide();});';
                echo '$("#app-navbar-collapse .nav.navbar-nav:first > li > a").each(function(){var t=$(this).text().trim();if(t==="Клиенты"||t==="Customers")$(this).parent().hide();});';
            }
            echo '});';
        }, 20);
    }

    /**
     * Render channel/type label for a conversation
     */
    private function renderLabel($conversation, $isDetail)
    {
        if (self::labelsHidden()) return;

        $type = $conversation->type;
        $channel = $conversation->channel;
        $icon = null;
        $label = null;
        $color = null;

        // AI type (4) — sub-label by channel
        if ($type == self::TYPE_AI) {
            if ($channel == self::CHANNEL_VOICE) {
                $icon = "glyphicon glyphicon-headphones";
                $label = "AI voice";
            } else {
                $icon = "glyphicon glyphicon-console";
                $label = "AI";
            }
        }

        // Form channel (101) — for type=phone (order form) tickets
        if ($channel == self::CHANNEL_FORM && $type != self::TYPE_AI) {
            $icon = "glyphicon glyphicon-globe";
            $label = "Форма";
            $color = "background-color:#5cb85c; color:white;";
        }

        // Social type (5)
        if ($type == self::TYPE_SOCIAL) {
            if ($channel == 10) {
                // VK
                echo $isDetail
                    ? '<span class="fs-tag fs-tag-md" style="margin-left:4px"><a class="fs-tag-name" href="#"><i class="fab fa-vk" style="color:#4680C2"></i> VK</a></span>'
                    : '<span class="fs-tag pull-left" style="margin-left:4px"><span class="fs-tag-name"><i class="fab fa-vk" style="color:#4680C2"></i> VK</span></span>';
                return;
            }
            $icon = "glyphicon glyphicon-comment";
            $label = "Соцсети";
        }

        if (!$label) return;

        $style = $color ? ' style="' . $color . '"' : '';

        if ($isDetail) {
            echo '<span class="fs-tag fs-tag-md" style="margin-left:4px"><a class="fs-tag-name"' . $style . ' href="#"><small class="' . $icon . '"></small> ' . e($label) . '</a></span>';
        } else {
            echo '<span class="fs-tag pull-left" style="margin-left:4px"><span class="fs-tag-name"' . $style . '><small class="' . $icon . '"></small> ' . e($label) . '</span></span>';
        }
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

    public function register()
    {
    }
}
