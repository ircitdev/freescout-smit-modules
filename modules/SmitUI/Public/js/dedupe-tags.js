/* dedupe-tags.js — three behaviours:
 * 1. Hide a coloured .tag if an assignee badge in the same row already has that text.
 *    Words list: смит, робор, voice, войс, smit, robor.
 * 2. Hide a numeric/phone .tag if the row has a Phone badge.
 * 3. Add .fs-tag-negative on balance pills with leading minus.
 */
(function () {
    var DUP_WORDS = ['смит', 'робор', 'voice', 'войс', 'smit', 'robor'];

    function escapeRegex(s) {
        return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    }

    function rowHasPhoneBadge(row) {
        var tags = row.querySelectorAll('.fs-tag, .label, .badge');
        for (var i = 0; i < tags.length; i++) {
            var t = (tags[i].textContent || '').trim().toLowerCase();
            if (t === 'phone' || t === 'телефон' || t.indexOf('phone') === 0) return true;
        }
        return false;
    }

    function isPhoneNumberText(s) {
        var clean = s.replace(/[\s\-()+]/g, '');
        return /^\d{6,}$/.test(clean);
    }

    function dedupeRow(row) {
        var subject = row.querySelector('td.conv-subject');
        if (!subject) return;
        var rowText = '';
        var walker = document.createTreeWalker(row, NodeFilter.SHOW_TEXT, null);
        var n;
        while ((n = walker.nextNode())) {
            var p = n.parentElement;
            if (p && p.classList && p.classList.contains('tag')) continue;
            rowText += ' ' + n.textContent.toLowerCase();
        }
        var hasPhone = rowHasPhoneBadge(row);
        var tags = subject.querySelectorAll('.tag');
        tags.forEach(function (tag) {
            var t = (tag.textContent || '').trim();
            var tl = t.toLowerCase();
            if (DUP_WORDS.indexOf(tl) !== -1) {
                // \b doesn't work with cyrillic in JS — use indexOf.
                // We've already excluded text inside .tag elements, so any
                // occurrence in rowText is from a non-tag element (e.g. badge).
                if (rowText.indexOf(tl) !== -1) {
                    tag.style.display = 'none';
                    return;
                }
            }
            if (hasPhone && isPhoneNumberText(t)) {
                tag.style.display = 'none';
                return;
            }
        });
    }

    function flagNegativeBalances(root) {
        root = root || document;
        var balanceTags = root.querySelectorAll('.fs-tag[title="Баланс"]');
        balanceTags.forEach(function (tag) {
            var clean = (tag.textContent || '').trim().replace(/[^0-9.,\-]/g, '');
            if (/^-/.test(clean)) tag.classList.add('fs-tag-negative');
            else tag.classList.remove('fs-tag-negative');
        });
    }

    function run() {
        var rows = document.querySelectorAll('table.table-conversations tbody tr');
        rows.forEach(dedupeRow);
        flagNegativeBalances();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ajaxComplete(function () {
            setTimeout(run, 50);
        });
    }
})();
