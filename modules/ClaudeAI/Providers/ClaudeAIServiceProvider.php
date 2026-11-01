<?php

namespace Modules\ClaudeAI\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ClaudeAI\Services\ClaudeService;

class ClaudeAIServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerRoutes();
        $this->hooks();
    }

    public function register()
    {
        $this->registerConfig();
    }

    protected function hooks()
    {
        // Menu item under "Управление"
        \Eventy::addAction('menu.manage.after_mailboxes', function () {
            if (auth()->user() && auth()->user()->isAdmin()) {
                echo \View::make('claudeai::partials.menu')->render();
            }
        }, 36);

        // Operator hint button in ticket sidebar
        if (\Option::get('claudeai_operator_hint') === '1') {
            \Eventy::addAction('conversation.before_prev_convs', function ($customer, $conversation, $mailbox) {
                try {
                    $convId = $conversation->id;
                    $phone = $customer ? ($customer->getPhones()[0]['value'] ?? '') : '';

                    echo '<div style="padding:6px 15px 10px;display:flex;gap:6px;flex-wrap:nowrap;align-items:center">'
                       . '<a href="#" class="claudeai-hint-btn" data-conv-id="' . $convId . '" data-phone="' . e($phone) . '" '
                       . 'style="display:inline-flex;align-items:center;gap:5px;white-space:nowrap;'
                       . 'background:linear-gradient(135deg,#43b77a 0%,#369968 100%);'
                       . 'color:#fff;text-decoration:none;padding:5px 14px;border-radius:16px;'
                       . 'font-size:12px;font-weight:600;transition:all .3s ease;'
                       . 'box-shadow:0 2px 6px rgba(67,183,122,.3)">'
                       . '<i class="glyphicon glyphicon-flash" style="font-size:11px"></i> Нейрответ</a>'
                       . '<a href="#" class="claudeai-draft-btn" data-conv-id="' . $convId . '" '
                       . 'style="display:inline-flex;align-items:center;gap:5px;white-space:nowrap;'
                       . 'background:transparent;'
                       . 'color:#43b77a;text-decoration:none;padding:5px 14px;border-radius:16px;'
                       . 'border:1px solid #43b77a;'
                       . 'font-size:12px;font-weight:600;transition:all .3s ease">'
                       . '<i class="glyphicon glyphicon-pencil" style="font-size:11px"></i> Черновик</a>'
                       . '<div id="claudeai-result-' . $convId . '" style="font-size:12px;margin-top:8px;display:none;'
                       . 'background:#fffbeb;border:1px solid #fbbf24;border-radius:6px;padding:10px;'
                       . 'max-height:400px;overflow-y:auto;line-height:1.5"></div>'
                       . '</div>';
                } catch (\Exception $e) {
                    \Log::error('[ClaudeAI] Sidebar button error: ' . $e->getMessage());
                }
            }, 8, 3);
        }

        // JavaScript for sidebar buttons
        \Eventy::addAction('javascript', function () {
            echo $this->getJavaScript();
        });
    }

    protected function getJavaScript(): string
    {
        return '
document.addEventListener("click", function(e) {
    var el = e.target.closest(".claudeai-hint-btn");
    if (el) {
        e.preventDefault();
        var cid = el.dataset.convId;
        var phone = el.dataset.phone || "";
        var result = document.getElementById("claudeai-result-" + cid);
        var origHtml = el.innerHTML;
        el.innerHTML = "⏳ Думаю...";
        el.style.pointerEvents = "none";
        el.style.opacity = "0.7";
        result.style.display = "none";

        var threads = document.querySelectorAll(".thread-body .thread-content");
        var ticketText = threads.length > 0 ? threads[0].innerText.substring(0, 3000) : "";
        var subjEl = document.querySelector(".conv-subjtext");
        var ticketSubject = subjEl ? subjEl.innerText.trim() : "";

        fetch("/claudeai/operator-hint", {
            method: "POST",
            headers: {"Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content},
            body: JSON.stringify({ conversation_id: parseInt(cid), customer_phone: phone, ticket_text: ticketText, ticket_subject: ticketSubject })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            el.innerHTML = origHtml;
            el.style.pointerEvents = "auto";
            el.style.opacity = "1";
            result.style.display = "block";
            if (data.success) {
                result.innerHTML = "<pre style=\\"white-space:pre-wrap;font-family:inherit;margin:0;font-size:12px\\">" + escHtml(data.response) + "</pre>";
            } else {
                result.innerHTML = "⚠️ " + escHtml(data.error || "Ошибка");
            }
        })
        .catch(function() {
            el.innerHTML = origHtml;
            el.style.pointerEvents = "auto";
            el.style.opacity = "1";
            result.style.display = "block";
            result.innerHTML = "⚠️ Ошибка соединения";
        });
        return;
    }

    el = e.target.closest(".claudeai-draft-btn");
    if (el) {
        e.preventDefault();
        var cid = el.dataset.convId;
        var result = document.getElementById("claudeai-result-" + cid);
        var origHtml = el.innerHTML;
        el.innerHTML = "⏳ Пишу...";
        el.style.pointerEvents = "none";

        fetch("/claudeai/auto-reply", {
            method: "POST",
            headers: {"Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content},
            body: JSON.stringify({ conversation_id: parseInt(cid) })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            el.innerHTML = origHtml;
            el.style.pointerEvents = "auto";
            result.style.display = "block";
            if (data.success) {
                result.innerHTML = "<div style=\\"margin-bottom:6px;font-weight:600\\">✍️ Черновик ответа:</div>"
                    + "<div style=\\"background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:8px;margin-bottom:6px\\">"
                    + escHtml(data.response) + "</div>"
                    + "<button onclick=\\"navigator.clipboard.writeText(this.parentElement.querySelector(\\x27div:nth-child(2)\\x27).innerText);this.textContent=\\x27✅ Скопировано\\x27\\" "
                    + "style=\\"background:#059669;color:#fff;border:none;padding:4px 12px;border-radius:4px;cursor:pointer;font-size:11px\\">📋 Копировать</button>";
            } else {
                result.innerHTML = "⚠️ " + escHtml(data.error || "Ошибка");
            }
        })
        .catch(function() {
            el.innerHTML = origHtml;
            el.style.pointerEvents = "auto";
            result.style.display = "block";
            result.innerHTML = "⚠️ Ошибка соединения";
        });
        return;
    }
});

function escHtml(t) { var d = document.createElement("div"); d.textContent = t; return d.innerHTML; }
';
    }

    protected function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'claudeai');
    }

    protected function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'claudeai');
    }

    public function provides()
    {
        return [];
    }
}
