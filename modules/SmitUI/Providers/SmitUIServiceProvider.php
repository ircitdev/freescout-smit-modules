<?php

namespace Modules\SmitUI\Providers;

use Illuminate\Support\ServiceProvider;

class SmitUIServiceProvider extends ServiceProvider
{
    private static $isMontazhnik = null;
    private static $lastCommentData = null; // [conv_id => {date, type}]

    // Монтажник: user_field_id=5, value=2
    const MONTAZH_FIELD_ID = 5;
    const MONTAZH_VALUE_YES = '2';

    // Mailbox IDs
    const MAILBOX_SUPPORT = 1;       // Поддержка клиентов
    const MAILBOX_PODKLUCHENIE = 5;  // Подключение

    // Mailboxes скрытые для всех (служебные)
    const HIDDEN_MAILBOXES = [2, 3, 4, 6, 7, 8];

    // Kanban
    const KANBAN_BOARD_ID = 1;

    // Channel IDs for badges
    const CHANNEL_PHONE = 200;
    const CHANNEL_EMAIL = 1;
    const CHANNEL_AI = 100;
    const CHANNEL_FORM = 101;

    public function boot()
    {
        // ===================================================================
        // 0. LAYOUT: логотип, CSS, скрипты (заменяет правки в app.blade.php)
        // ===================================================================

        // 0a. Кастомный логотип PNG вместо SVG
        \Eventy::addFilter('layout.header_logo', function ($logo) {
            return asset('img/logo-brand.png');
        }, 20);

        // 0b. Подключение custom-colors.css + скрытие футера + стили колонки комментариев
        \Eventy::addAction('layout.head', function () {
            echo '<link rel="stylesheet" href="' . asset('/css/custom-colors.css?v=20260611-green6') . '">';
            echo '<style>.footer{display:none!important}'
               . '.conv-last-comment-th{white-space:nowrap}'
               . '.conv-last-comment-td{white-space:nowrap;font-size:12px}'
               . '.conv-last-comment-td a{color:#999}'
               . '.conv-last-comment-td .lc-icon{margin-right:3px;font-size:10px}'
               . '.conv-last-comment-td.lc-red a{color:#d9534f!important;font-weight:600}'
               . '.conv-last-comment-td.lc-orange a{color:#f0ad4e!important}'
               . '.lc-filter-btn{cursor:pointer;margin-left:2px;font-size:11px;color:#999}'
               . '.lc-filter-btn:hover,.lc-filter-btn.active{color:#337ab7}'
               . '.custom-field-th,.custom-field-td{text-align:center!important;width:20px!important;min-width:20px!important;max-width:20px!important;padding:4px 0!important}'
               . '.smit-assign-compact{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;font-size:11px}'
               // Dashboard: pattern background + frosted glass cards
               . 'body.smit-dashboard.smit-bg-off{background:#f4f6f9}'
               . '@media (prefers-color-scheme: dark){body.smit-dashboard.smit-bg-off{background:#1a2027}}'
               . 'body.smit-dashboard.smit-bg-on{background-size:cover;background-position:center center;background-attachment:fixed;background-repeat:no-repeat}'
               . '@media(max-width:767px){body.smit-dashboard.smit-bg-on{background:#f4f6f9}}'
               . '@media(max-width:767px) and (prefers-color-scheme: dark){body.smit-dashboard.smit-bg-on{background:#1a2027}}'
               . 'body.smit-dashboard .content{background:transparent;min-height:calc(100vh - 50px);margin-top:0;padding-top:0}'
               . 'body.smit-dashboard .section-heading{background:transparent;border:none;margin-top:0}'
               . 'body.smit-dashboard .heading{color:#fff;text-shadow:0 2px 8px rgba(0,0,0,0.3);font-weight:700;font-size:22px}'
               . '@media(max-width:767px){body.smit-dashboard .section-heading{display:none!important}}'
               . 'body.smit-dashboard .dash-card{background:linear-gradient(180deg,#fff 0%,#eef6e0 100%);backdrop-filter:blur(24px) saturate(1.6);-webkit-backdrop-filter:blur(24px) saturate(1.6);border:1px solid rgba(255,255,255,0.5);border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.08);transition:all 0.3s ease;border-top:3px solid transparent;overflow:hidden}'
               . 'body.smit-dashboard .dash-card:hover{transform:translateY(-4px);box-shadow:0 16px 48px rgba(0,0,0,0.14);border-top-color:#4CAF50}'
               . 'body.smit-dashboard .dash-card-content h3 a{color:#2c2c2c}'
               . 'body.smit-dashboard .dash-card-link{color:#555}'
               . 'body.smit-dashboard .dash-card-list-item{color:#333;border-bottom-color:rgba(0,0,0,0.06)}'
               . 'body.smit-dashboard .dash-card-list-item:hover{background:rgba(255,255,255,0.4)}'
               . 'body.smit-dashboard .dash-card-list-item strong{color:#222}'
               . 'body.smit-dashboard .dash-card-list-item strong.has-value{color:#388E3C}'
               . 'body.smit-dashboard .dash-card-list-item .waiting-since{color:#888}'
               . 'body.smit-dashboard .dash-card-footer{background:rgba(255,255,255,0.2);border-top:1px solid rgba(0,0,0,0.05);border-radius:0 0 16px 16px}'
               . 'body.smit-dashboard .dash-card-footer a{color:#555}'
               . 'body.smit-dashboard .dash-card-footer a:hover{color:#333}'
               // Kanban: pattern background (same as dashboard)
               . 'body.smit-kanban{background:url(/modules/smitui/img/pattern.jpg) repeat}'
               . 'body.smit-kanban .content{background:transparent}'
               . 'body.smit-kanban #kn-heading{background:linear-gradient(235deg,rgba(27,94,32,.75) 0%,rgba(46,125,50,.65) 50%,rgba(56,142,60,.55) 100%);backdrop-filter:blur(16px) saturate(1.4);-webkit-backdrop-filter:blur(16px) saturate(1.4);border:1px solid rgba(255,255,255,.15);border-radius:0;box-shadow:0 4px 20px rgba(0,0,0,.41),inset 0 1px 0 rgba(255,255,255,.2);margin:0}'
               . 'body.smit-kanban #kn-heading h1{color:#fff;text-shadow:0 1px 4px rgba(0,0,0,.3);font-weight:700}'
               . 'body.smit-kanban .kn-col{background:transparent;backdrop-filter:hue-rotate(45deg);-webkit-backdrop-filter:hue-rotate(45deg);border-radius:0;border:0;box-shadow:none}'
               . 'body.smit-kanban .kn-col-name{text-align:center;font-size:1.2em}'
               . 'body.smit-kanban a.btn.btn-bordered.btn-xs.kn-add-card{display:none}'
               . 'body.smit-kanban .kn-show-closed{text-align:center}'
               // AI-sessions: pattern background (same as kanban/dashboard)
               . 'body.smit-ai-sessions{background:url(/modules/smitui/img/pattern.jpg) repeat}'
               . 'body.smit-ai-sessions .content{background:transparent;margin-top:0}'
               . 'body.smit-ai-sessions .section-heading{background:linear-gradient(235deg,rgba(27,94,32,.75) 0%,rgba(46,125,50,.65) 50%,rgba(56,142,60,.55) 100%);backdrop-filter:blur(16px) saturate(1.4);-webkit-backdrop-filter:blur(16px) saturate(1.4);border:1px solid rgba(255,255,255,.15);border-radius:0;box-shadow:0 4px 20px rgba(0,0,0,.41),inset 0 1px 0 rgba(255,255,255,.2);margin:0 -15px;padding:10px 15px}'
               . 'body.smit-ai-sessions .section-heading h1{color:#fff;text-shadow:0 1px 4px rgba(0,0,0,.3);font-weight:700}'
               . 'body.smit-ai-sessions .ai-period{background:transparent!important;backdrop-filter:none;-webkit-backdrop-filter:none;box-shadow:none;border:none!important}'
               . 'body.smit-ai-sessions .ai-filters{background:rgba(255,255,255,.75)!important;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);box-shadow:0 2px 8px rgba(0,0,0,.07);border:1px solid rgba(255,255,255,.5)!important}'
               . 'body.smit-ai-sessions .ai-stat{background:rgba(255,255,255,.8)!important;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);box-shadow:0 2px 10px rgba(0,0,0,.07);border:1px solid rgba(255,255,255,.5)!important}'
               . 'body.smit-ai-sessions .table-ai{background:rgba(255,255,255,.6)}'
               . 'body.smit-ai-sessions .col-md-12>.table-responsive{background:rgba(255,255,255,.6);border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.06)}'
               // KnowledgeBase: pattern background + frosted heading
               . 'body.smit-kb{background:url(/modules/smitui/img/pattern.jpg) repeat}'
               . 'body.smit-kb .content{background:transparent;margin-top:0}'
               . 'body.smit-kb .kb-header{background:linear-gradient(235deg,rgba(27,94,32,.75) 0%,rgba(46,125,50,.65) 50%,rgba(56,142,60,.55) 100%)!important;backdrop-filter:blur(16px) saturate(1.4);-webkit-backdrop-filter:blur(16px) saturate(1.4);border:1px solid rgba(255,255,255,.15);border-radius:0!important;box-shadow:0 4px 20px rgba(0,0,0,.41),inset 0 1px 0 rgba(255,255,255,.2)}'
               . 'body.smit-kb .kb-nav{background:rgba(255,255,255,.75)!important;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);box-shadow:0 2px 8px rgba(0,0,0,.08)}'
               . 'body.smit-kb .kb-search-bar{background:rgba(255,255,255,.75)!important;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);box-shadow:0 2px 8px rgba(0,0,0,.06)}'
               . 'body.smit-kb .kb-container{background:rgba(255,255,255,.6);border-radius:0}'
               . 'body.smit-kb .kb-card{background:rgba(255,255,255,.8)!important;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);box-shadow:0 2px 12px rgba(0,0,0,.07);border:1px solid rgba(255,255,255,.5)!important}'
               . 'body.smit-kb .kb-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.12)}'
               // Kanban: AI channel badges — glass volumetric style
               . '.kn-channel-ai .fs-tag-name{background:linear-gradient(135deg,rgba(40,167,69,.85) 0%,rgba(30,130,55,.95) 100%);color:#fff;border:1px solid rgba(255,255,255,.25);box-shadow:0 2px 8px rgba(40,167,69,.3),inset 0 1px 0 rgba(255,255,255,.35);text-shadow:0 1px 2px rgba(0,0,0,.15);border-radius:10px;padding:2px 8px;font-weight:600;font-size:10px}'
               . '.kn-channel-ai-voice .fs-tag-name{background:linear-gradient(135deg,rgba(23,162,184,.85) 0%,rgba(18,130,150,.95) 100%);color:#fff;border:1px solid rgba(255,255,255,.25);box-shadow:0 2px 8px rgba(23,162,184,.3),inset 0 1px 0 rgba(255,255,255,.35);text-shadow:0 1px 2px rgba(0,0,0,.15);border-radius:10px;padding:2px 8px;font-weight:600;font-size:10px}'
               . '.kn-channel-form .fs-tag-name{background:linear-gradient(135deg,rgba(21,101,192,.85) 0%,rgba(16,80,160,.95) 100%);color:#fff;border:1px solid rgba(255,255,255,.25);box-shadow:0 2px 8px rgba(21,101,192,.3),inset 0 1px 0 rgba(255,255,255,.35);text-shadow:0 1px 2px rgba(0,0,0,.15);border-radius:10px;padding:2px 8px;font-weight:600;font-size:10px}'
               . '.kn-channel-phone .fs-tag-name{background:linear-gradient(135deg,rgba(84,110,122,.85) 0%,rgba(66,88,98,.95) 100%);color:#fff;border:1px solid rgba(255,255,255,.25);box-shadow:0 2px 8px rgba(84,110,122,.3),inset 0 1px 0 rgba(255,255,255,.35);text-shadow:0 1px 2px rgba(0,0,0,.15);border-radius:10px;padding:2px 8px;font-weight:600;font-size:10px}'
               . '.kn-card-tags .fs-tag{display:inline-block;margin:0 2px 2px 0;vertical-align:middle}'
               // Channel badge (Phone, AI, etc.) inline with tags in conversation detail
               . '#conv-subject .fs-tag.fs-tag-md{float:left;margin-top:8px;margin-right:8px}'
               // Sidebar: AI-сессия + Пинг роутера в одну строку
               . '.sidebar-btns-row{display:flex;align-items:flex-start;flex-wrap:wrap;gap:0;padding:6px 15px 4px}'
               . '.sidebar-btns-row>div{padding:0!important}'
               . '.sidebar-btns-row>div:last-of-type{margin-left:auto}'
               . '</style>';
        }, 20);

// 0b2. Random dashboard background image — per user opt-in.
        //   smit_show_bg_{userId} != '0' (default ON). На каждый запрос:
        //   1) Через filter body.class добавляем 'smit-bg-on' (либо 'smit-bg-off')
        //   2) Через action layout.head инжектим <style> с background-image для
        //      body.smit-dashboard.smit-bg-on (рендерится только когда оба класса есть)
        //   3) <picture> с WebP+JPG fallback нельзя в CSS — поэтому только WebP
        //      (поддерживается во всех современных браузерах, FreeScout требует Chrome 79+)
        \Eventy::addAction('body.class', function () {
            $user = auth()->user();
            if (!$user) return;
            $opt = \Option::get('smit_show_bg_' . $user->id, '1');
            echo ' ' . ($opt === '0' ? 'smit-bg-off' : 'smit-bg-on');
        }, 20);

        \Eventy::addAction('layout.head', function () {
            $user = auth()->user();
            if (!$user) return;
            $opt = \Option::get('smit_show_bg_' . $user->id, '1');
            if ($opt === '0') return;
            // seed = userId * 7 + day-of-year → фон у юзера меняется ежедневно
            $bgN = (int)(($user->id * 7 + (int)date('z')) % 5) + 1;  // 1..5
            $bgLight = asset('modules/smitui/img/bg/bg' . $bgN . '_light.webp');
            $bgDark  = asset('modules/smitui/img/bg/bg' . $bgN . '_dark.webp');
            // CSS: light по умолчанию, dark через prefers-color-scheme или html[data-theme="dark"]
            // Высокая специфичность (0,1,3): html body.smit-dashboard.smit-bg-on
            // чтобы перебить html:not([data-theme="dark"]) body.smit-dashboard в theme-overrides
            echo '<style>'
                . 'html body.smit-dashboard.smit-bg-on,'
                . 'html:not([data-theme="dark"]) body.smit-dashboard.smit-bg-on{'
                .   'background-image:url(' . $bgLight . ')!important;'
                .   'background-size:cover!important;'
                .   'background-position:center center!important;'
                .   'background-attachment:fixed!important;'
                .   'background-repeat:no-repeat!important;'
                .   'background-color:#f4f6f9!important;'
                . '}'
                . 'html[data-theme="dark"] body.smit-dashboard.smit-bg-on,'
                . 'body.dark-theme.smit-dashboard.smit-bg-on{'
                .   'background-image:url(' . $bgDark . ')!important;'
                .   'background-size:cover!important;'
                .   'background-position:center center!important;'
                .   'background-attachment:fixed!important;'
                .   'background-repeat:no-repeat!important;'
                .   'background-color:#1a2027!important;'
                . '}'
                . '@media (max-width:767px){'
                .   'html body.smit-dashboard.smit-bg-on,'
                .   'html:not([data-theme="dark"]) body.smit-dashboard.smit-bg-on{'
                .     'background-image:none!important;'
                .     'background-color:#f4f6f9!important;'
                .   '}'
                .   'html[data-theme="dark"] body.smit-dashboard.smit-bg-on,'
                .   'body.dark-theme.smit-dashboard.smit-bg-on{'
                .     'background-image:none!important;'
                .     'background-color:#1a2027!important;'
                .   '}'
                . '}'
                . '</style>';
        }, 25);


        // 0c. Скрипт AI-ассистента
        \Eventy::addAction('layout.body_bottom', function () {
            $nonce = \Helper::cspNonceAttr();
            echo '<script src="/js/freescout_ai_button.js?v=20260611-green6" ' . $nonce . '></script>';
        }, 20);

        // ===================================================================
        // 1. DASHBOARD: скрыть служебные ящики + редирект монтажников
        // ===================================================================



        // 1b. Скрыть служебные ящики с dashboard (priority 20)
        // Монтажники: только Календарь (скрыть Поддержка + Подключение)
        \Eventy::addFilter('dashboard.mailboxes', function ($mailboxes) {
            $hidden = self::HIDDEN_MAILBOXES;
            if (self::isMontazhnik()) {
                $hidden[] = self::MAILBOX_SUPPORT;
                $hidden[] = self::MAILBOX_PODKLUCHENIE;
            }
            return $mailboxes->filter(function ($m) use ($hidden) {
                return !in_array($m->id, $hidden);
            });
        }, 20);

        // ===================================================================
        // 2. MENU: скрыть ящики из навбара
        // ===================================================================

        \Eventy::addFilter('menu.mailboxes', function ($mailboxes) {
            $hidden = self::HIDDEN_MAILBOXES;
            // Монтажники: дополнительно скрыть "Поддержка клиентов"
            if (self::isMontazhnik()) {
                $hidden[] = self::MAILBOX_SUPPORT;
            }
            return $mailboxes->filter(function ($m) use ($hidden) {
                return !in_array($m->id, $hidden);
            })->values();
        }, 20);

        // ===================================================================
        // 3. MAILBOX URL: монтажник → "Подключение" открывает "Мои"
        // ===================================================================

        \Eventy::addFilter('mailbox.url', function ($url, $mailbox) {
            if (self::isMontazhnik() && $mailbox->id == self::MAILBOX_PODKLUCHENIE) {
                $user = auth()->user();
                if ($user) {
                    $folder = \App\Folder::where('mailbox_id', self::MAILBOX_PODKLUCHENIE)
                        ->where('type', \App\Folder::TYPE_MINE)
                        ->where('user_id', $user->id)
                        ->first();
                    if ($folder) {
                        return route('mailboxes.view.folder', [
                            'id' => self::MAILBOX_PODKLUCHENIE,
                            'folder_id' => $folder->id,
                        ]);
                    }
                }
            }
            return $url;
        }, 20, 2);

        // ===================================================================
        // 4. MENU APPEND: "Заявки" для монтажников
        // ===================================================================

        \Eventy::addAction('menu.append', function () {
            if (self::isMontazhnik()) {
                $user = auth()->user();
                $userId = $user ? $user->id : 0;
                $url = '/kanban?' . http_build_query([
                    'kn' => [
                        'board_id' => self::KANBAN_BOARD_ID,
                        'filters' => [
                            'kn_column_id' => '3|4',
                            'status' => '1|2|3',
                            'user_id' => $userId,
                        ]
                    ]
                ]);
                echo '<li id="menu-montazh-zayavki"><a href="' . htmlspecialchars($url) . '"><i class="glyphicon glyphicon-list-alt"></i> Заявки</a></li>';
            }
        }, 15);

        // ===================================================================
        // 5. JAVASCRIPT: единый блок CSS/JS инъекций
        //    NOTE: @action('javascript') выполняется ВНУТРИ <script> — без HTML тегов!
        // ===================================================================

        \Eventy::addAction('javascript', function () {
            $user = auth()->user();
            if (!$user) return;

            $isMontazh = self::isMontazhnik();

            // Dashboard glassmorphism: add body class
            echo 'if($(".dash-cards").length){$("body").addClass("smit-dashboard");}';
            // Kanban: pattern background
            echo 'if($("#kn-board").length){$("body").addClass("smit-kanban");}';
            // AI-sessions: pattern background
            echo 'if($(".table-ai").length){$("body").addClass("smit-ai-sessions");}';
            // KnowledgeBase: pattern background
            echo 'if($(".kb-wrap").length){$("body").addClass("smit-kb");}';

            // --- CSS (inject via JS) ---
            $css = '.customer-country,.conv-customer-country{display:none!important}'
                 . '.footer{display:none!important}';

            if ($isMontazh) {
                $css .= '.sidebar-2col{display:none!important}'
                      . '.content-2col{margin-left:0!important;width:100%!important}'
                      . '#kn-heading{display:none!important}'
                      . 'a.btn.btn-bordered.btn-xs.kn-add-card{display:none!important}'
                      . '#conv-toolbar{display:none!important}';
            }

            echo '$("head").append("<style>' . $css . '<\/style>");';

            // --- Mobile quick links ---
            if ($isMontazh) {
                $userId = $user->id;
                $zayavkiUrl = '/kanban?' . http_build_query([
                    'kn' => [
                        'board_id' => self::KANBAN_BOARD_ID,
                        'filters' => [
                            'kn_column_id' => '3|4',
                            'status' => '1|2|3',
                            'user_id' => $userId,
                        ]
                    ]
                ]);
                $links = '<a href="/calendar" style="color:#fff;font-size:13px;font-weight:600;margin-right:8px;text-decoration:none">Календарь</a>'
                       . '<a href="' . htmlspecialchars($zayavkiUrl) . '" style="color:#fff;font-size:13px;font-weight:600;text-decoration:none">Заявки</a>';
            } else {
                $links = '<a href="/mailbox/1/51" style="color:#fff;font-size:13px;font-weight:600;margin-right:8px;text-decoration:none">Поддержка</a>'
                       . '<a href="/kanban?kn%5Bboard_id%5D=1" style="color:#fff;font-size:13px;font-weight:600;text-decoration:none">Подключения</a>';
            }

            $mobileHtml = '<div class="mobile-quick-links" style="display:none;position:absolute;left:60px;top:16px;white-space:nowrap">'
                        . $links . '</div>';

            $mobileCss = '@media(max-width:767px){.mobile-quick-links{display:flex!important;align-items:center}}';
            echo 'var _mqls=document.createElement("style");_mqls.textContent=' . json_encode($mobileCss) . ';document.head.appendChild(_mqls);';

            // --- $(function(){}) block ---
            echo '$(function(){';

            // Append mobile links to header
            echo '$(".navbar-header").append(' . json_encode($mobileHtml) . ');';

            // Монтажник: скрыть меню Ящик, Подключения, Клиенты
            if ($isMontazh) {
                echo '$(".navbar-nav .dropdown-mailboxes").hide();';
                $hideDropdowns = json_encode(['Управление', 'Manage']);
                $hideLinks = json_encode(['Подключения', 'Клиенты']);
                echo 'var hd=' . $hideDropdowns . ',hl=' . $hideLinks . ';';
                echo '$(".navbar-nav > li.dropdown").not(".dropdown-mailboxes").each(function(){var t=$(this).find(".dropdown-toggle").text().trim();for(var i=0;i<hd.length;i++){if(t.indexOf(hd[i])!==-1){$(this).hide();break;}}});';
                echo '$(".navbar-nav > li > a").each(function(){var t=$(this).text().trim();for(var i=0;i<hl.length;i++){if(t===hl[i]){$(this).parent("li").hide();break;}}});';
            }

            // Для ВСЕХ: скрыть "Translate" из меню Управление
            echo '$(".dropdown-menu a[href*=\'/translations\']").parent("li").hide();';

            // Для ВСЕХ: "Управление" → шестерёнка в правом навбаре
            echo 'var $mDD=null;$("#app-navbar-collapse .nav.navbar-nav:first > li.dropdown > a.dropdown-toggle").each(function(){var t=$(this).text().trim();if(t.indexOf("Управление")!==-1||t.indexOf("Manage")!==-1){$mDD=$(this).parent();return false;}});';
            echo 'if($mDD&&$mDD.length){$mDD.find("> a.dropdown-toggle").html(\'<i class="glyphicon glyphicon-cog"></i>\').addClass("dropdown-toggle-icon");var $sLi=$("#search-dt").parent();$mDD.detach().insertBefore($sLi);$mDD.find(".dropdown-menu").css({"right":"0","left":"auto"});}';

            // Иконка «?» (База знаний) рядом с поиском
            echo 'var $helpLi=$("<li><a href=\'/knowledgebase\' title=\'База знаний\' style=\'padding:15px 10px\'><i class=\'glyphicon glyphicon-question-sign\'></i></a></li>");';
            echo 'var $sLi2=$("#search-dt").parent();if($sLi2.length){$helpLi.insertAfter($sLi2);}';

            // Мобильная группировка иконок: шестерёнка, лупа, вопрос — в ряд с серым фоном
            echo 'if($mDD&&$mDD.length){';
            echo '  var $searchLi=$("#search-dt").parent();';
            echo '  $mDD.addClass("smit-icon-item");';
            echo '  $searchLi.addClass("smit-icon-item");';
            echo '  $helpLi.addClass("smit-icon-item");';
            // На мобильном: обернуть в div-контейнер с серым фоном
            echo '  if(window.innerWidth<768){';
            echo '    var $iconRow=$("<div class=\'smit-icon-row\'></div>");';
            echo '    $mDD.before($iconRow);';
            echo '    $iconRow.append($mDD).append($searchLi).append($helpLi);';
            echo '  }';
            echo '}';

            // Мобильное меню: шестерёнка — прямая ссылка /app-settings без dropdown
            echo 'if($mDD&&$mDD.length&&window.innerWidth<768){';
            echo '  $mDD.removeClass("dropdown");';
            echo '  $mDD.find(".dropdown-menu").remove();';
            echo '  $mDD.find("> a").attr("href","/app-settings").removeAttr("data-toggle").removeClass("dropdown-toggle");';
            echo '}';

            // Мобильное меню: профиль наверх — прямая ссылка без dropdown
            echo 'var $accLi=$("#app-navbar-collapse .navbar-right > li").filter(function(){return $(this).find(".dropdown-toggle-account").length>0;});';
            echo 'if($accLi.length && window.innerWidth<768){';
            echo '  var profileUrl="/users/profile/' . $user->id . '";';
            echo '  var userName=$accLi.find(".nav-user").text().trim()||$accLi.find(".dropdown-toggle-account").text().trim();';
            echo '  var $avatar=$accLi.find(".person-photo").first().clone();';
            echo '  $accLi.empty().append($("<a>").attr("href",profileUrl).append($avatar).append(" "+userName));';
            echo '  $accLi.removeClass("dropdown").addClass("smit-profile-item");';
            echo '  $("#app-navbar-collapse .navbar-nav:first").prepend($accLi);';
            echo '}';

            // Мобильное меню: иконки к пунктам + переименования + доп. пункты
            echo 'var $navItems=$("#app-navbar-collapse .navbar-nav:first > li");';
            // Иконки к существующим пунктам
            echo '$navItems.each(function(){';
            echo '  var $a=$(this).find("> a").first(), t=$a.text().trim();';
            echo '  if(t==="Подключения"){$a.html(\'<i class="glyphicon glyphicon-road"></i> Подключения\');}';
            echo '  else if(t==="Клиенты"||t==="Customers"){$a.html(\'<i class="glyphicon glyphicon-user"></i> Клиенты\');}';
            echo '  else if(t==="Календарь"||t==="Calendar"){$a.html(\'<i class="glyphicon glyphicon-calendar"></i> Календарь\');}';
            echo '});';
            // Иконка для dropdown "Ящик" / "Mailbox"
            echo '$navItems.filter(".dropdown-mailboxes").find("> a.dropdown-toggle").each(function(){';
            echo '  var $a=$(this),t=$a.text().trim();';
            echo '  if(t){$a.prepend(\'<i class="glyphicon glyphicon-inbox"></i> \');}';
            echo '});';
            // Иконка для dropdown "Отчёты" / "Reports"
            echo '$navItems.filter(".dropdown").not(".dropdown-mailboxes").each(function(){';
            echo '  var $a=$(this).find("> a.dropdown-toggle"),t=$a.text().trim();';
            echo '  if(t.indexOf("Отчёты")!==-1||t.indexOf("Report")!==-1){';
            echo '    $a.prepend(\'<i class="glyphicon glyphicon-stats"></i> \');';
            echo '  }';
            echo '});';
            // Добавить пункты "AI-сессии" и "Агенты" (только мобильное меню)
            echo 'if(window.innerWidth<768){';
            echo '  var $reportsLi=$navItems.filter(function(){var t=$(this).find("> a").first().text().trim();return t.indexOf("Отчёты")!==-1||t.indexOf("Report")!==-1;});';
            echo '  var $aiLi=$("<li><a href=\'/ai-sessions\'><i class=\'glyphicon glyphicon-comment\'></i> AI-сессии</a></li>");';
            echo '  var $agLi=$("<li><a href=\'/users\'><i class=\'glyphicon glyphicon-user\'></i> Агенты</a></li>");';
            echo '  if($reportsLi.length){$agLi.insertAfter($reportsLi);$aiLi.insertAfter($reportsLi);}';
            echo '}';

            // Иконки для пунктов dropdown "Управление" (десктоп)
            echo 'if($mDD&&$mDD.length){';
            echo '  $mDD.find(".dropdown-menu > li > a").each(function(){';
            echo '    var $a=$(this);';
            echo '    if($a.find(".glyphicon").length) return;'; // уже есть иконка — пропустить
            echo '    var t=$a.text().trim(),icon="";';
            echo '    if(t.indexOf("Настройки")!==-1||t.indexOf("Settings")!==-1) icon="glyphicon-wrench";';
            echo '    else if(t.indexOf("Ящики")!==-1||t.indexOf("Mailboxes")!==-1) icon="glyphicon-inbox";';
            echo '    else if(t.indexOf("Теги")!==-1||t.indexOf("Tags")!==-1) icon="glyphicon-tags";';
            echo '    else if(t.indexOf("Агенты")!==-1||t.indexOf("Users")!==-1) icon="glyphicon-user";';
            echo '    else if(t.indexOf("Модули")!==-1||t.indexOf("Modules")!==-1) icon="glyphicon-th";';
            echo '    else if(t.indexOf("Журналы")!==-1||t.indexOf("Logs")!==-1) icon="glyphicon-list-alt";';
            echo '    else if(t.indexOf("Система")!==-1) icon="glyphicon-cog";';
            echo '    if(icon){$a.prepend(\'<i class="glyphicon \'+icon+\'" style="margin-right:6px;font-size:12px;width:16px;text-align:center;display:inline-block"></i>\');}';
            echo '  });';
            echo '}';

            // CSS для мобильного: крупный шрифт + горизонтальный ряд иконок + профиль
            echo 'var _ics=document.createElement("style");_ics.textContent='
               . json_encode(
                   '@media(max-width:767px){'
                   // Убрать белую линию между шапкой и меню
                   . '#app-navbar-collapse{border-top:none!important;margin-top:0!important;box-shadow:none!important}'
                   . '#app-navbar-collapse.in,.navbar-collapse.collapsing{padding:0!important;border:none!important}'
                   . '.navbar-default .navbar-collapse{border-color:transparent!important}'
                   . '.navbar-nav{margin-top:0!important}'
                   // Профиль — тёмный блок наверху, прямая ссылка без dropdown
                   . 'li.smit-profile-item{background:#1a252f!important;margin:0!important;padding:0!important;border-bottom:2px solid #4CAF50}'
                   . 'li.smit-profile-item>a{'
                   .   'color:#fff!important;font-size:20px!important;font-weight:600!important;'
                   .   'padding:16px 20px!important;line-height:1.3!important;display:flex!important;align-items:center!important;text-decoration:none!important'
                   . '}'
                   . 'li.smit-profile-item .person-photo{width:36px;height:36px;border-radius:50%;border:2px solid rgba(255,255,255,0.4);margin-right:10px;flex-shrink:0}'
                   // Крупный шрифт и отступы для ВСЕХ пунктов мобильного меню
                   . '#app-navbar-collapse .navbar-nav>li>a{'
                   .   'font-size:18px!important;padding:14px 24px!important;line-height:1.4!important'
                   . '}'
                   . '#app-navbar-collapse .navbar-nav>li>a>.glyphicon{'
                   .   'font-size:18px!important;margin-right:8px!important;width:22px!important;text-align:center!important;display:inline-block!important'
                   . '}'
                   // Три иконки (шестерёнка, лупа, вопрос) — в горизонтальный ряд
                   . '.smit-icon-row{display:flex!important;justify-content:center!important;align-items:center!important;gap:0;background:#eef1f3!important;margin:0 -15px!important;padding:8px 0!important;border-top:1px solid #dde1e5;border-bottom:1px solid #dde1e5}'
                   . '.smit-icon-row>li.smit-icon-item{display:block!important;float:none!important;flex:1;text-align:center}'
                   . '.smit-icon-row>li.smit-icon-item>a,.smit-icon-row>li.smit-icon-item>a.dropdown-toggle{'
                   .   'display:block!important;padding:12px 0!important;font-size:36px!important;line-height:1!important;color:#4CAF50!important'
                   . '}'
                   . '.smit-icon-row>li.smit-icon-item>a:hover{color:#2E7D32!important}'
                   . '.smit-icon-row>li.smit-icon-item .glyphicon{font-size:36px!important;margin-right:0!important}'
                   // Уведомления (колокольчик) — dropdown на всю ширину, впритык к шапке
                   // На мобильном FreeScout перемещает .web-notifications в .navbar-header
                   . '.navbar-header .dropdown.web-notifications>.dropdown-menu{'
                   .   'position:fixed!important;left:0!important;right:0!important;width:100vw!important;'
                   .   'top:50px!important;margin:0!important;border-radius:0!important;border-left:none!important;border-right:none!important;'
                   .   'box-shadow:0 4px 12px rgba(0,0,0,0.15)!important;max-height:70vh!important;overflow-y:auto!important'
                   . '}'
                   . '}'
               )
               . ';document.head.appendChild(_ics);';

            // --- Кнопка фильтра «Без ответа» ---
            echo '$(document).on("click","#lc-filter-no-reply",function(){';
            echo 'var u=new URL(window.location.href);';
            echo 'if(u.searchParams.get("smit_filter")==="no_reply"){u.searchParams.delete("smit_filter");}';
            echo 'else{u.searchParams.set("smit_filter","no_reply");}';
            echo 'window.location.href=u.toString();';
            echo '});';

            // --- Компактные колонки «Срочность» и «Тип» ---
            // Заголовки: замена текста на иконки
            echo '$(".table-conversations th.custom-field-th").each(function(){';
            echo '  var $s=$(this).find(".conv-col-sort"),txt=$s.text().trim();';
            echo '  var ar="";if(txt.indexOf("↑")!==-1)ar=" ↑";if(txt.indexOf("↓")!==-1)ar=" ↓";';
            echo '  if(txt.indexOf("рочность")!==-1){$s.html("🔥"+ar);$(this).css("text-align","center");}';
            echo '  if(txt.indexOf("Тип")!==-1&&txt.replace(/[↑↓]/g,"").trim().length<=3){$s.html("👤"+ar);$(this).css("text-align","center");}';
            echo '});';
            // Ячейки: замена по содержимому (без привязки к индексу колонки)
            echo '$(".table-conversations td.custom-field-td").each(function(){';
            echo '  var $td=$(this),$a=$td.find("a"),v=$a.text().trim();';
            echo '  if(v==="")return;';
            echo '  if(v.indexOf("Пожар")!==-1){$a.html("🔥");$td.css({"text-align":"center","width":"40px"});}';
            echo '  else if(v==="Высокая"){$a.html("❗");$td.css({"text-align":"center","width":"40px"});}';
            echo '  else if(v==="Обычная"){$a.html("🔘");$td.css({"text-align":"center","width":"40px"});}';
            echo '  else if(v.indexOf("Физ")!==-1){$a.html("👤");$td.css({"text-align":"center","width":"40px"});}';
            echo '  else if(v.indexOf("Юр")!==-1){$a.html("💼");$td.css({"text-align":"center","width":"40px"});}';
            echo '});';

            // --- Сужение колонки «Назначено» ---
            echo 'var $allTh=$(".table-conversations thead th");';
            echo '$allTh.each(function(){';
            echo '  var t=$(this).text().trim();';
            echo '  if(t.indexOf("Назначено")!==-1||t.indexOf("Assigned")!==-1){';
            echo '    var ci=$(this).index();';
            echo '    $(this).css({"width":"140px","max-width":"110px","white-space":"nowrap","overflow":"hidden"});';
            echo '    $(".table-conversations colgroup col").eq(ci).css("width","140px");';
            echo '    $(".table-conversations tbody tr").each(function(){';
            echo '      $(this).children("td").eq(ci).addClass("smit-assign-compact");';
            echo '    });';
            echo '  }';
            echo '});';

            echo '});'; // end $(function)
        }, 20);

        // ===================================================================
        // 6. КОЛОНКА «Послед. комментарий» в таблице тикетов
        // ===================================================================

        // 6a. Preload: загрузить дату и тип последнего thread для всех тикетов на странице
        \Eventy::addFilter('conversations_table.preload_table_data', function ($conversations) {
            if ($conversations && count($conversations)) {
                $ids = $conversations->pluck('id')->toArray();
                if (!empty($ids)) {
                    $rows = \DB::table('threads as t')
                        ->join(\DB::raw('(SELECT conversation_id, MAX(id) as max_id FROM threads GROUP BY conversation_id) tm'), 't.id', '=', 'tm.max_id')
                        ->whereIn('t.conversation_id', $ids)
                        ->select('t.conversation_id', 't.created_at', 't.type')
                        ->get();
                    self::$lastCommentData = [];
                    foreach ($rows as $row) {
                        self::$lastCommentData[$row->conversation_id] = [
                            'date' => $row->created_at,
                            'type' => (int)$row->type,
                        ];
                    }
                }
            }
            return $conversations;
        }, 15);

        // 6b. <col> — ширина колонки
        \Eventy::addAction('conversations_table.col_before_conv_number', function () {
            echo '<col style="width:95px">';
        }, 15);

        // 6c. <th> — заголовок с сортировкой + кнопка фильтра «Без ответа»
        \Eventy::addAction('conversations_table.th_before_conv_number', function () {
            $sorting = ['sort_by' => 'date', 'order' => 'desc'];
            if (isset($_REQUEST['sorting'])) {
                $sorting['sort_by'] = $_REQUEST['sorting']['sort_by'] ?? 'date';
                $sorting['order'] = $_REQUEST['sorting']['order'] ?? 'desc';
            }
            // По умолчанию (без явной сортировки) — считаем last_comment активным
            $isActive = ($sorting['sort_by'] === 'last_comment') || !isset($_REQUEST['sorting']);
            $sortBy = isset($_REQUEST['sorting']) ? $sorting['sort_by'] : 'last_comment';
            $order = ($sortBy === 'last_comment') ? ($sorting['order'] ?? 'desc') : 'desc';
            $arrow = '';
            if ($isActive && $sortBy === 'last_comment') {
                $arrow = ($order === 'asc') ? ' ↓' : ' ↑';
            }
            $filterActive = ($_REQUEST['smit_filter'] ?? '') === 'no_reply';
            $filterClass = $filterActive ? 'lc-filter-btn active' : 'lc-filter-btn';
            $filterTitle = $filterActive ? 'Показать все' : 'Без ответа оператора';
            echo '<th class="conv-last-comment-th">'
               . '<div style="display:inline-flex;align-items:center;white-space:nowrap;gap:3px">'
               . '<span class="conv-col-sort" data-sort-by="last_comment" data-order="' . $order . '" title="Комментарий"><small class="glyphicon glyphicon-comment"></small>' . $arrow . '</span>'
               . '<span class="' . $filterClass . '" id="lc-filter-no-reply" title="' . $filterTitle . '"><small class="glyphicon glyphicon-filter"></small></span>'
               . '</div></th>';
        }, 15);

        // 6d. <td> — иконка типа + относительное время + цвет просрочки
        \Eventy::addAction('conversations_table.td_before_conv_number', function ($conversation) {
            $data = self::$lastCommentData[$conversation->id] ?? null;
            $text = '';
            $title = '';
            $cssClass = 'lc-normal';
            $icon = '';
            if ($data) {
                $date = $data['date'];
                $type = $data['type'];
                $text = self::shortTimeDiff($date);
                $carbon = \Carbon\Carbon::parse($date);
                $user = auth()->user();
                if ($user) {
                    $carbon->setTimezone($user->timezone);
                }
                $title = $carbon->format('d.m.Y H:i');

                // Иконка по типу thread
                if ($type == 1) {
                    $icon = '<small class="glyphicon glyphicon-user lc-icon"></small>';
                } elseif ($type == 2) {
                    $icon = '<small class="glyphicon glyphicon-share-alt lc-icon"></small>';
                } elseif ($type == 3) {
                    $icon = '<small class="glyphicon glyphicon-pushpin lc-icon"></small>';
                }

                // Цвет просрочки — только если ждёт клиент (type=1)
                if ($type == 1) {
                    $diffHours = \Carbon\Carbon::parse($date)->diffInMinutes(\Carbon\Carbon::now()) / 60;
                    if ($diffHours > 2) {
                        $cssClass = 'lc-red';
                    } elseif ($diffHours > 1) {
                        $cssClass = 'lc-orange';
                    }
                }
            }
            echo '<td class="conv-last-comment-td ' . $cssClass . '"><a href="' . $conversation->url() . '" title="' . $title . '">' . $icon . $text . '</a></td>';
        }, 15);

        // 6e. Сортировка + фильтр «Без ответа» в SQL
        // НЕ используем conversations.table_sorting — ядро пытается ORDER BY $sort_by как колонку.
        // Вместо этого добавляем ORDER BY через folder.conversations_query (выполняется раньше ядра).
        \Eventy::addFilter('folder.conversations_query', function ($query) {
            // Сортировка по last_comment (явная или дефолтная)
            $needSort = false;
            $order = 'desc';
            if (isset($_REQUEST['sorting']) && ($_REQUEST['sorting']['sort_by'] ?? '') === 'last_comment') {
                $needSort = true;
                $order = ($_REQUEST['sorting']['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
            } elseif (!isset($_REQUEST['sorting'])) {
                // Дефолтная сортировка
                $needSort = true;
            }
            if ($needSort) {
                $query = $query->leftJoin(
                    \DB::raw('(SELECT conversation_id, MAX(created_at) as last_comment_at FROM threads GROUP BY conversation_id) smit_lc'),
                    'smit_lc.conversation_id', '=', 'conversations.id'
                );
                $query = $query->orderBy('smit_lc.last_comment_at', $order);
            }

            // Фильтр «Без ответа» — только тикеты где последний thread от клиента
            if (($_REQUEST['smit_filter'] ?? '') === 'no_reply') {
                $query->whereIn('conversations.id', function ($sub) {
                    $sub->select('t.conversation_id')
                        ->from('threads as t')
                        ->whereRaw('t.id = (SELECT MAX(t2.id) FROM threads t2 WHERE t2.conversation_id = t.conversation_id)')
                        ->where('t.type', 1);
                });
            }

            return $query;
        }, 15);

        // CHANNEL BADGES: перенесены в VoiceMailServiceProvider (единственный источник)

        // Sidebar: flex-обёртка для кнопок AI-сессия (prio 9) + Пинг роутера (prio 10)
        \Eventy::addAction('conversation.before_prev_convs', function () {
            echo '<div class="sidebar-btns-row">';
        }, 8, 3);
        \Eventy::addAction('conversation.before_prev_convs', function () {
            echo '</div>';
        }, 11, 3);

        // ===================================================================
        // 7. ПРОФИЛЬ: персональные переключатели интерфейса
        //    smit_hide_labels_{userId} — плашки каналов (VoiceMail/AIFormLabel),
        //      биллинг-виджет (SmitBilling), плашки клиента в строке (Crm)
        //    smit_hide_tags_{userId}   — теги тикетов (модуль Tags)
        //    Каждый модуль читает свою опцию по auth()->id().
        // ===================================================================

        // 7a. Чекбоксы в форме профиля сотрудника
        \Eventy::addAction('user.edit.before_photo', function ($user) {
            $chLabels = \Option::get('smit_hide_labels_' . $user->id) ? ' checked' : '';
            $chTags   = \Option::get('smit_hide_tags_' . $user->id) ? ' checked' : '';
            $chBg     = \Option::get('smit_show_bg_' . $user->id, '1') === '0' ? '' : ' checked';
            echo '<div class="form-group">'
               . '<label class="col-sm-2 control-label">Интерфейс</label>'
               . '<div class="col-sm-6"><div class="controls">'
               . '<input type="hidden" name="smit_show_bg" value="0">'
               . '<label class="checkbox plain">'
               . '<input type="checkbox" name="smit_show_bg" value="1"' . $chBg . '> '
               . '<span class="text-help">Показывать фоновое изображение на главной странице (Панель управления)</span>'
               . '</label>'
               . '<input type="hidden" name="smit_hide_labels" value="0">'
               . '<label class="checkbox plain">'
               . '<input type="checkbox" name="smit_hide_labels" value="1"' . $chLabels . '> '
               . '<span class="text-help">Скрыть плашки каналов (Phone, AI, VK…), биллинг-виджет и данные клиента в строке</span>'
               . '</label>'
               . '<input type="hidden" name="smit_hide_tags" value="0">'
               . '<label class="checkbox plain">'
               . '<input type="checkbox" name="smit_hide_tags" value="1"' . $chTags . '> '
               . '<span class="text-help">Скрыть теги тикетов (смит, робор, нет-интернета…)</span>'
               . '</label>'
               . '</div></div></div>';
        }, 20);

        // 7b. Сохранение переключателей вместе с профилем
        \Eventy::addFilter('user.save_profile', function ($user, $request) {
            \Option::set('smit_show_bg_' . $user->id, $request->input('smit_show_bg') ? '1' : '0');
            \Option::set('smit_hide_labels_' . $user->id, $request->input('smit_hide_labels') ? '1' : '0');
            \Option::set('smit_hide_tags_' . $user->id, $request->input('smit_hide_tags') ? '1' : '0');
            return $user;
        }, 20, 2);
    }

    public function register()
    {
    }



    /**
     * Определить тёмная тема активна или нет по cookie от theme-toggle.js.
     * Если cookie нет — light. Затем при показе body_top JS может скорректировать через MutationObserver.
     */
    private static function detectDarkMode()
    {
        $cookie = $_COOKIE['smit_theme'] ?? null;
        return $cookie === 'dark';
    }

    /**
     * Short relative time: "5 мин", "2 ч", "3 д", "1 нед", "2 мес"
     */
    private static function shortTimeDiff($date)
    {
        $now = \Carbon\Carbon::now();
        $dt = \Carbon\Carbon::parse($date);
        $mins = $dt->diffInMinutes($now);
        if ($mins < 1) return 'сейчас';
        if ($mins < 60) return $mins . ' мин';
        $hours = intval($mins / 60);
        if ($hours < 24) return $hours . ' ч';
        $days = intval($hours / 24);
        if ($days < 7) return $days . ' д';
        $weeks = intval($days / 7);
        if ($days < 30) return $weeks . ' нед';
        $months = intval($days / 30);
        return $months . ' мес';
    }

    /**
     * Check if current user is montazhnik (cached)
     */
    private static function isMontazhnik()
    {
        if (self::$isMontazhnik === null) {
            $user = auth()->user();
            if (!$user) {
                return false;
            }
            self::$isMontazhnik = \DB::table('user_user_field')
                ->where('user_id', $user->id)
                ->where('user_field_id', self::MONTAZH_FIELD_ID)
                ->where('value', self::MONTAZH_VALUE_YES)
                ->exists();
        }
        return self::$isMontazhnik;
    }
}
