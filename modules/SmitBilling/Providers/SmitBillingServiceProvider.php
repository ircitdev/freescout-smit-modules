<?php

namespace Modules\SmitBilling\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\SmitBilling\Services\SmitBillingService;

class SmitBillingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerRoutes();
        $this->hooks();
    }

    public function hooks()
    {
        // Кнопка «Пинг роутера» — СКРЫТА (временно отключена)
        // \Eventy::addAction('conversation.before_prev_convs', function ($customer, $conversation, $mailbox) {
        //     ...
        // }, 10, 3);

        // Sidebar виджет — вставляется в боковую панель тикета
        // conversation.after_prev_convs($customer, $conversation, $mailbox)
        \Eventy::addAction('conversation.after_prev_convs', function ($customer, $conversation, $mailbox) {
            if (self::labelsHidden()) return;
            try {
                $billing = new SmitBillingService();
                $data = $billing->getAbonentByConversation($conversation);
                echo \View::make('smitbilling::partials/sidebar', [
                    'billing'      => $data,
                    'conversation' => $conversation,
                ])->render();
            } catch (\Exception $e) {
                \Log::error('[SmitBilling] Sidebar error: ' . $e->getMessage());
            }
        }, 5, 3);

        // JavaScript для кнопок sidebar — event delegation (CSP-совместимо, без onclick)
        \Eventy::addAction('javascript', function () {
            echo '

// Style balance badges in conversation list
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll("span.fs-tag.crm-tag[data-toggle]").forEach(function(el) {
        var title = (el.getAttribute("title") || el.getAttribute("data-original-title") || "").toLowerCase();
        var text = el.textContent.trim();
        if (title.indexOf("\u0431\u0430\u043b\u0430\u043d\u0441") >= 0) {
            var num = parseFloat(text.replace(/[^0-9.\-]/g, ""));
            if (!isNaN(num)) {
                var icon = el.querySelector(".glyphicon");
                if (icon) { icon.className = ""; icon.textContent = "\u20bd"; }
                if (num < 0) {
                    el.style.cssText = "background:#fce4ec!important;border-color:#ef9a9a!important;border-radius:3px;padding:1px 6px;font-style:normal";
                    el.querySelectorAll("*").forEach(function(c){c.style.color="#a11";});
                    el.style.color = "#a11";
                } else {
                    el.style.cssText = "background:#e8f5e9!important;border-color:#a5d6a7!important;border-radius:3px;padding:1px 6px;font-style:normal";
                    el.querySelectorAll("*").forEach(function(c){c.style.color="#2e7d32";});
                    el.style.color = "#2e7d32";
                }
            }
        }
        if (title.indexOf("\u0434\u043e\u0433\u043e\u0432\u043e\u0440") >= 0) {
            var icon = el.querySelector(".glyphicon");
            if (icon) { icon.className = "glyphicon glyphicon-file"; icon.style.color = "#555"; }
            el.style.cssText = "background:transparent!important;border:1px solid #ccc!important;border-radius:3px;padding:1px 6px;font-style:normal";
            el.querySelectorAll("*").forEach(function(c){c.style.color="#555";});
            el.style.color = "#555";
        }
    });
});
function cbEsc(t) { var d = document.createElement("div"); d.textContent = t; return d.innerHTML; }

document.addEventListener("click", function(e) {
    var el = e.target.closest(".cb-refresh-btn");
    if (el) {
        e.preventDefault();
        el.textContent = "⏳ Обновление...";
        fetch("/smitbilling/refresh/" + el.dataset.convId, {method: "POST", headers: {"X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content}})
            .then(function() { location.reload(); })
            .catch(function() { location.reload(); });
        return;
    }

    el = e.target.closest(".cb-ping-btn");
    if (el) {
        e.preventDefault();
        var cid = el.dataset.convId;
        var result = document.getElementById("cb-ping-result-" + cid);
        var origHtml = el.innerHTML;
        el.innerHTML = "<span style=\\"display:inline-block;width:14px;height:14px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:cb-spin .6s linear infinite\\"></span> Пингую...";
        el.style.pointerEvents = "none";
        el.style.opacity = "0.7";
        result.style.display = "none";
        if (!document.getElementById("cb-spin-css")) { var ss=document.createElement("style"); ss.id="cb-spin-css"; ss.textContent="@keyframes cb-spin{to{transform:rotate(360deg)}}"; document.head.appendChild(ss); }
        fetch("/smitbilling/ping/" + cid, {
            method: "POST",
            headers: {"X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content}
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            el.innerHTML = "📡 Пинг роутера";
            el.style.pointerEvents = "auto";
            el.style.opacity = "1";
            result.style.display = "block";
            if (data.success) {
                result.style.borderColor = data.online ? "#c8e6c9" : "#ffcdd2";
                result.style.background = data.online ? "#f1f8e9" : "#fce4ec";
                result.innerHTML = "<span style=\\"font-weight:600\\">" + cbEsc(data.message) + "</span>";
            } else {
                result.style.borderColor = "#e0e0e0";
                result.style.background = "#f5f5f5";
                result.innerHTML = "⚠️ " + cbEsc(data.error || "Ошибка");
            }
        })
        .catch(function() {
            el.innerHTML = "📡 Пинг роутера";
            el.style.pointerEvents = "auto";
            el.style.opacity = "1";
            result.style.display = "block";
            result.style.borderColor = "#e0e0e0";
            result.style.background = "#f5f5f5";
            result.innerHTML = "⚠️ Ошибка соединения";
        });
        return;
    }

    el = e.target.closest(".cb-ai-btn");
    if (el) {
        e.preventDefault();
        var cid = el.dataset.convId;
        var phone = el.dataset.phone || "";
        var result = document.getElementById("cb-ai-result-" + cid);
        el.textContent = "⏳ Анализирую...";
        el.style.pointerEvents = "none";
        result.style.display = "none";
        var threads = document.querySelectorAll(".thread-body .thread-content");
        var ticketText = threads.length > 0 ? threads[0].innerText.substring(0, 2000) : "";
        var subjEl = document.querySelector(".conv-subjtext");
        var ticketSubject = subjEl ? subjEl.innerText.trim() : "";
        fetch("/smitbilling/ai-assist", {
            method: "POST",
            headers: {"Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content},
            body: JSON.stringify({ conversation_id: parseInt(cid), customer_phone: phone, ticket_text: ticketText, ticket_subject: ticketSubject })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            el.innerHTML = "🤖 AI Подсказка";
            el.style.pointerEvents = "auto";
            result.style.display = "block";
            if (data.success) {
                var html = "";
                if (data.diagnosis) { html += "<div style=\\"margin-bottom:4px\\"><strong>🔍 Диагноз:</strong> " + cbEsc(data.diagnosis) + "</div>"; }
                if (data.check_steps && data.check_steps.length) {
                    html += "<div style=\\"margin-bottom:4px\\"><strong>✅ Проверить:</strong><ul style=\\"margin:2px 0;padding-left:16px\\">";
                    data.check_steps.forEach(function(s) { html += "<li>" + cbEsc(s) + "</li>"; });
                    html += "</ul></div>";
                }
                if (data.solution) { html += "<div style=\\"margin-bottom:4px\\"><strong>💡 Решение:</strong> " + cbEsc(data.solution) + "</div>"; }
                if (data.client_response) {
                    html += "<div style=\\"margin-bottom:4px; background:#fff; padding:6px; border-radius:4px; border:1px solid #ddd\\">"
                        + "<strong>💬 Ответ клиенту:</strong><br>" + cbEsc(data.client_response) + "</div>";
                }
                if (data.escalation) { html += "<div style=\\"color:#c0392b; font-weight:bold\\">⚠️ ЭСКАЛАЦИЯ: " + cbEsc(data.escalation) + "</div>"; }
                result.innerHTML = html;
            } else {
                result.innerHTML = "<span style=\\"color:#888\\">⚠️ " + cbEsc(data.error || "Ошибка") + "</span>";
            }
        })
        .catch(function() {
            el.innerHTML = "🤖 AI Подсказка";
            el.style.pointerEvents = "auto";
            result.style.display = "block";
            result.innerHTML = "<span style=\\"color:#888\\">⚠️ Ошибка соединения</span>";
        });
        return;
    }
});
';
        });
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
        $this->registerConfig();
    }

    protected function registerRoutes()
    {
        $routesPath = __DIR__ . '/../Http/routes.php';
        if (file_exists($routesPath)) {
            require $routesPath;
        }
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php', 'smitbilling'
        );
    }

    protected function registerViews()
    {
        $sourcePath = __DIR__ . '/../Resources/views';
        $this->loadViewsFrom($sourcePath, 'smitbilling');
    }

    public function provides()
    {
        return [];
    }
}
