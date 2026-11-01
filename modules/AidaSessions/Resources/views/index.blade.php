@extends('layouts.app')

@section('title', 'AI-сессии')

@section('content')
<style>
.ai-stats { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
.ai-stat {
    flex: 1; min-width: 90px;
    background: #fff; padding: 12px 8px; border-radius: 8px;
    text-align: center; border: 1px solid #e8e8e8;
}
.ai-stat .num { font-size: 22px; font-weight: bold; color: #333; }
.ai-stat .lbl { color: #999; font-size: 11px; margin-top: 2px; }
.ai-stat.blue .num { color: #1565c0; }
.ai-stat.orange .num { color: #e65100; }
.ai-stat.green .num { color: #2e7d32; }
.ai-stat.red .num { color: #c62828; }
.ai-stat.purple .num { color: #6a1b9a; }

.ai-branch { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 500; }
.ai-branch-support { background: #e3f2fd; color: #1565c0; }
.ai-branch-connection { background: #fff3e0; color: #e65100; }

.ai-status-active { color: #4caf50; font-weight: 500; }
.ai-status-completed { color: #2196f3; font-weight: 500; }
.ai-status-timeout { color: #ff9800; font-weight: 500; }
.ai-status-abandoned { color: #9e9e9e; font-weight: 500; }

.ai-badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 10px; font-weight: 500; margin-right: 2px; }
.ai-badge-ok { background: #e8f5e9; color: #2e7d32; }
.ai-badge-err { background: #ffebee; color: #c62828; }
.ai-badge-smit { background: #e8f5e9; color: #2e7d32; }
.ai-badge-robor { background: #e3f2fd; color: #1565c0; }
.ai-badge-voice { background: #f3e5f5; color: #6a1b9a; }
.ai-badge-text { background: #e8eaf6; color: #283593; }
.ai-badge-biz { background: #fff3e0; color: #e65100; }

.table-ai td { vertical-align: middle; font-size: 13px; }
.table-ai .actions a, .table-ai .actions button { margin-right: 2px; }
.row-error { background: #fff8f8; }

.section-heading { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; }
.section-heading h1 { font-size: 18px; margin: 0; white-space: nowrap; }
.ai-period { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin: 0; padding: 0; background: transparent; border-radius: 0; border: none; margin-left: auto; }
.ai-period .btn-group .btn { font-size: 13px; }
.ai-period .btn-group .btn.active { background: #337ab7; color: #fff; border-color: #2e6da4; }
.ai-period .date-inputs { display: flex; align-items: center; gap: 6px; margin-left: 8px; }
.ai-period .date-inputs input[type="date"] { padding: 5px 8px; border: 1px solid rgba(255,255,255,.4); border-radius: 4px; font-size: 13px; height: 30px; background: rgba(255,255,255,.85); }
.ai-period-label { color: rgba(255,255,255,.85); font-size: 13px; margin-right: 4px; }

.ai-filters { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; padding: 10px 14px; background: #fff; border-radius: 8px; border: 1px solid #e8e8e8; align-items: center; }
.ai-filters .flabel { color: #666; font-size: 12px; margin-right: 2px; }
.ai-filters .btn-group { margin-right: 10px; }
.ai-filters .btn-group .btn { font-size: 12px; padding: 3px 10px; }
.ai-filters .btn-group .btn.active { background: #5cb85c; color: #fff; border-color: #4cae4c; }

.ai-link { font-size: 11px; }

/* ===== Mobile cards ===== */
.ai-cards { display: none; }
.ai-card {
    background: #fff; border-radius: 10px; border: 1px solid #e8e8e8;
    padding: 14px 16px; margin-bottom: 10px; cursor: pointer;
    transition: box-shadow .15s, border-color .15s;
    text-decoration: none; display: block; color: inherit;
}
.ai-card:hover, .ai-card:focus { border-color: #b0bec5; box-shadow: 0 2px 8px rgba(0,0,0,.08); text-decoration: none; color: inherit; }
.ai-card.row-error { background: #fff8f8; border-color: #ffcdd2; }
.ai-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.ai-card-id { font-weight: 700; font-size: 14px; color: #333; }
.ai-card-date { font-size: 12px; color: #999; }
.ai-card-mid { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 8px; }
.ai-card-client { font-size: 14px; font-weight: 500; color: #333; }
.ai-card-bot { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.ai-card-msgs { font-size: 12px; color: #999; margin-left: auto; white-space: nowrap; }
.ai-card-links { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
.ai-card-actions { display: flex; gap: 6px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #f0f0f0; }
.ai-card-actions a, .ai-card-actions button { font-size: 12px; }

/* ===== Desktop: clickable rows ===== */
.table-ai tr[data-href] { cursor: pointer; }
.table-ai tr[data-href]:hover { background: #f5f8fa; }

@media (max-width: 768px) {
    .ai-table-desktop { display: none !important; }
    .ai-cards { display: block !important; }

    .ai-stats { gap: 6px; }
    .ai-stat { min-width: 60px; padding: 8px 4px; }
    .ai-stat .num { font-size: 18px; }
    .ai-stat .lbl { font-size: 10px; }

    .section-heading { flex-direction: column; align-items: flex-start; gap: 8px; padding-bottom: 12px; }
    .section-heading h1 { font-size: 16px !important; }
    .ai-period { width: 100%; padding: 0; gap: 6px; margin-left: 0; }
    .ai-period .date-inputs { margin-left: 0; width: 100%; }
    .ai-period .date-inputs input[type="date"] { flex: 1; min-width: 0; }
    .ai-period-label { color: rgba(255,255,255,.7); }

    .ai-filters { padding: 8px 10px; gap: 4px; }
    .ai-filters .flabel { font-size: 11px; width: 100%; margin-top: 4px; }
    .ai-filters .flabel:first-child { margin-top: 0; }
    .ai-filters .btn-group { margin-right: 0; }
    .ai-filters .btn-group .btn { font-size: 11px; padding: 3px 8px; }

    .container { padding-left: 10px; padding-right: 10px; }
}
</style>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="section-heading margin-bottom">
                <h1><i class="glyphicon glyphicon-comment"></i> AI-сессии</h1>
                {{-- Period selector (inside heading on desktop) --}}
                <form method="GET" action="{{ route('aidasessions.index') }}" class="ai-period" id="periodForm">
                    <input type="hidden" name="provider" value="{{ $filterProvider }}">
                    <input type="hidden" name="type" value="{{ $filterType }}">
                    <input type="hidden" name="linked" value="{{ $filterLinked }}">
                    <span class="ai-period-label">Период:</span>
                    <div class="btn-group">
                        <button type="submit" name="period" value="day" class="btn btn-default btn-sm {{ $period === 'day' ? 'active' : '' }}">Сутки</button>
                        <button type="submit" name="period" value="week" class="btn btn-default btn-sm {{ $period === 'week' ? 'active' : '' }}">Неделя</button>
                        <button type="submit" name="period" value="month" class="btn btn-default btn-sm {{ $period === 'month' ? 'active' : '' }}">Месяц</button>
                    </div>
                    <div class="date-inputs">
                        <input type="date" name="date_from" value="{{ $dateFrom }}">
                        <span style="color:#999;">&mdash;</span>
                        <input type="date" name="date_to" value="{{ $dateTo }}">
                        <button type="submit" name="period" value="custom" class="btn btn-primary btn-sm">
                            <i class="glyphicon glyphicon-search"></i> Показать
                        </button>
                    </div>
                </form>
            </div>

            {{-- Filters --}}
            <div class="ai-filters">
                <span class="flabel">Провайдер:</span>
                <div class="btn-group">
                    <a href="{{ route('aidasessions.index', array_merge(request()->all(), ['provider' => ''])) }}" class="btn btn-default btn-xs {{ $filterProvider === '' ? 'active' : '' }}">Все</a>
                    <a href="{{ route('aidasessions.index', array_merge(request()->all(), ['provider' => 'smit'])) }}" class="btn btn-default btn-xs {{ $filterProvider === 'smit' ? 'active' : '' }}">СМИТ</a>
                    <a href="{{ route('aidasessions.index', array_merge(request()->all(), ['provider' => 'robor'])) }}" class="btn btn-default btn-xs {{ $filterProvider === 'robor' ? 'active' : '' }}">РОБОР</a>
                </div>
                <span class="flabel">Тип:</span>
                <div class="btn-group">
                    <a href="{{ route('aidasessions.index', array_merge(request()->all(), ['type' => ''])) }}" class="btn btn-default btn-xs {{ $filterType === '' ? 'active' : '' }}">Все</a>
                    <a href="{{ route('aidasessions.index', array_merge(request()->all(), ['type' => 'text'])) }}" class="btn btn-default btn-xs {{ $filterType === 'text' ? 'active' : '' }}">Текст</a>
                    <a href="{{ route('aidasessions.index', array_merge(request()->all(), ['type' => 'voice'])) }}" class="btn btn-default btn-xs {{ $filterType === 'voice' ? 'active' : '' }}">Голос</a>
                </div>
                <span class="flabel">Привязка:</span>
                <div class="btn-group">
                    <a href="{{ route('aidasessions.index', array_merge(request()->all(), ['linked' => ''])) }}" class="btn btn-default btn-xs {{ $filterLinked === '' ? 'active' : '' }}">Все</a>
                    <a href="{{ route('aidasessions.index', array_merge(request()->all(), ['linked' => 'ticket'])) }}" class="btn btn-default btn-xs {{ $filterLinked === 'ticket' ? 'active' : '' }}">С тикетом</a>
                    <a href="{{ route('aidasessions.index', array_merge(request()->all(), ['linked' => 'lead'])) }}" class="btn btn-default btn-xs {{ $filterLinked === 'lead' ? 'active' : '' }}">С лидом</a>
                    <a href="{{ route('aidasessions.index', array_merge(request()->all(), ['linked' => 'none'])) }}" class="btn btn-default btn-xs {{ $filterLinked === 'none' ? 'active' : '' }}">Без привязки</a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="ai-stats">
                <div class="ai-stat">
                    <div class="num">{{ $stats->total ?: 0 }}</div>
                    <div class="lbl">Всего</div>
                </div>
                <div class="ai-stat green">
                    <div class="num">{{ $stats->today ?: 0 }}</div>
                    <div class="lbl">Сегодня</div>
                </div>
                <div class="ai-stat">
                    <div class="num">{{ $stats->text_count ?: 0 }}</div>
                    <div class="lbl">Текст</div>
                </div>
                <div class="ai-stat purple">
                    <div class="num">{{ $stats->voice ?: 0 }}</div>
                    <div class="lbl">Голос</div>
                </div>
                <div class="ai-stat green">
                    <div class="num">{{ $stats->smit ?: 0 }}</div>
                    <div class="lbl">СМИТ</div>
                </div>
                <div class="ai-stat blue">
                    <div class="num">{{ $stats->robor ?: 0 }}</div>
                    <div class="lbl">РОБОР</div>
                </div>
                <div class="ai-stat">
                    <div class="num">{{ $stats->tickets ?: 0 }}</div>
                    <div class="lbl">Тикеты</div>
                </div>
                <div class="ai-stat orange">
                    <div class="num">{{ $stats->leads ?: 0 }}</div>
                    <div class="lbl">Лиды</div>
                </div>
                <div class="ai-stat red">
                    <div class="num">{{ $stats->errors ?: 0 }}</div>
                    <div class="lbl">Ошибки</div>
                </div>
            </div>

            @if ($conversations->isEmpty())
                <div class="text-center text-muted" style="padding: 40px;">
                    <p>Диалогов не найдено за выбранный период</p>
                </div>
            @else
                {{-- Desktop table --}}
                <table class="table table-striped table-ai ai-table-desktop">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Дата</th>
                            <th>Клиент</th>
                            <th>Тип</th>
                            <th>Ветка</th>
                            <th>Сообщ.</th>
                            <th>Статус</th>
                            <th>Связи</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conversations as $conv)
                            <tr class="{{ $conv->has_error ? 'row-error' : '' }}" data-href="{{ route('aidasessions.show', $conv->id) }}">
                                <td>
                                    <a href="{{ route('aidasessions.show', $conv->id) }}" style="color:#333; font-weight:600; text-decoration:none;">#{{ $conv->id }}</a>
                                    @if ($conv->has_error)
                                        <span style="color:#c62828;" title="Ошибка">&#9888;</span>
                                    @endif
                                </td>
                                <td>{{ date('d.m H:i', strtotime($conv->started_at)) }}</td>
                                <td>{{ $conv->client_name ?: ($conv->client_phone ?: 'Аноним') }}@if($conv->company_name) / <span style="color:#1565c0;">{{ $conv->company_name }}</span>@endif</td>
                                <td>
                                    @if ($conv->is_voice)
                                        <span class="ai-badge ai-badge-voice" title="Голосовой AI">&#127908;</span>
                                    @else
                                        <span class="ai-badge ai-badge-text" title="Текстовый AI">&#128172;</span>
                                    @endif
                                    @if ($conv->provider === 'РОБОР')
                                        <span class="ai-badge ai-badge-robor">РБР</span>
                                    @elseif ($conv->provider === 'СМИТ')
                                        <span class="ai-badge ai-badge-smit">СМИТ</span>
                                    @endif
                                    @if ($conv->company_name)
                                        <span class="ai-badge ai-badge-biz">бизнес</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($conv->branch === 'support')
                                        <span class="ai-branch ai-branch-support">Поддержка</span>
                                    @elseif ($conv->branch === 'connection')
                                        <span class="ai-branch ai-branch-connection">Подключение</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $conv->messages_count }}</td>
                                <td class="ai-status-{{ $conv->status }}">{{ ucfirst($conv->status) }}</td>
                                <td>
                                    @if ($conv->ticket_created && $conv->ticket_id)
                                        <a href="https://support.example.com/conversation/{{ $conv->ticket_id }}" target="_blank" class="ai-link" title="Тикет #{{ $conv->ticket_id }}" onclick="event.stopPropagation()">
                                            <span class="ai-badge ai-badge-ok">T#{{ $conv->ticket_id }}</span>
                                        </a>
                                    @endif
                                    @if ($conv->lead_created && $conv->lead_id)
                                        <a href="https://your-account.amocrm.ru/leads/detail/{{ $conv->lead_id }}" target="_blank" class="ai-link" title="Лид #{{ $conv->lead_id }}" onclick="event.stopPropagation()">
                                            <span class="ai-badge" style="background:#fff3e0;color:#e65100;">L#{{ $conv->lead_id }}</span>
                                        </a>
                                    @endif
                                    @if (!$conv->ticket_created && !$conv->lead_created)
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td class="actions" onclick="event.stopPropagation()">
                                    <a href="{{ route('aidasessions.show', $conv->id) }}" class="btn btn-xs btn-default" title="Открыть"><i class="glyphicon glyphicon-eye-open"></i></a>
                                    <a href="{{ route('aidasessions.download', $conv->id) }}" class="btn btn-xs btn-primary" title="Скачать .md"><i class="glyphicon glyphicon-download-alt"></i></a>
                                    @if (auth()->user() && auth()->user()->isAdmin())
                                        @if ($conv->ticket_created && $conv->ticket_id)
                                            <button type="button" class="btn btn-xs btn-danger" disabled title="Привязан тикет #{{ $conv->ticket_id }}"><i class="glyphicon glyphicon-trash"></i></button>
                                        @else
                                            <form method="POST" action="{{ route('aidasessions.delete', $conv->id) }}" style="display:inline;" data-confirm="Удалить диалог #{{ $conv->id }}?">
                                                {{ csrf_field() }}
                                                <button type="submit" class="btn btn-xs btn-danger" title="Удалить"><i class="glyphicon glyphicon-trash"></i></button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Mobile cards --}}
                <div class="ai-cards">
                    @foreach ($conversations as $conv)
                        <a href="{{ route('aidasessions.show', $conv->id) }}" class="ai-card {{ $conv->has_error ? 'row-error' : '' }}">
                            <div class="ai-card-top">
                                <span class="ai-card-id">
                                    #{{ $conv->id }}
                                    @if ($conv->has_error)
                                        <span style="color:#c62828;" title="Ошибка">&#9888;</span>
                                    @endif
                                </span>
                                <span class="ai-card-date">{{ date('d.m H:i', strtotime($conv->started_at)) }}</span>
                            </div>
                            <div class="ai-card-mid">
                                <span class="ai-card-client">{{ $conv->client_name ?: ($conv->client_phone ?: 'Аноним') }}@if($conv->company_name) / <span style="color:#1565c0;">{{ $conv->company_name }}</span>@endif</span>
                                <span class="ai-card-msgs">{{ $conv->messages_count }} сообщ.</span>
                            </div>
                            <div class="ai-card-bot">
                                @if ($conv->is_voice)
                                    <span class="ai-badge ai-badge-voice">&#127908;</span>
                                @else
                                    <span class="ai-badge ai-badge-text">&#128172;</span>
                                @endif
                                @if ($conv->provider === 'РОБОР')
                                    <span class="ai-badge ai-badge-robor">РБР</span>
                                @elseif ($conv->provider === 'СМИТ')
                                    <span class="ai-badge ai-badge-smit">СМИТ</span>
                                @endif
                                @if ($conv->company_name)
                                    <span class="ai-badge ai-badge-biz">бизнес</span>
                                @endif
                                @if ($conv->branch === 'support')
                                    <span class="ai-branch ai-branch-support">Поддержка</span>
                                @elseif ($conv->branch === 'connection')
                                    <span class="ai-branch ai-branch-connection">Подключение</span>
                                @endif
                                <span class="ai-status-{{ $conv->status }}" style="font-size:12px;">{{ ucfirst($conv->status) }}</span>
                            </div>
                            @if ($conv->ticket_created || $conv->lead_created)
                                <div class="ai-card-links">
                                    @if ($conv->ticket_created && $conv->ticket_id)
                                        <span class="ai-badge ai-badge-ok">T#{{ $conv->ticket_id }}</span>
                                    @endif
                                    @if ($conv->lead_created && $conv->lead_id)
                                        <span class="ai-badge" style="background:#fff3e0;color:#e65100;">L#{{ $conv->lead_id }}</span>
                                    @endif
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('javascript')
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (form.dataset.confirm && !confirm(form.dataset.confirm)) {
            e.preventDefault();
        }
    });
    document.querySelectorAll('.table-ai tr[data-href]').forEach(function(row) {
        row.addEventListener('click', function() {
            window.location = this.dataset.href;
        });
    });
@endsection
