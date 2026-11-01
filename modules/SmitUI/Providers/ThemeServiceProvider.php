<?php

namespace Modules\SmitUI\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * SmitUI v2.0.0 — Theme system for SMIT Billing brand.
 *
 * Подключает tokens.css → theme-overrides.css → dark.css.
 * Инжектит anti-FOUC inline-script, заменяет логотип на SMIT smit_w/smit_d,
 * добавляет лунный toggle в navbar.
 *
 * Хранение: cookie smit_theme + localStorage. Дефолт: auto (prefers-color-scheme).
 */
class ThemeServiceProvider extends ServiceProvider
{
    const MODULE_ALIAS = 'smitui';

    const CONSTRAIN_DEFAULT = '1';  // По умолчанию max-width включён
    const CONSTRAIN_MAX_PX = 1600;
    const GLASS_MODALS_DEFAULT = '1'; // По умолчанию matte-glass для модалок включён

    public function boot()
    {
        // 0) PER-USER OPTIONS: max-width контента + matte-glass модалок
        \Eventy::addAction('javascript', function () {
            $user = auth()->user();
            if (!$user) return;

            $optW = \Option::get('smit_constrain_width_' . $user->id, self::CONSTRAIN_DEFAULT);
            if ($optW === '1' || $optW === 1 || $optW === true) {
                echo '$("body").addClass("smit-constrain-content");';
            }
            $optG = \Option::get('smit_glass_modals_' . $user->id, self::GLASS_MODALS_DEFAULT);
            if ($optG === '1' || $optG === 1 || $optG === true) {
                echo '$("body").addClass("smit-glass-modals");';
            }
        }, 30);

        // 0a) Чекбоксы в профиле: ширина + matte-glass
        \Eventy::addAction('user.edit.before_photo', function ($user) {
            $checkedW = \Option::get('smit_constrain_width_' . $user->id, self::CONSTRAIN_DEFAULT) === '1' ? ' checked' : '';
            $checkedG = \Option::get('smit_glass_modals_' . $user->id, self::GLASS_MODALS_DEFAULT) === '1' ? ' checked' : '';
            echo '<div class="form-group">'
               . '<label class="col-sm-2 control-label">Интерфейс</label>'
               . '<div class="col-sm-6"><div class="controls">'
               . '<input type="hidden" name="smit_constrain_width" value="0">'
               . '<label class="checkbox plain">'
               . '<input type="checkbox" name="smit_constrain_width" value="1"' . $checkedW . '> '
               . '<span class="text-help">Ограничить ширину контент-области (' . self::CONSTRAIN_MAX_PX . 'px, центровка)</span>'
               . '</label>'
               . '<input type="hidden" name="smit_glass_modals" value="0">'
               . '<label class="checkbox plain">'
               . '<input type="checkbox" name="smit_glass_modals" value="1"' . $checkedG . '> '
               . '<span class="text-help">Эффект матового стекла для модальных окон</span>'
               . '</label>'
               . '</div></div></div>';
        }, 25);

        \Eventy::addFilter('user.save_profile', function ($user, $request) {
            \Option::set(
                'smit_constrain_width_' . $user->id,
                $request->input('smit_constrain_width') ? '1' : '0'
            );
            \Option::set(
                'smit_glass_modals_' . $user->id,
                $request->input('smit_glass_modals') ? '1' : '0'
            );
            return $user;
        }, 25, 2);

        // 1) ANTI-FOUC inline-script — priority 1, до любых других layout.head
        \Eventy::addAction('layout.head', function () {
            $theme = $_COOKIE['smit_theme'] ?? 'auto';
            if (!in_array($theme, ['light', 'dark', 'auto'], true)) {
                $theme = 'auto';
            }
            echo "\n<!-- SmitUI theme: anti-FOUC bootstrap -->\n";
            echo "<script nonce=\"" . \Helper::cspNonce() . "\">(function(){var t=" . json_encode($theme) . ';'
                . 'try{var ls=localStorage.getItem("smit_theme");if(ls==="light"||ls==="dark"||ls==="auto")t=ls;}catch(e){}'
                . 'var eff=t;if(t==="auto"){eff=matchMedia("(prefers-color-scheme: dark)").matches?"dark":"light";}'
                . 'document.documentElement.setAttribute("data-theme",eff);'
                . 'document.documentElement.setAttribute("data-theme-mode",t);'
                . 'if(eff==="dark"){document.documentElement.classList.add("dark-theme");}'
                . '})();</script>' . "\n";
        }, 1);

        // 2) STYLESHEETS: tokens → overrides → dark
        \Eventy::addFilter('stylesheets', function ($styles) {
            $base = \Module::getPublicPath(self::MODULE_ALIAS);
            $styles[] = $base . '/css/tokens.css';
            $styles[] = $base . '/css/theme-overrides.css';
            $styles[] = $base . '/css/dark.css';
            return $styles;
        }, 20);

        // 3) JAVASCRIPTS: theme-toggle.js
        \Eventy::addFilter('javascripts', function ($scripts) {
            $base = \Module::getPublicPath(self::MODULE_ALIAS);
            $scripts[] = $base . "/js/theme-toggle.js";
            $scripts[] = $base . "/js/dedupe-tags.js";
            return $scripts;
        }, 20);

        // 4) LOGO: SMIT smit_w / smit_d, switched by data-theme
        //    Заменяем стандартный layout.header_logo
        \Eventy::addFilter('layout.header_logo', function ($logo) {
            $base = \Module::getPublicPath(self::MODULE_ALIAS);
            // Возвращаем светлый по умолчанию — JS свапнет на тёмный если data-theme=dark
            return $base . '/img/smit_w.png';
        }, 30); // priority 30 — позже SmitUIServiceProvider (priority 20) который ставит logo-brand.png

        // 5) Body bottom — расставляем правильный логотип после загрузки + добавляем toggle
        \Eventy::addAction('layout.body_bottom', function () {
            $base = \Module::getPublicPath(self::MODULE_ALIAS);
            $lightLogo = $base . '/img/smit_w.png';
            $darkLogo = $base . '/img/smit_d.png';
            echo "<script nonce=\"" . \Helper::cspNonce() . "\">(function(){"
                . 'var $logo=document.querySelector(".navbar-brand img");'
                . 'if($logo){'
                .   'var swap=function(){var dark=document.documentElement.getAttribute("data-theme")==="dark";'
                .     '$logo.src=dark?' . json_encode($darkLogo) . ':' . json_encode($lightLogo) . ';};'
                .   'swap();'
                .   'var mo=new MutationObserver(function(){swap();});'
                .   'mo.observe(document.documentElement,{attributes:true,attributeFilter:["data-theme"]});'
                . '}'
                . '})();</script>';
        }, 5);

        // 5a) RENAME ".heading" "SMIT34 ..." → "Поддержка клиентов"
        //     + PIN profile dropdown к правому краю
        \Eventy::addAction('javascript', function () {
            echo '$(function(){'
                . 'var $h=$(".section-heading .heading, .heading"); $h.each(function(){var t=$(this).text().trim();if(t.indexOf("SMIT34")===0||t.indexOf("Панель")>=0){$(this).text("Поддержка клиентов");}});'
                . 'var $acc=$(".navbar-nav .dropdown-toggle-account").parent("li");'
                . 'if($acc.length){'
                .   '$acc.addClass("account-pinned");'
                .   'var $right=$(".navbar-nav.navbar-right").first();'
                .   'if($right.length){'
                .     'var $kb=$right.find("a").filter(function(){return /знани|knowledge/i.test($(this).text());}).closest("li");'
                .     'if($kb.length){$acc.insertAfter($kb);}else{$right.prepend($acc);}'
                .   '}else{$(".navbar-nav:first").append($acc);}'
                . '}'
                . '});';
        }, 26);

        // 6) NAVBAR TOGGLE BUTTON — добавляем через JS-action
        \Eventy::addAction('javascript', function () {
            // SVG-иконки: луна + солнце, белые на навбаре
            $sun = '<svg class="theme-icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
            $moon = '<svg class="theme-icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
            $toggleHtml = '<li class="smit-theme-toggle-li">'
                . '<a href="#" class="theme-toggle-btn" title="Сменить тему" aria-label="Сменить тему">'
                . $sun . $moon
                . '</a></li>';
            echo '(function(){var $btn=$(' . json_encode($toggleHtml) . ');'
                . 'var $brand=$(".navbar-header .navbar-brand").first();'
                . 'if($brand.length){$btn.css({listStyle:"none",display:"inline-block",margin:"0px",verticalAlign:"middle"}).insertBefore($brand);}'
                . 'else if($("#search-dt").length){$btn.insertBefore($("#search-dt").parent());}'
                . 'else if($(".navbar-nav.navbar-right").length){$(".navbar-nav.navbar-right").first().prepend($btn);}'
                . '})();';
        }, 25);
    }

    public function register()
    {
    }
}
