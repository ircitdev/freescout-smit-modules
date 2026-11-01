@extends('layouts.app')

@section('title', 'База знаний')

@section('stylesheets')
<style>
.kb-wrap {
  --kb-primary: #059669;
  --kb-primary-light: #ecfdf5;
  --kb-red: #dc2626;
  --kb-red-bg: #fef2f2;
  --kb-orange: #d97706;
  --kb-orange-bg: #fffbeb;
  --kb-yellow: #f59e0b;
  --kb-yellow-bg: #fffbeb;
  --kb-green: #059669;
  --kb-green-bg: #ecfdf5;
  --kb-gray: #64748b;
  --kb-gray-bg: #f8fafc;
  --kb-border: #e2e8f0;
  --kb-bg: #fff;
  --kb-text: #0f172a;
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  color: var(--kb-text);
  line-height: 1.5;
}

.kb-wrap .kb-header {
  background: linear-gradient(135deg, #059669, #0d9488);
  color: #fff;
  padding: 24px 32px;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 8px rgba(0,0,0,.15);
  border-radius: 8px 8px 0 0;
}
.kb-wrap .kb-header h1 { font-size: 22px; font-weight: 600; margin: 0; text-align: center; }
.kb-wrap .kb-header .subtitle { font-size: 13px; opacity: .85; margin-top: 4px; text-align: center; }

.kb-wrap .kb-nav {
  background: #fff;
  border-bottom: 1px solid var(--kb-border);
  padding: 0 32px;
  display: flex;
  justify-content: center;
  gap: 0;
  position: sticky;
  top: 50px;
  z-index: 99;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.kb-wrap .kb-nav a {
  padding: 12px 20px;
  text-decoration: none;
  color: var(--kb-gray);
  font-size: 14px;
  font-weight: 500;
  border-bottom: 3px solid transparent;
  transition: all .2s;
  white-space: nowrap;
}
.kb-wrap .kb-nav a:hover { color: var(--kb-primary); background: var(--kb-primary-light); }
.kb-wrap .kb-nav a.active { color: var(--kb-primary); border-bottom-color: var(--kb-primary); }
.kb-wrap .kb-nav a.kb-external { margin-left: auto; }
.kb-wrap .kb-nav a.kb-external::after { content: " \2197"; font-size: 0.85em; opacity: 0.7; margin-left: 2px; }

.kb-wrap .kb-search-bar {
  padding: 16px 32px;
  background: #fff;
  border-bottom: 1px solid var(--kb-border);
  position: sticky;
  top: 98px;
  z-index: 98;
  text-align: center;
}
.kb-wrap .kb-search-bar input {
  width: 100%;
  max-width: 600px;
  display: inline-block;
  padding: 10px 16px 10px 40px;
  border: 1px solid var(--kb-border);
  border-radius: 24px;
  font-size: 15px;
  outline: none;
  background: var(--kb-gray-bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") 14px center no-repeat;
  transition: all .2s;
}
.kb-wrap .kb-search-bar input:focus { border-color: var(--kb-primary); background-color: #fff; box-shadow: 0 0 0 3px rgba(5,150,105,.15); }

.kb-wrap .kb-container { max-width: 1400px; margin: 0 auto; padding: 24px 32px 60px; }

.kb-wrap .kb-section { margin-bottom: 32px; }
.kb-wrap .kb-section-title {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid var(--kb-primary);
  display: flex;
  align-items: center;
  gap: 8px;
}

.kb-wrap .scenarios { display: flex; flex-direction: column; gap: 16px; }

.kb-wrap .kb-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid var(--kb-border);
  overflow: hidden;
  transition: box-shadow .2s;
}
.kb-wrap .kb-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.kb-wrap .kb-card.hidden { display: none; }

.kb-wrap .kb-card-header {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  cursor: pointer;
  gap: 12px;
  user-select: none;
}
.kb-wrap .kb-card-header:hover { background: var(--kb-gray-bg); }

.kb-wrap .kb-card-id {
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  background: var(--kb-primary);
  border-radius: 6px;
  padding: 2px 8px;
  min-width: 40px;
  text-align: center;
}

.kb-wrap .kb-card-title { font-weight: 600; font-size: 15px; flex: 1; }

.kb-wrap .kb-card-problem {
  font-size: 13px;
  color: var(--kb-gray);
  flex: 2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.kb-wrap .kb-badge {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 12px;
  white-space: nowrap;
}
.kb-wrap .kb-badge-high { background: var(--kb-red-bg); color: var(--kb-red); }
.kb-wrap .kb-badge-medium { background: var(--kb-orange-bg); color: var(--kb-orange); }
.kb-wrap .kb-badge-low { background: var(--kb-green-bg); color: var(--kb-green); }
.kb-wrap .kb-badge-none { background: var(--kb-gray-bg); color: var(--kb-gray); }
.kb-wrap .kb-badge-audience {
  background: var(--kb-primary-light);
  color: var(--kb-primary);
}

.kb-wrap .kb-chevron {
  width: 20px;
  height: 20px;
  transition: transform .2s;
  color: var(--kb-gray);
}
.kb-wrap .kb-card.open .kb-chevron { transform: rotate(180deg); }

.kb-wrap .kb-card-body {
  display: none;
  padding: 0 20px 20px;
  border-top: 1px solid var(--kb-border);
}
.kb-wrap .kb-card.open .kb-card-body { display: block; }

.kb-wrap .kb-steps {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
  margin-top: 16px;
}

.kb-wrap .kb-step {
  background: var(--kb-gray-bg);
  border-radius: 8px;
  padding: 14px 16px;
}
.kb-wrap .kb-step-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--kb-primary);
  text-transform: uppercase;
  margin-bottom: 6px;
}
.kb-wrap .kb-step-content {
  font-size: 13px;
  white-space: pre-line;
}

.kb-wrap .kb-card-keywords {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.kb-wrap .kb-keyword {
  background: var(--kb-primary-light);
  color: var(--kb-primary);
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
}

.kb-wrap .kb-card-note {
  margin-top: 12px;
  padding: 10px 14px;
  background: #fffde7;
  border-left: 3px solid var(--kb-yellow);
  border-radius: 0 6px 6px 0;
  font-size: 13px;
}

/* Decision Tree */
.kb-wrap .tree-table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--kb-border);
}
.kb-wrap .tree-table th {
  background: var(--kb-primary);
  color: #fff;
  padding: 12px 16px;
  font-size: 13px;
  font-weight: 600;
  text-align: left;
}
.kb-wrap .tree-table td {
  padding: 12px 16px;
  font-size: 13px;
  border-bottom: 1px solid var(--kb-border);
  vertical-align: top;
}
.kb-wrap .tree-table tr:last-child td { border-bottom: none; }
.kb-wrap .tree-table tr:hover td { background: var(--kb-primary-light); }
.kb-wrap .tree-table .step-num {
  font-weight: 700;
  color: var(--kb-primary);
  text-align: center;
  font-size: 16px;
}
.kb-wrap .tree-yes { color: var(--kb-green); font-weight: 500; }
.kb-wrap .tree-no { color: var(--kb-red); font-weight: 500; }
.kb-wrap .tree-ref {
  display: inline-block;
  background: var(--kb-primary-light);
  color: var(--kb-primary);
  padding: 1px 6px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 12px;
  cursor: pointer;
}

/* Dialog Scripts */
.kb-wrap .dialogs { display: flex; flex-direction: column; gap: 12px; }

.kb-wrap .dialog-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid var(--kb-border);
  overflow: hidden;
}
.kb-wrap .dialog-card.hidden { display: none; }

.kb-wrap .dialog-header {
  display: flex;
  align-items: center;
  padding: 14px 20px;
  cursor: pointer;
  gap: 12px;
}
.kb-wrap .dialog-header:hover { background: var(--kb-gray-bg); }

.kb-wrap .dialog-id {
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  background: #10b981;
  border-radius: 6px;
  padding: 2px 8px;
}

.kb-wrap .dialog-title { font-weight: 600; font-size: 14px; flex: 1; }

.kb-wrap .dialog-channel {
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 12px;
  background: var(--kb-green-bg);
  color: var(--kb-green);
  font-weight: 500;
}

.kb-wrap .dialog-body {
  display: none;
  padding: 0 20px 20px;
  border-top: 1px solid var(--kb-border);
}
.kb-wrap .dialog-card.open .dialog-body { display: block; }

.kb-wrap .dialog-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-top: 16px;
}

.kb-wrap .dialog-block {
  border-radius: 8px;
  padding: 14px 16px;
  font-size: 13px;
  white-space: pre-line;
  position: relative;
}
.kb-wrap .dialog-block.client {
  background: #ecfdf5;
  border-left: 3px solid var(--kb-primary);
}
.kb-wrap .dialog-block.internal {
  background: #fff3e0;
  border-left: 3px solid var(--kb-orange);
}
.kb-wrap .dialog-block-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 6px;
}
.kb-wrap .dialog-block.client .dialog-block-label { color: var(--kb-primary); }
.kb-wrap .dialog-block.internal .dialog-block-label { color: var(--kb-orange); }

.kb-wrap .copy-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  background: rgba(255,255,255,.8);
  border: 1px solid var(--kb-border);
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 11px;
  cursor: pointer;
  color: var(--kb-gray);
  transition: all .2s;
}
.kb-wrap .copy-btn:hover { background: #fff; color: var(--kb-primary); border-color: var(--kb-primary); }
.kb-wrap .copy-btn.copied { background: var(--kb-green); color: #fff; border-color: var(--kb-green); }

.kb-wrap .tone-badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
  background: #f0fdfa;
  color: #0d9488;
}

/* Back to top */
.kb-wrap .back-top {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--kb-primary);
  color: #fff;
  border: none;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0,0,0,.2);
  font-size: 20px;
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 100;
  transition: transform .2s;
}
.kb-wrap .back-top:hover { transform: scale(1.1); }
.kb-wrap .back-top.visible { display: flex; }

/* Search counter */
.kb-wrap .kb-search-counter {
  text-align: center;
  font-size: 13px;
  color: var(--kb-gray);
  padding: 6px 0 0;
  display: none;
}
.kb-wrap .kb-search-counter.visible { display: block; }
.kb-wrap .kb-search-counter strong { color: var(--kb-primary); }

/* Filter bar */
.kb-wrap .kb-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
  margin-bottom: 16px;
}
.kb-wrap .kb-filters-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--kb-gray);
  text-transform: uppercase;
  margin-right: 4px;
}
.kb-wrap .kb-filter-btn {
  font-size: 12px;
  padding: 4px 12px;
  border-radius: 16px;
  border: 1px solid var(--kb-border);
  background: #fff;
  color: var(--kb-gray);
  cursor: pointer;
  transition: all .2s;
  font-weight: 500;
}
.kb-wrap .kb-filter-btn:hover { border-color: var(--kb-primary); color: var(--kb-primary); }
.kb-wrap .kb-filter-btn.active { background: var(--kb-primary); color: #fff; border-color: var(--kb-primary); }

/* Expand/Collapse button */
.kb-wrap .kb-toggle-all {
  font-size: 12px;
  color: var(--kb-primary);
  cursor: pointer;
  background: none;
  border: 1px solid var(--kb-primary);
  border-radius: 16px;
  padding: 4px 12px;
  transition: all .2s;
  margin-left: auto;
}
.kb-wrap .kb-toggle-all:hover { background: var(--kb-primary); color: #fff; }

/* Section title flex for toggle button */
.kb-wrap .kb-section-title { justify-content: space-between; }

/* Tree visual improvements */
.kb-wrap .tree-table tr:nth-child(even) td { background: var(--kb-gray-bg); }
.kb-wrap .tree-table tr:hover td { background: var(--kb-primary-light) !important; }
.kb-wrap .tree-yes { color: var(--kb-green); font-weight: 600; }
.kb-wrap .tree-no { color: var(--kb-red); font-weight: 600; }
.kb-wrap .tree-table .step-num {
  background: var(--kb-primary);
  color: #fff;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
}

/* Permalink highlight */
.kb-wrap .kb-card.kb-highlighted { box-shadow: 0 0 0 3px var(--kb-primary), 0 4px 12px rgba(5,150,105,.2); }

/* Print */
@media print {
  .kb-wrap .kb-header { position: static; }
  .kb-wrap .kb-nav, .kb-wrap .kb-search-bar, .kb-wrap .back-top { display: none !important; }
  .kb-wrap .kb-card-body, .kb-wrap .dialog-body { display: block !important; }
  .kb-wrap .kb-card, .kb-wrap .dialog-card { break-inside: avoid; }
}

/* Tablet */
@media (max-width: 1024px) {
  .kb-wrap .kb-steps { grid-template-columns: repeat(2, 1fr); }
}

/* Mobile */
@media (max-width: 768px) {
  .kb-wrap { margin: 0 !important; width: 100% !important; max-width: 100vw; overflow-x: hidden; }
  .kb-wrap .kb-header { padding: 16px; border-radius: 0; position: static; }
  .kb-wrap .kb-header h1 { font-size: 18px; line-height: 1.3; word-wrap: break-word; }
  .kb-wrap .kb-header .subtitle { font-size: 11px; }
  .kb-wrap .kb-nav { padding: 0; overflow-x: auto; -webkit-overflow-scrolling: touch; position: static; top: auto; box-shadow: none; }
  .kb-wrap .kb-nav a { padding: 10px 14px; font-size: 13px; flex-shrink: 0; }
  .kb-wrap .kb-search-bar { padding: 10px 12px; position: static; top: auto; }
  .kb-wrap .kb-search-bar input { font-size: 14px; padding: 10px 14px 10px 36px; max-width: 100%; box-sizing: border-box; }
  .kb-wrap .kb-container { padding: 12px 10px 80px; }
  .kb-wrap .kb-section-title { font-size: 17px; flex-wrap: wrap; }
  .kb-wrap .kb-filters { gap: 4px; }
  .kb-wrap .kb-filter-btn { font-size: 11px; padding: 3px 8px; }
  .kb-wrap .kb-toggle-all { font-size: 11px; padding: 3px 8px; }
  .kb-wrap .kb-card-header { padding: 12px 14px; gap: 8px; flex-wrap: wrap; }
  .kb-wrap .kb-card-id { font-size: 12px; padding: 2px 6px; min-width: 36px; }
  .kb-wrap .kb-card-title { font-size: 14px; flex: 1 1 auto; min-width: 0; }
  .kb-wrap .kb-card-problem { display: none; }
  .kb-wrap .kb-badge { font-size: 10px; padding: 2px 7px; }
  .kb-wrap .kb-badge-audience { display: none; }
  .kb-wrap .kb-chevron { width: 18px; height: 18px; }
  .kb-wrap .kb-card-body { padding: 0 12px 14px; }
  .kb-wrap .kb-steps { grid-template-columns: 1fr; gap: 10px; margin-top: 12px; }
  .kb-wrap .kb-step { padding: 12px; }
  .kb-wrap .kb-step-label { font-size: 11px; }
  .kb-wrap .kb-step-content { font-size: 12px; }
  .kb-wrap .kb-card-keywords { gap: 4px; }
  .kb-wrap .kb-keyword { font-size: 10px; padding: 1px 6px; }
  .kb-wrap .kb-card-note { font-size: 12px; padding: 8px 10px; }
  .kb-wrap .tree-table { display: block; border: none; }
  .kb-wrap .tree-table thead { display: none; }
  .kb-wrap .tree-table tbody { display: flex; flex-direction: column; gap: 10px; }
  .kb-wrap .tree-table tr { display: flex; flex-direction: column; background: #fff; border: 1px solid var(--kb-border); border-radius: 10px; padding: 12px; gap: 6px; }
  .kb-wrap .tree-table tr:hover td { background: transparent; }
  .kb-wrap .tree-table td { padding: 0; border: none; font-size: 13px; }
  .kb-wrap .tree-table td:before { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--kb-gray); margin-bottom: 2px; }
  .kb-wrap .tree-table .step-num { text-align: left; font-size: 14px; }
  .kb-wrap .tree-table .step-num:before { content: ''; }
  .kb-wrap .tree-table td:nth-child(2):before { content: 'Проверка'; }
  .kb-wrap .tree-table td:nth-child(3):before { content: 'Если ДА'; }
  .kb-wrap .tree-table td:nth-child(4):before { content: 'Если НЕТ'; }
  .kb-wrap .tree-table td:nth-child(5):before { content: 'Сценарий'; }
  .kb-wrap .dialog-header { padding: 12px 14px; gap: 8px; flex-wrap: wrap; }
  .kb-wrap .dialog-id { font-size: 12px; }
  .kb-wrap .dialog-title { font-size: 13px; flex: 1 1 auto; min-width: 0; }
  .kb-wrap .tone-badge { font-size: 10px; }
  .kb-wrap .dialog-channel { font-size: 10px; }
  .kb-wrap .dialog-body { padding: 0 12px 14px; }
  .kb-wrap .dialog-columns { grid-template-columns: 1fr; gap: 10px; margin-top: 12px; }
  .kb-wrap .dialog-block { padding: 12px; font-size: 12px; }
  .kb-wrap .dialog-block-label { font-size: 10px; }
  .kb-wrap .copy-btn { padding: 3px 8px; font-size: 10px; }
  .kb-wrap .back-top { bottom: 16px; right: 16px; width: 40px; height: 40px; font-size: 18px; }
}

@media (max-width: 380px) {
  .kb-wrap .kb-header h1 { font-size: 16px; }
  .kb-wrap .kb-nav a { padding: 8px 10px; font-size: 12px; }
  .kb-wrap .kb-card-header { padding: 10px 10px; }
  .kb-wrap .kb-card-title { font-size: 13px; }
}

/* Analytics tables */
.kb-table { width:100%; border-collapse:collapse; font-size:14px; }
.kb-table th { background:var(--kb-gray-bg); font-weight:600; text-align:left; padding:10px 14px; border-bottom:2px solid var(--kb-border); white-space:nowrap; }
.kb-table td { padding:10px 14px; border-bottom:1px solid var(--kb-border); }
.kb-table tr:hover td { background:var(--kb-primary-light); }
.kb-table .kb-scenario-link { color:var(--kb-primary); text-decoration:none; font-weight:600; }
.kb-table .kb-scenario-link:hover { text-decoration:underline; }

/* Override FreeScout content area padding */
.kb-wrap { margin: -20px; box-sizing: border-box; }
@media (max-width: 768px) {
  .content:has(.kb-wrap) { padding: 0 !important; margin: 0 !important; }
  .content-2col:has(.kb-wrap) { padding: 0 !important; }
}
</style>
@endsection

@section('content')
<div class="kb-wrap">

<div class="kb-header">
  <h1>База знаний</h1>
  <div class="subtitle">Версия 2.0 | На основе анализа обращений в поддержку</div>
</div>

<div class="kb-nav">
  <a href="#scenarios" class="active" data-kb-tab="scenarios">Сценарии решения</a>
  <a href="#tree" data-kb-tab="tree">Дерево диагностики</a>
  <a href="#dialogs" data-kb-tab="dialogs">Шаблоны ответов</a>
  <a href="https://docs.billing.smit34.ru/pages/helpdesk.html" target="_blank" rel="noopener" class="kb-external">Работа с сервисом</a>
</div>

<div class="kb-search-bar">
  <input type="text" id="kb-search" placeholder="Поиск по ключевым словам, проблемам, сценариям...">
  <div class="kb-search-counter" id="kb-search-counter"></div>
</div>

<div class="kb-container">

<!-- ===== РАЗДЕЛ 1: СЦЕНАРИИ ===== -->
<div id="kb-section-scenarios" class="kb-section">
  <div class="kb-section-title">Типовые сценарии решения проблем <button class="kb-toggle-all" data-target="scenarios">Развернуть все</button></div>

  <div class="kb-filters" id="kb-filters">
    <span class="kb-filters-label">Аудитория:</span>
    <button class="kb-filter-btn active" data-filter="audience" data-value="all">Все</button>
    <button class="kb-filter-btn" data-filter="audience" data-value="bot">Бот</button>
    <button class="kb-filter-btn" data-filter="audience" data-value="operator">Оператор</button>
    <span class="kb-filters-label" style="margin-left:12px">Приоритет:</span>
    <button class="kb-filter-btn active" data-filter="priority" data-value="all">Все</button>
    <button class="kb-filter-btn" data-filter="priority" data-value="high">Высокий</button>
    <button class="kb-filter-btn" data-filter="priority" data-value="medium">Средний</button>
    <button class="kb-filter-btn" data-filter="priority" data-value="low">Низкий</button>
  </div>

  <div class="scenarios" id="kb-scenarios-list">

    <!-- S01 -->
    <div class="kb-card" data-id="S01" data-priority="high" data-audience="bot operator" data-keywords="нет инета, пропал интернет, не работает, отключился, отсутствует, нет соединения, нет интернета">
      <div class="kb-card-header">
        <span class="kb-card-id">S01</span>
        <span class="kb-card-title">Нет интернета</span>
        <span class="kb-card-problem">Нет интернета / Пропал интернет / Не работает интернет</span>
        <span class="kb-badge kb-badge-high">Высокий</span>
        <span class="kb-badge kb-badge-audience">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. Назовите номер договора или адрес
2. Когда пропал интернет?
3. Горят ли индикаторы на оборудовании?
4. Перезагружали ли роутер?</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Проверить наличие сессии в биллинге
2. Проверить баланс абонента
3. Проверить статус порта на коммутаторе
4. Проверить наличие массовой аварии по адресу</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">ЕСЛИ сессии нет + баланс "+":
&#8594; Попросить перезагрузить роутер (выключить из розетки на 30 сек)
&#8594; Проверить кабель (нет ли заломов/повреждений)
&#8594; Попросить подключить напрямую в ПК

ЕСЛИ баланс "-":
&#8594; Сообщить о необходимости пополнения</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Если перезагрузка не помогла и проблема на нашей стороне &#8594; создать заявку на выезд.
Озвучить: "Если проблема на нашей стороне &#8212; выезд бесплатный"</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Технический отдел
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">нет инета</span>
          <span class="kb-keyword">пропал интернет</span>
          <span class="kb-keyword">не работает</span>
          <span class="kb-keyword">отключился</span>
          <span class="kb-keyword">отсутствует</span>
          <span class="kb-keyword">нет соединения</span>
        </div>
        <div class="kb-card-note">62% всех обращений. Самая частая проблема. Часто связана с массовыми авариями в отдельных посёлках.</div>
      </div>
    </div>

    <!-- S02 -->
    <div class="kb-card" data-id="S02" data-priority="high" data-audience="bot operator" data-keywords="горит лос, горит los, красный индикатор, красная лампочка, los горит">
      <div class="kb-card-header">
        <span class="kb-card-id">S02</span>
        <span class="kb-card-title">Горит LOS</span>
        <span class="kb-card-problem">Горит LOS / Горит красным на коробочке / На ONU красный индикатор</span>
        <span class="kb-badge kb-badge-high">Высокий</span>
        <span class="kb-badge kb-badge-audience">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. Номер договора / адрес
2. Какие индикаторы горят на ONU? (попросить фото)
3. Не было ли ремонтных работ рядом?
4. Кабель цел? Нет ли заломов?</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Проверить наличие сессии &#8212; скорее всего нет
2. Проверить, есть ли массовые обращения по этому адресу
3. Уточнить внешний осмотр оптического кабеля</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">LOS = потеря оптического сигнала.
&#8594; Клиенту: "Проверьте оптический кабель &#8212; нет ли заломов, изгибов, повреждений"
&#8594; Если кабель цел визуально &#8212; необходим выезд специалиста
&#8594; Озвучить: "Если проблема на нашей стороне &#8212; бесплатно"</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Создать заявку на выезд с пометкой "LOS на ONU".
Требуется: измеритель оптического сигнала, возможна замена ONU.</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Монтажный отдел
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">горит лос</span>
          <span class="kb-keyword">горит los</span>
          <span class="kb-keyword">красный индикатор</span>
          <span class="kb-keyword">красная лампочка</span>
        </div>
        <div class="kb-card-note">Обрыв оптики. Удалённо не решается. Часто после ветра, ремонтных работ, деревьев на линии.</div>
      </div>
    </div>

    <!-- S03 -->
    <div class="kb-card" data-id="S03" data-priority="medium" data-audience="operator" data-keywords="на мк, медиаконвертер, индикатор, не все горят, 4 из 8, 4 из 6, коробочка">
      <div class="kb-card-header">
        <span class="kb-card-id">S03</span>
        <span class="kb-card-title">Проблемы с МК</span>
        <span class="kb-card-problem">На медиаконвертере горят не все индикаторы / Мало лампочек</span>
        <span class="kb-badge kb-badge-medium">Средний</span>
        <span class="kb-badge kb-badge-audience">Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. Номер договора / адрес
2. Сколько индикаторов горит из скольких?
3. Попросить фото МК
4. Когда началась проблема?</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Проверить сессию
2. Проверить порт на коммутаторе
3. Уточнить &#8212; подключен ли абонент через МК или ONU</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">&#8594; Попросить перезагрузить МК (выключить/включить)
&#8594; Перетыкнуть кабель в МК
&#8594; Если не помогло &#8212; проверить линк напрямую в ПК</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Создать заявку на выезд с указанием "МК &#8212; неполная индикация".
Возможна замена МК или порта.</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Монтажный отдел
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">медиаконвертер</span>
          <span class="kb-keyword">индикатор</span>
          <span class="kb-keyword">не все горят</span>
          <span class="kb-keyword">коробочка</span>
        </div>
        <div class="kb-card-note">Часто в отдельных посёлках. МК может быть неисправен или проблема на стороне коммутатора.</div>
      </div>
    </div>

    <!-- S04 -->
    <div class="kb-card" data-id="S04" data-priority="medium" data-audience="bot operator" data-keywords="низкая скорость, медленн, тормозит, не грузит, маленькая скорость, лагает, долго грузит">
      <div class="kb-card-header">
        <span class="kb-card-id">S04</span>
        <span class="kb-card-title">Низкая скорость</span>
        <span class="kb-card-problem">Низкая скорость / Медленный интернет / Тормозит</span>
        <span class="kb-badge kb-badge-medium">Средний</span>
        <span class="kb-badge kb-badge-audience">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. Номер договора / адрес
2. Какой тариф? Какая скорость ожидается?
3. Подключены через роутер или напрямую?
4. Сколько устройств подключено?
5. Используете ли VPN?</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Проверить тариф абонента
2. Проверить сессию &#8212; скорость по сессии
3. Попросить сделать замер на speedtest.yandex.ru НАПРЯМУЮ через кабель (не Wi-Fi!)</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">ЕСЛИ скорость по замеру соответствует тарифу:
&#8594; "Скорость соответствует вашему тарифу. Проблема может быть в Wi-Fi / кол-ве устройств"
&#8594; Рекомендовать 5GHz, перезагрузку роутера

ЕСЛИ скорость ниже тарифа напрямую:
&#8594; Создать заявку на диагностику линии</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Если напрямую скорость низкая &#8212; заявка на диагностику.
Если только по Wi-Fi &#8212; рекомендовать настройку / замену роутера.</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Технический отдел
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">низкая скорость</span>
          <span class="kb-keyword">тормозит</span>
          <span class="kb-keyword">не грузит</span>
          <span class="kb-keyword">лагает</span>
          <span class="kb-keyword">долго грузит</span>
        </div>
        <div class="kb-card-note">5% обращений. Часто причина: много устройств на Wi-Fi, VPN, старый роутер. Замер ТОЛЬКО через кабель!</div>
      </div>
    </div>

    <!-- S05 -->
    <div class="kb-card" data-id="S05" data-priority="medium" data-audience="bot operator" data-keywords="проблема с роутером, настройка роутер, сбросился, заводские настройки, название сети изменилось, робор, rbr">
      <div class="kb-card-header">
        <span class="kb-card-id">S05</span>
        <span class="kb-card-title">Проблемы с роутером</span>
        <span class="kb-card-problem">Роутер не работает / Нужна настройка / Сбросился роутер</span>
        <span class="kb-badge kb-badge-medium">Средний</span>
        <span class="kb-badge kb-badge-audience">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. Номер договора / адрес
2. Какой роутер? (наш РОБОР rbr* или свой?)
3. Изменилось ли название Wi-Fi сети?
4. Горят ли индикаторы на роутере?</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Если РОБОР (rbr*) &#8212; найти серийный номер в системе
2. Проверить сессию
3. Если название сети сменилось на заводское &#8212; скорее всего сброс</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">ЕСЛИ роутер СМИТ (РОБОР):
&#8594; Можно настроить удалённо (PPPoE: логин/пароль из биллинга)
&#8594; Отправить видеоинструкцию по настройке

ЕСЛИ свой роутер:
&#8594; Озвучить стоимость: настройка 1200&#8381;, выезд 800&#8381;
&#8594; Отправить инструкцию для самостоятельной настройки (PPPoE)</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Если клиент не может настроить сам &#8594; заявка на выезд.
Озвучить: настройка 1200&#8381; + выезд 800&#8381; (если проблема не на нашей стороне).</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Технический отдел
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">роутер</span>
          <span class="kb-keyword">настройка</span>
          <span class="kb-keyword">сбросился</span>
          <span class="kb-keyword">робор</span>
          <span class="kb-keyword">rbr</span>
        </div>
        <div class="kb-card-note">7% обращений. Роутеры РОБОР (rbr*) &#8212; фирменные. Частая причина: случайный сброс кнопкой Reset.</div>
      </div>
    </div>

    <!-- S06 -->
    <div class="kb-card" data-id="S06" data-priority="low" data-audience="bot operator" data-keywords="вай фай, wi-fi, wifi, беспроводной, не ловит, пропадает вайфай, плохой сигнал">
      <div class="kb-card-header">
        <span class="kb-card-id">S06</span>
        <span class="kb-card-title">Проблемы с Wi-Fi</span>
        <span class="kb-card-problem">Не работает Wi-Fi / Пропадает Wi-Fi / Плохой сигнал</span>
        <span class="kb-badge kb-badge-low">Низкий</span>
        <span class="kb-badge kb-badge-audience">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. Номер договора / адрес
2. Работает ли интернет по кабелю напрямую?
3. На всех ли устройствах проблема?
4. Сколько комнат / этажей?</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Проверить сессию
2. Если сессия есть и по кабелю работает &#8594; проблема в роутере/Wi-Fi
3. Если сессии нет &#8594; см. сценарий S01</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">ЕСЛИ по кабелю работает, Wi-Fi нет:
&#8594; Перезагрузить роутер
&#8594; Проверить: видят ли устройства сеть?
&#8594; Рекомендовать переключить на канал 5GHz
&#8594; Если большая площадь &#8212; рекомендовать репитер/mesh

ЕСЛИ по кабелю тоже не работает:
&#8594; Перейти к сценарию S01</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Если проблема в покрытии &#8594; предложить доп. оборудование (репитер 3500&#8381;).
Если роутер неисправен &#8594; замена (4500&#8381; наш / свой).</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Продажи (доп. оборудование)
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">вай фай</span>
          <span class="kb-keyword">wi-fi</span>
          <span class="kb-keyword">wifi</span>
          <span class="kb-keyword">не ловит</span>
          <span class="kb-keyword">плохой сигнал</span>
        </div>
        <div class="kb-card-note">Часто: старый роутер, далеко от роутера, много стен. Не путать с отсутствием интернета!</div>
      </div>
    </div>

    <!-- S07 -->
    <div class="kb-card" data-id="S07" data-priority="low" data-audience="bot operator" data-keywords="оплата, баланс, обещанный платёж, личный кабинет, списали, минус, задолженность, не могу зайти">
      <div class="kb-card-header">
        <span class="kb-card-id">S07</span>
        <span class="kb-card-title">Вопросы оплаты</span>
        <span class="kb-card-problem">Не могу оплатить / Вопрос по балансу / Обещанный платёж</span>
        <span class="kb-badge kb-badge-low">Низкий</span>
        <span class="kb-badge kb-badge-audience">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. Номер договора
2. Какой именно вопрос: баланс / оплата / вход в ЛК?
3. Каким способом оплачивали?</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Проверить баланс в биллинге
2. Проверить дату последней оплаты
3. Проверить тариф и ежемесячное списание</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">ЕСЛИ минус на балансе:
&#8594; Назвать сумму задолженности
&#8594; Сообщить способы оплаты
&#8594; Предложить обещанный платёж (если доступен)

ЕСЛИ не может войти в ЛК:
&#8594; Логин = номер договора
&#8594; Пароль можно сбросить в офисе или по звонку</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Если спорная ситуация по списанию &#8594; передать в бухгалтерию.
Если требует перерасчёт &#8594; заявка на перерасчёт.</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Бухгалтерия / Руководитель
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">оплата</span>
          <span class="kb-keyword">баланс</span>
          <span class="kb-keyword">обещанный платёж</span>
          <span class="kb-keyword">личный кабинет</span>
          <span class="kb-keyword">задолженность</span>
        </div>
        <div class="kb-card-note">3% обращений. Оплата списывается 1 числа. Рекомендовать оплачивать за 3 дня до конца месяца.</div>
      </div>
    </div>

    <!-- S08 -->
    <div class="kb-card" data-id="S08" data-priority="low" data-audience="bot" data-keywords="подключить, подключение, подключиться, хочу интернет, тариф, сколько стоит, установка">
      <div class="kb-card-header">
        <span class="kb-card-id">S08</span>
        <span class="kb-card-title">Подключение</span>
        <span class="kb-card-problem">Хочу подключить интернет / Сколько стоит</span>
        <span class="kb-badge kb-badge-low">Низкий</span>
        <span class="kb-badge kb-badge-audience">Бот</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. ФИО
2. Точный адрес с населённым пунктом
3. Актуальный номер телефона
4. Удобное время для связи
5. Какой тариф интересует?</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Проверить техническую возможность по адресу
2. Если адрес в зоне покрытия &#8212; озвучить тарифы
3. Если нет &#8212; сообщить и предложить оставить заявку</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">Тарифы СМИТ:
&#8226; SMiT 100 Мбит/с &#8212; 949&#8381;/мес
&#8226; Для Тебя &#8212; уточнить
&#8226; Без Границ 150 Мбит/с &#8212; уточнить
&#8226; SmitBusiness &#8212; для юрлиц

Записать заявку: ФИО, адрес, телефон, тариф, удобное время.</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Передать заявку в отдел подключений.
Монтаж в ближайший рабочий день.
Позвонить за час до выезда.</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Отдел подключений
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">подключить</span>
          <span class="kb-keyword">подключение</span>
          <span class="kb-keyword">тариф</span>
          <span class="kb-keyword">сколько стоит</span>
        </div>
        <div class="kb-card-note">2.4% обращений. Важно собрать полные данные с первого контакта.</div>
      </div>
    </div>

    <!-- S09 -->
    <div class="kb-card" data-id="S09" data-priority="medium" data-audience="operator" data-keywords="сессия в кб, в килобайтах, в байтах, скорость прыгает, то работает то нет, нестабильный">
      <div class="kb-card-header">
        <span class="kb-card-id">S09</span>
        <span class="kb-card-title">Сессия в кб/байтах</span>
        <span class="kb-card-problem">Интернет то в кб, то в гб / Скорость прыгает</span>
        <span class="kb-badge kb-badge-medium">Средний</span>
        <span class="kb-badge kb-badge-audience">Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. Номер договора / адрес
2. Как давно проблема?
3. Перезагружали ли роутер?
4. Есть ли закономерность (утро/вечер)?</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Проверить сессию &#8212; если трафик в кб &#8594; проблема на линии
2. Проверить порт на коммутаторе
3. Проверить, нет ли ошибок на порту</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">&#8594; Сбросить сессию со стороны провайдера
&#8594; Попросить перезагрузить роутер
&#8594; Если после сброса сессия снова в кб &#8594; проблема физическая</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Создать заявку на диагностику линии.
Может быть: плохой контакт на патчкорде, проблема в МК/ONU, проблема на коммутаторе.</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Технический отдел
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">сессия в кб</span>
          <span class="kb-keyword">в килобайтах</span>
          <span class="kb-keyword">скорость прыгает</span>
          <span class="kb-keyword">нестабильный</span>
        </div>
        <div class="kb-card-note">Признак деградации линии. Часто предвестник полного отключения.</div>
      </div>
    </div>

    <!-- S10 -->
    <div class="kb-card" data-id="S10" data-priority="high" data-audience="bot operator" data-keywords="обрыв, оборвался кабель, повреждён, оторвался, провод порвали, дерево, упало">
      <div class="kb-card-header">
        <span class="kb-card-id">S10</span>
        <span class="kb-card-title">Обрыв кабеля</span>
        <span class="kb-card-problem">Оборвался кабель / Кабель повреждён / Дерево упало на провода</span>
        <span class="kb-badge kb-badge-high">Высокий</span>
        <span class="kb-badge kb-badge-audience">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. Номер договора / адрес
2. Где именно обрыв? (в квартире / на улице / на доме)
3. Видно ли повреждение?
4. Что послужило причиной?</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Проверить сессию &#8212; скорее всего нет
2. Зафиксировать описание повреждения</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">&#8594; "Мы зафиксировали обрыв. Создаём заявку на выезд монтажника."
&#8594; Если обрыв ВНУТРИ квартиры &#8212; озвучить стоимость восстановления
&#8594; Если обрыв СНАРУЖИ &#8212; бесплатно</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Срочная заявка на выезд.
Если обрыв на нашей стороне &#8212; бесплатно.
Если клиент повредил &#8212; восстановление платное.</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Монтажный отдел (срочно)
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">обрыв</span>
          <span class="kb-keyword">оборвался кабель</span>
          <span class="kb-keyword">повреждён</span>
          <span class="kb-keyword">дерево</span>
        </div>
        <div class="kb-card-note">Часто после ветра, строительных работ. Проверять связанных абонентов на том же линке.</div>
      </div>
    </div>

    <!-- S11 -->
    <div class="kb-card" data-id="S11" data-priority="high" data-audience="operator" data-keywords="повторно, уже звонил, который раз, опять, жалоба, когда решите, сколько можно, устала">
      <div class="kb-card-header">
        <span class="kb-card-id">S11</span>
        <span class="kb-card-title">Повторное обращение</span>
        <span class="kb-card-problem">Уже звонил / Никто не приехал / Когда решите проблему / Жалоба</span>
        <span class="kb-badge kb-badge-high">Высокий</span>
        <span class="kb-badge kb-badge-audience">Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. Номер договора / адрес
2. Когда обращались в первый раз?
3. Что было сказано / обещано?
4. Номер предыдущей заявки (если есть)</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Найти предыдущие тикеты абонента
2. Проверить статус заявки на выезд
3. Есть ли абонент в плане выездов?</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">&#8594; Извиниться за задержку
&#8594; "Я вижу вашу предыдущую заявку. Уточню статус."
&#8594; Связаться с техотделом для уточнения сроков
&#8594; Перезвонить клиенту с конкретной датой/временем</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Если заявка потеряна &#8212; создать повторную с пометкой "ПОВТОРНОЕ".
Если клиент агрессивен &#8212; передать руководителю.
Рассмотреть перерасчёт за дни без интернета.</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Руководитель техподдержки
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">повторно</span>
          <span class="kb-keyword">уже звонил</span>
          <span class="kb-keyword">жалоба</span>
          <span class="kb-keyword">когда решите</span>
          <span class="kb-keyword">сколько можно</span>
        </div>
        <div class="kb-card-note">~15% обращений. Критично для удержания клиента. Приоритизировать абонентов с 3+ обращениями.</div>
      </div>
    </div>

    <!-- S12 -->
    <div class="kb-card" data-id="S12" data-priority="low" data-audience="bot operator" data-keywords="возобновить, возобновление, был отключен, заново, давно не пользовался, восстановить">
      <div class="kb-card-header">
        <span class="kb-card-id">S12</span>
        <span class="kb-card-title">Возобновление услуги</span>
        <span class="kb-card-problem">Хочу возобновить / Был отключён / Давно не пользовался</span>
        <span class="kb-badge kb-badge-low">Низкий</span>
        <span class="kb-badge kb-badge-audience">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">1. Номер договора / адрес
2. Как давно отключены?
3. Оборудование на месте? (роутер, МК, ONU)</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">1. Проверить статус в биллинге
2. Проверить, есть ли задолженность
3. Проверить, есть ли линк / техническая возможность</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">&#8594; Активировать услугу в биллинге
&#8594; Попросить оплатить (если задолженность)
&#8594; Попросить перезагрузить оборудование
&#8594; Если роутер РОБОР &#8212; возможно нужна перенастройка</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Если линка нет / оборудование снято &#8212; заявка на выезд монтажника.
Может потребоваться переподключение.</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Отдел подключений
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">возобновить</span>
          <span class="kb-keyword">возобновление</span>
          <span class="kb-keyword">был отключен</span>
          <span class="kb-keyword">восстановить</span>
        </div>
        <div class="kb-card-note">Проверить, не ушёл ли клиент к конкуренту.</div>
      </div>
    </div>

    <!-- S13 -->
    <div class="kb-card" data-id="S13" data-priority="none" data-audience="bot operator" data-keywords="заработок, report domain, dmarc, парковка, автобус, сцепление, не по теме, спам">
      <div class="kb-card-header">
        <span class="kb-card-id">S13</span>
        <span class="kb-card-title">Спам / Нецелевое</span>
        <span class="kb-card-problem">Заработок / Report domain / Не по теме / Ошибочный звонок</span>
        <span class="kb-badge kb-badge-none">Нет</span>
        <span class="kb-badge kb-badge-audience">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">&#8212; Не требуется &#8212;</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удалённая диагностика</div>
            <div class="kb-step-content">&#8212; Не требуется &#8212;</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Решение</div>
            <div class="kb-step-content">Бот: "Извините, я могу помочь только с вопросами по интернету от провайдера СМИТ."

Оператор: Закрыть тикет с тегом "спам" или "нецелевое".</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Если не помогло</div>
            <div class="kb-step-content">Закрыть без эскалации.
DMARC-отчёты &#8212; перенаправить сисадмину.</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> &#8212;
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">спам</span>
          <span class="kb-keyword">report domain</span>
          <span class="kb-keyword">dmarc</span>
          <span class="kb-keyword">не по теме</span>
        </div>
        <div class="kb-card-note">Часть обращений остаётся неклассифицированной.</div>
      </div>
    </div>

    <!-- S17 -->
    <div class="kb-card" data-id="S17" data-priority="medium" data-audience="bot operator" data-keywords="расторжение, расторгнуть, отключить, отключение, уйти, другой провайдер, отказ, не нужен, убрать оборудование">
      <div class="kb-card-header">
        <span class="kb-card-id">S17</span>
        <span class="kb-card-title">Расторжение / Отключение</span>
        <span class="kb-card-problem">Расторжение договора / Отключение услуг / Переход к другому провайдеру</span>
        <span class="kb-badge kb-badge-medium">Средний</span>
        <span class="kb-badge kb-badge-audience">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="kb-card-body">
        <div class="kb-steps">
          <div class="kb-step">
            <div class="kb-step-label">Шаг 1: Уточнение</div>
            <div class="kb-step-content">Уточните причину расторжения:
&#8212; Переезд в зону без покрытия?
&#8212; Не устраивает качество/цена?
&#8212; Переход к другому провайдеру?
&#8212; Временное отключение (отъезд)?

Если временное — предложите заморозку (S12).</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 2: Удержание</div>
            <div class="kb-step-content">Предложите варианты:
&#8212; Смена тарифа на более выгодный
&#8212; Перерасчёт за период простоя
&#8212; Бесплатная диагностика линии
&#8212; Заморозка на срок до 3 месяцев

Зафиксируйте причину в тикете.</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 3: Оформление</div>
            <div class="kb-step-content">Если клиент настаивает:
1. Создать тикет с тегом "расторжение"
2. Указать причину в тикете
3. Согласовать дату отключения
4. Уточнить возврат оборудования (если арендное)
5. Передать в отдел подключений</div>
          </div>
          <div class="kb-step">
            <div class="kb-step-label">Шаг 4: Возврат оборудования</div>
            <div class="kb-step-content">Арендное оборудование (роутер, МК):
&#8212; Назначить выезд монтажника для демонтажа
&#8212; Или клиент привозит в офис самостоятельно

Купленное оборудование остаётся у клиента.</div>
          </div>
        </div>
        <div style="margin-top:12px; font-size:13px; color:var(--kb-gray);">
          <strong>Эскалация:</strong> Начальник отдела подключений
        </div>
        <div class="kb-card-keywords">
          <span class="kb-keyword">расторжение</span>
          <span class="kb-keyword">отключение</span>
          <span class="kb-keyword">уйти</span>
          <span class="kb-keyword">другой провайдер</span>
        </div>
        <div class="kb-card-note">Небольшая доля обращений. Важно фиксировать причину для анализа оттока.</div>
      </div>
    </div>

  </div>
</div>

<!-- ===== РАЗДЕЛ 2: ДЕРЕВО РЕШЕНИЙ ===== -->
<div id="kb-section-tree" class="kb-section" style="display:none;">
  <div class="kb-section-title">Дерево диагностики: "Нет интернета"</div>

  <div style="background:#fff; border-radius:12px; border:1px solid var(--kb-border); padding:20px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
      <div style="width:40px; height:40px; border-radius:50%; background:var(--kb-primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px;">?</div>
      <div>
        <div style="font-weight:600; font-size:16px;">Пошаговая диагностика</div>
        <div style="font-size:13px; color:var(--kb-gray);">Следуйте шагам сверху вниз. На каждом шаге выбирайте "Да" или "Нет".</div>
      </div>
    </div>

    <table class="tree-table">
      <thead>
        <tr>
          <th style="width:60px; text-align:center;">Шаг</th>
          <th>Вопрос / Проверка</th>
          <th style="width:25%;">Если ДА</th>
          <th style="width:25%;">Если НЕТ</th>
          <th style="width:120px;">Сценарий</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="step-num">1</td>
          <td><strong>Есть ли сессия в биллинге?</strong></td>
          <td class="tree-yes">&#8594; Шаг 2 (проблема не в подключении)</td>
          <td class="tree-no">&#8594; Шаг 5 (нет соединения)</td>
          <td>&#8212;</td>
        </tr>
        <tr>
          <td class="step-num">2</td>
          <td><strong>Сессия в ГБ (нормальная)?</strong></td>
          <td class="tree-yes">&#8594; Шаг 3 (линия ОК, проблема локальная)</td>
          <td class="tree-no">&#8594; Если в КБ &#8594; <span class="tree-ref" data-kb-scenario="S09">S09</span> (деградация линии)</td>
          <td><span class="tree-ref" data-kb-scenario="S09">S09</span></td>
        </tr>
        <tr>
          <td class="step-num">3</td>
          <td><strong>Работает ли интернет напрямую (без роутера)?</strong></td>
          <td class="tree-yes">&#8594; Проблема в роутере &#8594; <span class="tree-ref" data-kb-scenario="S05">S05</span> / <span class="tree-ref" data-kb-scenario="S06">S06</span></td>
          <td class="tree-no">&#8594; Шаг 4</td>
          <td><span class="tree-ref" data-kb-scenario="S05">S05</span>, <span class="tree-ref" data-kb-scenario="S06">S06</span></td>
        </tr>
        <tr>
          <td class="step-num">4</td>
          <td><strong>Попробовать перезагрузить роутер и сбросить сессию</strong></td>
          <td class="tree-yes">&#8594; Заработало &#8594; Закрыть тикет &#10003;</td>
          <td class="tree-no">&#8594; Заявка на выезд</td>
          <td>&#8212;</td>
        </tr>
        <tr>
          <td class="step-num">5</td>
          <td><strong>Баланс положительный?</strong></td>
          <td class="tree-yes">&#8594; Шаг 6</td>
          <td class="tree-no">&#8594; Сообщить о задолженности &#8594; <span class="tree-ref" data-kb-scenario="S07">S07</span></td>
          <td><span class="tree-ref" data-kb-scenario="S07">S07</span></td>
        </tr>
        <tr>
          <td class="step-num">6</td>
          <td><strong>Горит ли LOS на ONU?</strong></td>
          <td class="tree-yes">&#8594; <span class="tree-ref" data-kb-scenario="S02">S02</span> (обрыв оптики)</td>
          <td class="tree-no">&#8594; Шаг 7</td>
          <td><span class="tree-ref" data-kb-scenario="S02">S02</span></td>
        </tr>
        <tr>
          <td class="step-num">7</td>
          <td><strong>Все ли индикаторы горят на МК/ONU?</strong></td>
          <td class="tree-yes">&#8594; Шаг 8 (оборудование ОК)</td>
          <td class="tree-no">&#8594; <span class="tree-ref" data-kb-scenario="S03">S03</span> / <span class="tree-ref" data-kb-scenario="S10">S10</span> (проблема оборудования)</td>
          <td><span class="tree-ref" data-kb-scenario="S03">S03</span>, <span class="tree-ref" data-kb-scenario="S10">S10</span></td>
        </tr>
        <tr>
          <td class="step-num">8</td>
          <td><strong>Перезагрузка оборудования + перетык кабеля помогли?</strong></td>
          <td class="tree-yes">&#8594; Закрыть тикет &#10003;</td>
          <td class="tree-no">&#8594; Создать заявку на выезд</td>
          <td>&#8212;</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div style="background:#fff; border-radius:12px; border:1px solid var(--kb-border); padding:20px;">
    <div style="font-weight:600; font-size:15px; margin-bottom:12px;">Справка по стоимости услуг</div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
      <div style="background:var(--kb-gray-bg); border-radius:8px; padding:12px;">
        <div style="font-size:12px; color:var(--kb-gray); margin-bottom:4px;">Выезд специалиста</div>
        <div style="font-size:18px; font-weight:600;">800 &#8381;</div>
        <div style="font-size:11px; color:var(--kb-green);">Бесплатно, если наша сторона</div>
      </div>
      <div style="background:var(--kb-gray-bg); border-radius:8px; padding:12px;">
        <div style="font-size:12px; color:var(--kb-gray); margin-bottom:4px;">Настройка роутера</div>
        <div style="font-size:18px; font-weight:600;">1 200 &#8381;</div>
      </div>
      <div style="background:var(--kb-gray-bg); border-radius:8px; padding:12px;">
        <div style="font-size:12px; color:var(--kb-gray); margin-bottom:4px;">Роутер СМИТ</div>
        <div style="font-size:18px; font-weight:600;">4 500 / 4 900 &#8381;</div>
      </div>
      <div style="background:var(--kb-gray-bg); border-radius:8px; padding:12px;">
        <div style="font-size:12px; color:var(--kb-gray); margin-bottom:4px;">Репитер</div>
        <div style="font-size:18px; font-weight:600;">3 500 &#8381;</div>
      </div>
    </div>
  </div>
</div>

<!-- ===== РАЗДЕЛ 3: ШАБЛОНЫ ДИАЛОГОВ ===== -->
<div id="kb-section-dialogs" class="kb-section" style="display:none;">
  <div class="kb-section-title">Скрипты диалогов и шаблоны ответов <button class="kb-toggle-all" data-target="dialogs">Развернуть все</button></div>
  <div class="dialogs" id="kb-dialogs-list">

    <!-- D01 -->
    <div class="dialog-card" data-keywords="приветствие, здравствуйте, добрый день, привет">
      <div class="dialog-header">
        <span class="dialog-id">D01</span>
        <span class="dialog-title">Приветствие</span>
        <span class="tone-badge">Дружелюбный</span>
        <span class="dialog-channel">Бот</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="dialog-body">
        <div class="dialog-columns">
          <div class="dialog-block client">
            <button class="copy-btn">Копировать</button>
            <div class="dialog-block-label">Ответ клиенту</div>
Здравствуйте! Я — виртуальный помощник провайдера СМИТ.

Я могу помочь с:
&#8226; Проблемами с интернетом
&#8226; Вопросами по оплате
&#8226; Подключением новых услуг

Опишите вашу проблему или назовите номер договора.</div>
          <div class="dialog-block internal">
            <div class="dialog-block-label">Для оператора (внутренний)</div>
&#8212;</div>
        </div>
      </div>
    </div>

    <!-- D02 -->
    <div class="dialog-card" data-keywords="номер договора, договор, адрес, уточнение">
      <div class="dialog-header">
        <span class="dialog-id">D02</span>
        <span class="dialog-title">Запрос номера договора</span>
        <span class="tone-badge">Нейтральный</span>
        <span class="dialog-channel">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="dialog-body">
        <div class="dialog-columns">
          <div class="dialog-block client">
            <button class="copy-btn">Копировать</button>
            <div class="dialog-block-label">Ответ клиенту</div>
Для проверки мне нужен ваш номер договора или адрес подключения. Номер договора можно найти в квитанции об оплате или в личном кабинете.</div>
          <div class="dialog-block internal">
            <div class="dialog-block-label">Для оператора (внутренний)</div>
Уточните номер договора или адрес.</div>
        </div>
      </div>
    </div>

    <!-- D03 -->
    <div class="dialog-card" data-keywords="нет интернета, не работает, пропал, первичный ответ, диагностика">
      <div class="dialog-header">
        <span class="dialog-id">D03</span>
        <span class="dialog-title">Нет интернета — первичный ответ</span>
        <span class="tone-badge">Эмпатичный</span>
        <span class="dialog-channel">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="dialog-body">
        <div class="dialog-columns">
          <div class="dialog-block client">
            <button class="copy-btn">Копировать</button>
            <div class="dialog-block-label">Ответ клиенту</div>
Я понимаю, как это неприятно. Давайте разберёмся!

Попробуйте, пожалуйста:
1. Выключите роутер из розетки
2. Подождите 30 секунд
3. Включите обратно

Если не помогло — напишите, какие индикаторы горят на оборудовании (можно прислать фото).</div>
          <div class="dialog-block internal">
            <div class="dialog-block-label">Для оператора (внутренний)</div>
Сессии нет. Баланс [+/-]. Попросить перезагрузить роутер. Если не помогает — проверить линк напрямую.</div>
        </div>
      </div>
    </div>

    <!-- D04 -->
    <div class="dialog-card" data-keywords="заявка, выезд, создана, монтажник">
      <div class="dialog-header">
        <span class="dialog-id">D04</span>
        <span class="dialog-title">Создана заявка на выезд</span>
        <span class="tone-badge">Профессиональный</span>
        <span class="dialog-channel">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="dialog-body">
        <div class="dialog-columns">
          <div class="dialog-block client">
            <button class="copy-btn">Копировать</button>
            <div class="dialog-block-label">Ответ клиенту</div>
Я создал заявку на проверку вашего подключения.

Наш специалист свяжется с вами перед выездом.

Если проблема на нашей стороне — выезд бесплатный.
Если проблема в вашем оборудовании — стоимость выезда 800&#8381;.

Спасибо за терпение!</div>
          <div class="dialog-block internal">
            <div class="dialog-block-label">Для оператора (внутренний)</div>
Заявка создана. Передать в план выездов. Озвучить клиенту: бесплатно, если наша сторона; 800&#8381; выезд + 1200&#8381; настройка, если клиентская.</div>
        </div>
      </div>
    </div>

    <!-- D05 -->
    <div class="dialog-card" data-keywords="массовая авария, технические работы, район, восстановление">
      <div class="dialog-header">
        <span class="dialog-id">D05</span>
        <span class="dialog-title">Массовая авария</span>
        <span class="tone-badge">Эмпатичный</span>
        <span class="dialog-channel">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="dialog-body">
        <div class="dialog-columns">
          <div class="dialog-block client">
            <button class="copy-btn">Копировать</button>
            <div class="dialog-block-label">Ответ клиенту</div>
В вашем районе сейчас проводятся технические работы.

Мы знаем о проблеме и уже работаем над восстановлением.
Ориентировочное время: [XX часов].

Приносим извинения за неудобства! Мы сообщим, когда всё будет восстановлено.</div>
          <div class="dialog-block internal">
            <div class="dialog-block-label">Для оператора (внутренний)</div>
Массовая авария на узле [название]. Ориентировочное восстановление: [время]. Не создавать индивидуальные заявки — ждать общего решения.</div>
        </div>
      </div>
    </div>

    <!-- D06 -->
    <div class="dialog-card" data-keywords="злится, повторное, жалоба, агрессив, недоволен, разочарование">
      <div class="dialog-header">
        <span class="dialog-id">D06</span>
        <span class="dialog-title">Клиент злится / повторное обращение</span>
        <span class="tone-badge">Эмпатичный</span>
        <span class="dialog-channel">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="dialog-body">
        <div class="dialog-columns">
          <div class="dialog-block client">
            <button class="copy-btn">Копировать</button>
            <div class="dialog-block-label">Ответ клиенту</div>
Я понимаю ваше разочарование и приношу искренние извинения за задержку. Ваша проблема для нас приоритетна.

Я передам ваше обращение старшему специалисту прямо сейчас. Вам перезвонят в течение [X часов] с конкретным решением.

Номер вашей заявки: [XXXX].</div>
          <div class="dialog-block internal">
            <div class="dialog-block-label">Для оператора (внутренний)</div>
ПОВТОРНОЕ ОБРАЩЕНИЕ. Клиент звонит [N]-й раз. Проверить статус заявки. Если заявка потеряна — пересоздать с тегом "ПРИОРИТЕТ". Эскалировать на руководителя при 3+ обращениях.</div>
        </div>
      </div>
    </div>

    <!-- D07 -->
    <div class="dialog-card" data-keywords="стоимость, цены, прайс, тариф, выезд, настройка, роутер">
      <div class="dialog-header">
        <span class="dialog-id">D07</span>
        <span class="dialog-title">Стоимость услуг</span>
        <span class="tone-badge">Информативный</span>
        <span class="dialog-channel">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="dialog-body">
        <div class="dialog-columns">
          <div class="dialog-block client">
            <button class="copy-btn">Копировать</button>
            <div class="dialog-block-label">Ответ клиенту</div>
Стоимость наших услуг:

Выезд специалиста: 800&#8381; (бесплатно, если проблема на нашей стороне)
Настройка роутера: 1 200&#8381;
Роутер СМИТ: 4 500&#8381; / 4 900&#8381;
Репитер: 3 500&#8381;

Тарифы:
SMiT 100 Мбит/с — 949&#8381;/мес
Без Границ 150 Мбит/с — уточняйте

Хотите оставить заявку?</div>
          <div class="dialog-block internal">
            <div class="dialog-block-label">Для оператора (внутренний)</div>
Прайс: выезд 800&#8381;, настройка 1200&#8381;, роутер 4500/4900&#8381;, репитер 3500&#8381;.</div>
        </div>
      </div>
    </div>

    <!-- D08 -->
    <div class="dialog-card" data-keywords="перевод, оператор, живой, передаю, подождите">
      <div class="dialog-header">
        <span class="dialog-id">D08</span>
        <span class="dialog-title">Перевод на оператора</span>
        <span class="tone-badge">Нейтральный</span>
        <span class="dialog-channel">Бот</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="dialog-body">
        <div class="dialog-columns">
          <div class="dialog-block client">
            <button class="copy-btn">Копировать</button>
            <div class="dialog-block-label">Ответ клиенту</div>
Я передаю ваш вопрос живому оператору. Пожалуйста, подождите — специалист подключится в ближайшее время.

Вся информация, которую вы мне рассказали, уже передана оператору.</div>
          <div class="dialog-block internal">
            <div class="dialog-block-label">Для оператора (внутренний)</div>
&#8212;</div>
        </div>
      </div>
    </div>

    <!-- D09 -->
    <div class="dialog-card" data-keywords="закрытие, решено, помог, спасибо, завершение">
      <div class="dialog-header">
        <span class="dialog-id">D09</span>
        <span class="dialog-title">Закрытие обращения</span>
        <span class="tone-badge">Позитивный</span>
        <span class="dialog-channel">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="dialog-body">
        <div class="dialog-columns">
          <div class="dialog-block client">
            <button class="copy-btn">Копировать</button>
            <div class="dialog-block-label">Ответ клиенту</div>
Рад, что удалось помочь!

Если у вас возникнут ещё вопросы — обращайтесь. Мы работаем 24/7.</div>
          <div class="dialog-block internal">
            <div class="dialog-block-label">Для оператора (внутренний)</div>
Проблема решена. Закрыть тикет. Уточнить у клиента: "Всё ли работает?"</div>
        </div>
      </div>
    </div>

    <!-- D10 -->
    <div class="dialog-card" data-keywords="нецелевое, не по теме, ошибочный, спам">
      <div class="dialog-header">
        <span class="dialog-id">D10</span>
        <span class="dialog-title">Нецелевое обращение</span>
        <span class="tone-badge">Вежливый</span>
        <span class="dialog-channel">Бот + Оператор</span>
        <svg class="kb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div class="dialog-body">
        <div class="dialog-columns">
          <div class="dialog-block client">
            <button class="copy-btn">Копировать</button>
            <div class="dialog-block-label">Ответ клиенту</div>
Извините, я могу помочь только с вопросами по интернету от провайдера СМИТ.

Если у вас вопрос по интернету — опишите проблему.
Если вам нужна другая помощь — обратитесь по телефону 8 (XXX) XXX-XX-XX.</div>
          <div class="dialog-block internal">
            <div class="dialog-block-label">Для оператора (внутренний)</div>
Нецелевое обращение. Закрыть с тегом "не по теме".</div>
        </div>
      </div>
    </div>

  </div>
</div>


</div><!-- .kb-container -->

<button class="back-top" id="kbBackTop">&#8593;</button>

</div><!-- .kb-wrap -->
@endsection

@section('javascript')
(function() {
  var wrap = document.querySelector('.kb-wrap');
  if (!wrap) return;

  // Active filters state
  var activeFilters = { audience: 'all', priority: 'all' };

  function switchTab(el, tab) {
    wrap.querySelectorAll('.kb-nav a').forEach(function(a) { a.classList.remove('active'); });
    el.classList.add('active');
    wrap.querySelectorAll('.kb-section').forEach(function(s) { s.style.display = 'none'; });
    document.getElementById('kb-section-' + tab).style.display = '';
    document.getElementById('kb-search').value = '';
    filterAll();
    // Update URL hash for tab
    if (tab !== 'scenarios') history.replaceState(null, '', '#' + tab);
  }

  function filterAll() {
    var q = document.getElementById('kb-search').value.toLowerCase().trim();
    var total = 0, visible = 0;

    wrap.querySelectorAll('#kb-scenarios-list .kb-card').forEach(function(card) {
      total++;
      var hidden = false;

      // Text search
      if (q) {
        var text = card.textContent.toLowerCase() + ' ' + (card.dataset.keywords || '');
        if (!text.includes(q)) hidden = true;
      }

      // Audience filter
      if (!hidden && activeFilters.audience !== 'all') {
        var aud = card.dataset.audience || '';
        if (aud.indexOf(activeFilters.audience) === -1) hidden = true;
      }

      // Priority filter
      if (!hidden && activeFilters.priority !== 'all') {
        if (card.dataset.priority !== activeFilters.priority) hidden = true;
      }

      card.classList.toggle('hidden', hidden);
      if (!hidden) visible++;
    });

    // Update search counter
    var counter = document.getElementById('kb-search-counter');
    if (counter) {
      if (q || activeFilters.audience !== 'all' || activeFilters.priority !== 'all') {
        counter.innerHTML = 'Найдено: <strong>' + visible + '</strong> из ' + total;
        counter.classList.add('visible');
      } else {
        counter.classList.remove('visible');
      }
    }

    // Filter dialogs by text search only
    wrap.querySelectorAll('#kb-dialogs-list .dialog-card').forEach(function(card) {
      if (!q) { card.classList.remove('hidden'); return; }
      var text = card.textContent.toLowerCase() + ' ' + (card.dataset.keywords || '');
      card.classList.toggle('hidden', !text.includes(q));
    });
  }

  function openScenario(id) {
    switchTab(wrap.querySelector('.kb-nav a:first-child'), 'scenarios');
    wrap.querySelectorAll('#kb-scenarios-list .kb-card').forEach(function(card) {
      if (card.dataset.id === id) {
        card.classList.add('open', 'kb-highlighted');
        setTimeout(function() { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 100);
        setTimeout(function() { card.classList.remove('kb-highlighted'); }, 2000);
        history.replaceState(null, '', '#' + id);
      }
    });
  }

  // Event delegation
  wrap.addEventListener('click', function(e) {
    // Tab links
    var tabEl = e.target.closest('[data-kb-tab]');
    if (tabEl) { e.preventDefault(); switchTab(tabEl, tabEl.dataset.kbTab); return; }

    // Scenario card headers — update permalink
    var cardHeader = e.target.closest('.kb-card-header');
    if (cardHeader) {
      var card = cardHeader.closest('.kb-card');
      card.classList.toggle('open');
      if (card.dataset.id) {
        history.replaceState(null, '', card.classList.contains('open') ? '#' + card.dataset.id : '#scenarios');
      }
      return;
    }

    // Dialog card headers
    var dialogHeader = e.target.closest('.dialog-header');
    if (dialogHeader) { dialogHeader.closest('.dialog-card').classList.toggle('open'); return; }

    // Tree scenario refs
    var scenRef = e.target.closest('[data-kb-scenario]');
    if (scenRef) { openScenario(scenRef.dataset.kbScenario); return; }

    // Filter buttons
    var filterBtn = e.target.closest('.kb-filter-btn');
    if (filterBtn) {
      var filterType = filterBtn.dataset.filter;
      var filterValue = filterBtn.dataset.value;
      activeFilters[filterType] = filterValue;
      // Update active state
      wrap.querySelectorAll('.kb-filter-btn[data-filter="' + filterType + '"]').forEach(function(b) {
        b.classList.toggle('active', b.dataset.value === filterValue);
      });
      filterAll();
      return;
    }

    // Expand/collapse all
    var toggleBtn = e.target.closest('.kb-toggle-all');
    if (toggleBtn) {
      var target = toggleBtn.dataset.target;
      var selector = target === 'scenarios' ? '#kb-scenarios-list .kb-card' : '#kb-dialogs-list .dialog-card';
      var cards = wrap.querySelectorAll(selector);
      var anyOpen = false;
      cards.forEach(function(c) { if (c.classList.contains('open')) anyOpen = true; });
      cards.forEach(function(c) {
        if (anyOpen) c.classList.remove('open');
        else c.classList.add('open');
      });
      toggleBtn.textContent = anyOpen ? 'Развернуть все' : 'Свернуть все';
      return;
    }

    // Copy buttons
    var copyBtn = e.target.closest('.copy-btn');
    if (copyBtn) {
      var block = copyBtn.closest('.dialog-block');
      var label = block.querySelector('.dialog-block-label');
      var text = block.textContent.replace(label.textContent, '').replace('Копировать', '').trim();
      navigator.clipboard.writeText(text).then(function() {
        copyBtn.textContent = 'Скопировано!';
        copyBtn.classList.add('copied');
        setTimeout(function() { copyBtn.textContent = 'Копировать'; copyBtn.classList.remove('copied'); }, 1500);
      });
      return;
    }

    // Back to top
    if (e.target.closest('#kbBackTop')) { window.scrollTo({top:0, behavior:'smooth'}); return; }
  });

  // Search input
  var searchInput = document.getElementById('kb-search');
  if (searchInput) searchInput.addEventListener('input', filterAll);

  // Back to top visibility
  window.addEventListener('scroll', function() {
    var btn = document.getElementById('kbBackTop');
    if (btn) btn.classList.toggle('visible', window.scrollY > 300);
  });

  // Ctrl+K to focus search
  document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      if (searchInput) { e.preventDefault(); searchInput.focus(); }
    }
  });

  // Handle permalink on page load
  var hash = window.location.hash.replace('#', '');
  if (hash) {
    if (hash === 'tree' || hash === 'dialogs') {
      var tabLink = wrap.querySelector('[data-kb-tab="' + hash + '"]');
      if (tabLink) switchTab(tabLink, hash);
    } else if (hash === 'scenarios') {
      // default tab, do nothing
    } else {
      // Scenario ID (e.g. S05)
      setTimeout(function() { openScenario(hash); }, 100);
    }
  }
})();
@endsection
