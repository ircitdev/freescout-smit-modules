@extends('layouts.app')

@section('title', 'Диалог #' . $conversation->id)

@section('content')
<style>
.ai-info-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
.ai-info-item {
    flex: 1; min-width: 200px;
    background: #fff; padding: 12px 14px; border-radius: 8px; border: 1px solid #e8e8e8;
}
.ai-info-item .lbl { color: #999; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
.ai-info-item .val { font-size: 14px; color: #333; }
.ai-info-item .pair { display: flex; flex-direction: column; gap: 8px; }
.ai-info-item .pair .half .lbl { margin-bottom: 2px; }
.ai-info-item.err { border-color: #ffcdd2; }
.ai-info-item.err .lbl { color: #c62828; }
.ai-info-item.err .val { color: #c62828; }

/* Chat container — стиль assistant.example.com */
.ai-chat-container {
    max-width: 700px; margin: 0 auto 20px;
    background: linear-gradient(180deg, #e8edce 0%, #f5f7eb 100%);
    border-radius: 16px; border: 1px solid #d4dbb0;
    overflow: hidden; box-shadow: 0 2px 12px rgba(100,126,52,.12);
}
.ai-chat-header {
    background: linear-gradient(135deg, #84A73F 0%, #657E34 100%);
    color: #fff; padding: 14px 20px; text-align: center;
}
.ai-chat-header h4 { margin: 0; font-size: 15px; font-weight: 600; }
.ai-chat-header .sub { font-size: 11px; opacity: .8; margin-top: 2px; }
.ai-chat-messages { padding: 20px; display: flex; flex-direction: column; gap: 10px; }

.ai-msg { padding: 12px 16px; border-radius: 12px; max-width: 82%; position: relative; line-height: 1.6; font-size: 13.5px; }
.ai-msg-user {
    background: linear-gradient(190deg, rgba(132,167,63,.6) 0%, rgba(101,126,52,.82) 100%);
    color: #fff; margin-left: auto; border-top-right-radius: 3px;
}
.ai-msg-assistant {
    background: linear-gradient(#f0f0f0a6, #f0f0f0);
    color: #333; border: none; border-top-left-radius: 3px;
    box-shadow: 0 0 8px rgba(205,209,183,.6);
}
.ai-msg-role {
    font-size: 10px; margin-bottom: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: .3px;
}
.ai-msg-user .ai-msg-role { color: rgba(255,255,255,.75); }
.ai-msg-assistant .ai-msg-role { color: #8a9a5e; }
.ai-msg-text { white-space: pre-wrap; word-break: break-word; }

.ai-status-active { color: #4caf50; font-weight: 500; }
.ai-status-completed { color: #2196f3; font-weight: 500; }
.ai-status-timeout { color: #ff9800; font-weight: 500; }
.ai-status-abandoned { color: #9e9e9e; font-weight: 500; }

.ai-badge-ok { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; background: #e8f5e9; color: #2e7d32; }
.ai-badge-no { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; background: #ffebee; color: #c62828; }
.ai-badge-smit { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; background: #e8f5e9; color: #2e7d32; }
.ai-badge-robor { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; background: #e3f2fd; color: #1565c0; }
.ai-badge-voice { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; background: #f3e5f5; color: #6a1b9a; }
.ai-badge-text-type { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; background: #e8eaf6; color: #283593; }

.ai-device { background: #f5f5f5; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; border: 1px solid #e0e0e0; }
.ai-device .title { font-weight: 600; font-size: 13px; color: #666; margin-bottom: 8px; }
.ai-device .row-item { display: inline-block; margin-right: 16px; font-size: 13px; color: #333; }
.ai-device .row-item .dlbl { color: #999; font-size: 11px; }

.ai-links { margin-bottom: 20px; }
.ai-links a { display: inline-block; margin-right: 12px; padding: 6px 14px; border-radius: 6px; font-size: 13px; text-decoration: none; }
.ai-link-ticket { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
.ai-link-ticket:hover { background: #c8e6c9; }
.ai-link-lead { background: #fff3e0; color: #e65100; border: 1px solid #ffe0b2; }
.ai-link-lead:hover { background: #ffe0b2; }
</style>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="section-heading margin-bottom" style="display:flex; align-items:center; flex-wrap:wrap; gap:10px;">
                <a href="{{ route('aidasessions.index') }}" class="btn btn-default btn-sm">
                    <i class="glyphicon glyphicon-arrow-left"></i> Назад
                </a>
                <h1 style="display:inline-block; margin:0;">Диалог #{{ $conversation->id }}</h1>

                {{-- Badges --}}
                @if ($conversation->is_voice)
                    <span class="ai-badge-voice">&#127908; Голосовой AI</span>
                @else
                    <span class="ai-badge-text-type">&#128172; Текстовый AI</span>
                @endif

                @if ($conversation->provider === 'РОБОР')
                    <span class="ai-badge-robor">РОБОР</span>
                @elseif ($conversation->provider === 'СМИТ')
                    <span class="ai-badge-smit">СМИТ</span>
                @endif

                <div style="margin-left:auto;">
                    <a href="{{ route('aidasessions.download', $conversation->id) }}" class="btn btn-primary btn-sm">
                        <i class="glyphicon glyphicon-download-alt"></i> Скачать .md
                    </a>
                    @if (auth()->user() && auth()->user()->isAdmin())
                        @if ($conversation->ticket_created && $conversation->ticket_id)
                            <button type="button" class="btn btn-danger btn-sm" disabled title="Привязан тикет #{{ $conversation->ticket_id }}">
                                <i class="glyphicon glyphicon-trash"></i> Удалить
                            </button>
                        @else
                            <form method="POST" action="{{ route('aidasessions.delete', $conversation->id) }}" style="display:inline;" onsubmit="return confirm('Удалить диалог #{{ $conversation->id }}?')">
                                {{ csrf_field() }}
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="glyphicon glyphicon-trash"></i> Удалить
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Links to ticket/lead --}}
            @if ($conversation->ticket_created || $conversation->lead_created)
                <div class="ai-links">
                    @if ($conversation->ticket_created && $conversation->ticket_id)
                        <a href="https://support.example.com/conversation/{{ $conversation->ticket_id }}" target="_blank" class="ai-link-ticket">
                            <i class="glyphicon glyphicon-tag"></i> Тикет #{{ $conversation->ticket_id }}
                        </a>
                    @endif
                    @if ($conversation->lead_created && $conversation->lead_id)
                        <a href="https://your-account.amocrm.ru/leads/detail/{{ $conversation->lead_id }}" target="_blank" class="ai-link-lead">
                            <i class="glyphicon glyphicon-briefcase"></i> Лид #{{ $conversation->lead_id }}
                        </a>
                    @endif
                </div>
            @endif

            {{-- Device info + IP + UTM --}}
            @if ($conversation->device_type || $conversation->device_os || $conversation->device_browser || $conversation->ip_address || $conversation->ip_country || $conversation->utm_source)
                <div class="ai-device">
                    <div class="title"><i class="glyphicon glyphicon-phone"></i> Устройство</div>
                    @if ($conversation->device_type)
                        <div class="row-item"><span class="dlbl">Тип:</span> {{ $conversation->device_type }}</div>
                    @endif
                    @if ($conversation->device_os)
                        <div class="row-item"><span class="dlbl">ОС:</span> {{ $conversation->device_os }}</div>
                    @endif
                    @if ($conversation->device_browser)
                        <div class="row-item"><span class="dlbl">Браузер:</span> {{ $conversation->device_browser }}</div>
                    @endif
                    @if ($conversation->device_screen)
                        <div class="row-item"><span class="dlbl">Экран:</span> {{ $conversation->device_screen }}</div>
                    @endif
                    @if ($conversation->ip_address)
                        <div class="row-item"><span class="dlbl">IP:</span> {{ $conversation->ip_address }}@if ($conversation->ip_country)
                            @php
                                $cc = strtolower($conversation->ip_country);
                                $flag = implode('', array_map(fn($c) => mb_chr(0x1F1E6 + ord($c) - ord('a')), str_split($cc)));
                            @endphp
                            <span style="margin-left:6px">{{ $flag }} {{ $conversation->ip_city ? $conversation->ip_city . ', ' : '' }}{{ $conversation->ip_country }}</span>
                        @endif</div>
                    @endif
                    @if ($conversation->utm_source)
                        <div class="row-item"><span class="dlbl">UTM:</span> {{ $conversation->utm_source }}{{ $conversation->utm_medium ? ' / ' . $conversation->utm_medium : '' }}</div>
                    @endif
                </div>
            @endif

            @php
                $branchNames = ['support' => 'Поддержка', 'connection' => 'Подключение'];
            @endphp

            <div class="ai-info-grid">
                {{-- Статус + Ветка --}}
                <div class="ai-info-item">
                    <div class="pair">
                        <div class="half">
                            <div class="lbl">Статус</div>
                            <div class="val ai-status-{{ $conversation->status }}">{{ ucfirst($conversation->status) }}</div>
                        </div>
                        <div class="half">
                            <div class="lbl">Ветка</div>
                            <div class="val">{{ $branchNames[$conversation->branch] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                {{-- Начат + Сообщений --}}
                <div class="ai-info-item">
                    <div class="pair">
                        <div class="half">
                            <div class="lbl">Начат</div>
                            <div class="val">{{ date('d.m.Y H:i', strtotime($conversation->started_at)) }}</div>
                        </div>
                        <div class="half">
                            <div class="lbl">Сообщений</div>
                            <div class="val">{{ $conversation->messages_count }}</div>
                        </div>
                    </div>
                </div>
                {{-- Клиент + Телефон --}}
                <div class="ai-info-item">
                    <div class="pair">
                        <div class="half">
                            <div class="lbl">Клиент</div>
                            <div class="val">
                                @if ($customerId)
                                    <a href="{{ url('/customer/' . $customerId) }}" target="_blank" style="color:#337ab7; text-decoration:none; font-weight:500;">{{ $conversation->client_name ?: 'Контакт' }}</a>
                                @else
                                    {{ $conversation->client_name ?: '-' }}
                                @endif
                                @if ($conversation->company_name)
                                    / <span style="color:#1565c0; font-weight:500;">{{ $conversation->company_name }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="half">
                            <div class="lbl">Телефон</div>
                            <div class="val">{{ $conversation->client_phone ?: '-' }}</div>
                        </div>
                    </div>
                </div>
                {{-- Лид + Тикет --}}
                <div class="ai-info-item">
                    <div class="pair">
                        <div class="half">
                            <div class="lbl">Лид</div>
                            <div class="val">
                                @if ($conversation->lead_created)
                                    <a href="https://your-account.amocrm.ru/leads/detail/{{ $conversation->lead_id }}" target="_blank" class="ai-badge-ok" style="text-decoration:none">Да #{{ $conversation->lead_id }}</a>
                                @else
                                    <span class="ai-badge-no">Нет</span>
                                @endif
                            </div>
                        </div>
                        <div class="half">
                            <div class="lbl">Тикет</div>
                            <div class="val">
                                @if ($conversation->ticket_created)
                                    <a href="https://support.example.com/conversation/{{ $conversation->ticket_id }}" target="_blank" class="ai-badge-ok" style="text-decoration:none">Да #{{ $conversation->ticket_id }}</a>
                                @else
                                    <span class="ai-badge-no">Нет</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Адрес + Тариф --}}
                @if ($conversation->address || $conversation->tariff)
                <div class="ai-info-item">
                    <div class="pair">
                        <div class="half">
                            <div class="lbl">Адрес</div>
                            <div class="val">{{ $conversation->address ?: '-' }}</div>
                        </div>
                        <div class="half">
                            <div class="lbl">Тариф</div>
                            <div class="val">{{ $conversation->tariff ?: '-' }}</div>
                        </div>
                    </div>
                </div>
                @endif
                {{-- Источник (как узнали) --}}
                @if ($conversation->referrer)
                <div class="ai-info-item">
                    <div class="lbl">Как узнали</div>
                    <div class="val">📣 {{ $conversation->referrer }}</div>
                </div>
                @endif
                {{-- Ошибка --}}
                @if ($conversation->has_error)
                    <div class="ai-info-item err">
                        <div class="lbl">Ошибка</div>
                        <div class="val">{{ $conversation->error_message ?: 'Да' }}</div>
                    </div>
                @endif
            </div>

            <div class="ai-chat-container">
                <div class="ai-chat-header">
                    <h4>{{ $conversation->provider ?: 'SMIT' }} AI-ассистент</h4>
                    <div class="sub">{{ $conversation->is_voice ? 'Голосовой диалог' : 'Текстовый диалог' }} &bull; {{ $conversation->messages_count ?: 0 }} сообщений &bull; {{ date('d.m.Y H:i', strtotime($conversation->started_at)) }}</div>
                </div>

                <div class="ai-chat-messages">
                    @if ($messages->isEmpty())
                        <div class="text-center text-muted" style="padding: 30px;">
                            Сообщения ещё не записаны
                        </div>
                    @else
                        @foreach ($messages as $msg)
                            <div class="ai-msg ai-msg-{{ $msg->role }}">
                                <div class="ai-msg-role">
                                    {{ $msg->role === 'user' ? 'Клиент' : 'Бот' }} &bull; {{ date('H:i', strtotime($msg->created_at)) }}
                                </div>
                                <div class="ai-msg-text">{{ $msg->content }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
