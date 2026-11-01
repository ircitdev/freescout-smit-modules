/* SmitUI v2.0.0 — theme toggle (light/dark/auto)
 * Storage: cookie `smit_theme` + localStorage `smit_theme`
 * Default mode: 'auto' — следует prefers-color-scheme
 * Кнопка имеет класс .theme-toggle-btn
 */
(function () {
    'use strict';

    var COOKIE_NAME = 'smit_theme';
    var STORAGE_KEY = 'smit_theme';
    var MAX_AGE = 60 * 60 * 24 * 365; // 1 год

    function readCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]+)'));
        return m ? decodeURIComponent(m[1]) : null;
    }

    function writeCookie(name, value) {
        document.cookie = name + '=' + encodeURIComponent(value) +
            ';path=/;max-age=' + MAX_AGE + ';SameSite=Lax';
    }

    function resolveAuto(mode) {
        if (mode === 'auto') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        return mode;
    }

    function getStoredMode() {
        var c = readCookie(COOKIE_NAME);
        if (c === 'light' || c === 'dark' || c === 'auto') return c;
        try {
            var l = localStorage.getItem(STORAGE_KEY);
            if (l === 'light' || l === 'dark' || l === 'auto') return l;
        } catch (e) {}
        return 'auto';
    }

    var LOGO_LIGHT = '/modules/smitui/img/smit_w.png';
    var LOGO_DARK  = '/modules/smitui/img/smit_d.png';

    function swapLogo(effective) {
        var imgs = document.querySelectorAll('.navbar-brand img, .smit-logo img');
        var src = (effective === 'dark') ? LOGO_DARK : LOGO_LIGHT;
        for (var i = 0; i < imgs.length; i++) {
            // Не трогаем чужие src (которые не наши smit_w/smit_d/logo-brand)
            var cur = imgs[i].getAttribute('src') || '';
            if (cur.indexOf('smit_') >= 0 || cur.indexOf('logo-brand') >= 0 || cur.indexOf('/modules/smitui/img/') >= 0) {
                imgs[i].setAttribute('src', src);
            } else if (cur === '' || imgs[i].dataset.smitLogo === '1') {
                imgs[i].setAttribute('src', src);
                imgs[i].dataset.smitLogo = '1';
            }
        }
    }

    function applyTheme(mode) {
        var effective = resolveAuto(mode);
        document.documentElement.setAttribute('data-theme', effective);
        document.documentElement.setAttribute('data-theme-mode', mode);
        // Для совместимости с биллингом — также проставляем body class
        if (effective === 'dark') {
            document.body.classList.add('dark-theme');
        } else {
            document.body.classList.remove('dark-theme');
        }
        updateToggleIcon(effective);
        convertFsTagsToOutline(effective);
        swapLogo(effective);
        // На случай если navbar отрендерится позже — повторим
        setTimeout(function () { swapLogo(effective); }, 100);
        setTimeout(function () { swapLogo(effective); }, 500);
    }

    // FreeScout fs-tag-name имеет inline background-color из БД (data-color).
    // В dark делаем outline: переносим цвет в border + color, прячем background.
    function convertFsTagsToOutline(theme) {
        var tags = document.querySelectorAll('.fs-tag-name');
        for (var i = 0; i < tags.length; i++) {
            var el = tags[i];
            if (theme === 'dark') {
                // Запомнить оригинальный цвет один раз
                if (!el.hasAttribute('data-orig-bg')) {
                    var orig = el.style.backgroundColor || getComputedStyle(el).backgroundColor;
                    el.setAttribute('data-orig-bg', orig);
                }
                var c = el.getAttribute('data-orig-bg');
                if (c && c !== 'rgba(0, 0, 0, 0)' && c !== 'transparent') {
                    el.style.setProperty('background-color', 'transparent', 'important');
                    el.style.setProperty('color', c, 'important');
                    el.style.setProperty('border', '1px solid ' + c, 'important');
                }
            } else {
                // Восстановить исходный заливочный стиль
                var origBg = el.getAttribute('data-orig-bg');
                if (origBg) {
                    el.style.setProperty('background-color', origBg, 'important');
                    el.style.removeProperty('color');
                    el.style.removeProperty('border');
                }
            }
        }
    }

    // При появлении новых тегов в DOM (AJAX) — конвертировать тоже
    if (typeof MutationObserver !== 'undefined') {
        var tagObserver = new MutationObserver(function (mutations) {
            var theme = document.documentElement.getAttribute('data-theme');
            if (theme !== 'dark') return;
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                for (var j = 0; j < m.addedNodes.length; j++) {
                    var n = m.addedNodes[j];
                    if (n.nodeType !== 1) continue;
                    if (n.classList && n.classList.contains('fs-tag-name')) {
                        convertFsTagsToOutline(theme);
                        return;
                    }
                    if (n.querySelector && n.querySelector('.fs-tag-name')) {
                        convertFsTagsToOutline(theme);
                        return;
                    }
                }
            }
        });
        // Запускаем после DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                tagObserver.observe(document.body, { childList: true, subtree: true });
            });
        } else {
            tagObserver.observe(document.body, { childList: true, subtree: true });
        }
    }

    function updateToggleIcon(effective) {
        var btns = document.querySelectorAll('.theme-toggle-btn');
        for (var i = 0; i < btns.length; i++) {
            btns[i].setAttribute('data-theme-current', effective);
            btns[i].setAttribute('title',
                effective === 'dark' ? 'Светлая тема' : 'Тёмная тема');
        }
    }

    function setTheme(mode) {
        writeCookie(COOKIE_NAME, mode);
        try { localStorage.setItem(STORAGE_KEY, mode); } catch (e) {}
        applyTheme(mode);
    }

    function toggle(e) {
        if (e) e.preventDefault();
        var current = document.documentElement.getAttribute('data-theme') || 'light';
        setTheme(current === 'dark' ? 'light' : 'dark');
    }

    // Реагируем на изменение системной темы, если режим auto
    if (window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        var mqHandler = function () {
            var stored = getStoredMode();
            if (stored === 'auto') applyTheme('auto');
        };
        if (mq.addEventListener) mq.addEventListener('change', mqHandler);
        else if (mq.addListener) mq.addListener(mqHandler); // legacy
    }

    // Делегирование клика — работает для динамически добавленных кнопок
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.theme-toggle-btn') : null;
        if (btn) toggle(e);
    });

    // Применяем сразу — на случай, если inline-script не успел (например cookie был сброшен)
    applyTheme(getStoredMode());

    // Публичный API
    window.SmitTheme = {
        set: setTheme,
        toggle: function () { toggle(); },
        get: function () { return getStoredMode(); },
        getEffective: function () { return document.documentElement.getAttribute('data-theme'); }
    };
})();

// === Mobile reorder: move theme-toggle into navbar-right (collapse) on <=768px ===
(function() {
    var DESKTOP_PARENT_SELECTOR = ".navbar-header"; // или там где он был изначально
    var $tt, $origParent, $origNext;
    function moveToCollapse() {
        var li = document.querySelector(".smit-theme-toggle-li");
        var right = document.querySelector("#app-navbar-collapse .navbar-nav.navbar-right");
        if (!li || !right) return;
        // Сохранить исходное местоположение если ещё не запомнили
        if (!li._smitOrigParent) {
            li._smitOrigParent = li.parentNode;
            li._smitOrigNext = li.nextSibling;
        }
        if (right.firstElementChild !== li) {
            right.insertBefore(li, right.firstElementChild);
        }
    }
    function restoreOriginal() {
        var li = document.querySelector(".smit-theme-toggle-li");
        if (!li || !li._smitOrigParent) return;
        if (li.parentNode !== li._smitOrigParent) {
            li._smitOrigParent.insertBefore(li, li._smitOrigNext);
        }
    }
    function apply() {
        if (window.matchMedia("(max-width: 768px)").matches) moveToCollapse();
        else restoreOriginal();
    }
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", apply);
    } else {
        apply();
    }
    window.addEventListener("resize", apply);
})();
