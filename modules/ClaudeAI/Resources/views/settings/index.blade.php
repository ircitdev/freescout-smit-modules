@extends('layouts.app')

@section('title', 'Claude AI — Настройки')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title" style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:24px">🧠</span> Claude AI — Настройки
                    </h3>
                </div>
                <div class="panel-body">

                    @if(session('flash_success_floating'))
                        <div class="alert alert-success">{{ session('flash_success_floating') }}</div>
                    @endif

                    <form method="POST" action="{{ route('claudeai.settings.save') }}" id="claudeai-form">
                        {{ csrf_field() }}

                        {{-- API --}}
                        <fieldset style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:20px">
                            <legend style="font-size:15px;font-weight:600;color:#374151;padding:0 8px;width:auto">
                                🔑 API подключение
                            </legend>

                            <div class="form-group">
                                <label>API ключ Anthropic</label>
                                <div style="display:flex;gap:8px">
                                    <input type="password" name="claudeai_api_key" class="form-control"
                                           value="{{ $settings['api_key'] ? '••••••••' : '' }}"
                                           placeholder="sk-ant-api03-..." id="api-key-input">
                                    <button type="button" class="btn btn-default" onclick="var i=document.getElementById('api-key-input');i.type=i.type=='password'?'text':'password'">
                                        👁
                                    </button>
                                </div>
                                <p class="help-block">Получить на <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></p>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Модель</label>
                                        <select name="claudeai_model" class="form-control">
                                            @foreach($models as $value => $label)
                                                <option value="{{ $value }}" @if($settings['model'] == $value) selected @endif>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Max tokens</label>
                                        <input type="number" name="claudeai_max_tokens" class="form-control"
                                               value="{{ $settings['max_tokens'] }}" min="256" max="4096">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Temperature</label>
                                        <input type="number" name="claudeai_temperature" class="form-control"
                                               value="{{ $settings['temperature'] }}" min="0" max="1" step="0.1">
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-info" id="test-btn">
                                🔌 Проверить подключение
                            </button>
                            <span id="test-result" style="margin-left:10px"></span>
                        </fieldset>

                        {{-- Features --}}
                        <fieldset style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:20px">
                            <legend style="font-size:15px;font-weight:600;color:#374151;padding:0 8px;width:auto">
                                ⚙️ Функции
                            </legend>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="claudeai_operator_hint" value="1"
                                                   @if($settings['operator_hint'] == '1') checked @endif>
                                            <strong>🧠 Подсказка оператору</strong>
                                            <br><small class="text-muted">Кнопка AI-анализа в сайдбаре тикета</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="claudeai_auto_reply" value="1"
                                                   @if($settings['auto_reply'] == '1') checked @endif>
                                            <strong>✍️ Автоответ</strong>
                                            <br><small class="text-muted">Генерировать черновик ответа</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="claudeai_auto_classify" value="1"
                                                   @if($settings['auto_classify'] == '1') checked @endif>
                                            <strong>🏷️ Авто-классификация</strong>
                                            <br><small class="text-muted">Определять сценарий S01-S21</small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row" style="margin-top:10px">
                                <div class="col-md-4">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="claudeai_require_review" value="1"
                                                   @if($settings['require_review'] == '1') checked @endif>
                                            <strong>👀 Требовать проверку</strong>
                                            <br><small class="text-muted">Оператор проверяет перед отправкой</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="claudeai_use_kb" value="1"
                                                   @if($settings['use_kb'] == '1') checked @endif>
                                            <strong>📚 База знаний</strong>
                                            <br><small class="text-muted">Использовать статьи FreeScout KB</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Макс. автоответов на тикет</label>
                                        <input type="number" name="claudeai_max_auto_replies" class="form-control"
                                               value="{{ $settings['max_auto_replies'] }}" min="1" max="10">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top:10px">
                                <label>Ключевые слова эскалации</label>
                                <input type="text" name="claudeai_escalation_keywords" class="form-control"
                                       value="{{ $settings['escalation_keywords'] }}"
                                       placeholder="оператор,человек,живой,менеджер">
                                <p class="help-block">Через запятую. При наличии этих слов — не автоотвечать, передать оператору.</p>
                            </div>
                        </fieldset>

                        {{-- Billing integration --}}
                        <fieldset style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:20px">
                            <legend style="font-size:15px;font-weight:600;color:#374151;padding:0 8px;width:auto">
                                🔗 Интеграция с биллингом
                            </legend>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>URL Smit Billing</label>
                                        <input type="text" name="claudeai_billing_url" class="form-control"
                                               value="{{ $settings['billing_url'] }}"
                                               placeholder="https://billing.example.com">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>API токен</label>
                                        <input type="text" name="claudeai_billing_token" class="form-control"
                                               value="{{ $settings['billing_token'] }}" placeholder="Опционально">
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        {{-- Prompts --}}
                        <fieldset style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:20px">
                            <legend style="font-size:15px;font-weight:600;color:#374151;padding:0 8px;width:auto">
                                📝 System prompts
                            </legend>
                            <p class="text-muted" style="margin-bottom:12px">
                                Оставьте пустым для использования встроенных промптов. Заполните для кастомизации.
                            </p>

                            <div class="form-group">
                                <label>Промпт для клиентов (чат-бот)</label>
                                <textarea name="claudeai_customer_prompt" class="form-control" rows="6"
                                          placeholder="Оставьте пустым для встроенного промпта">{{ $settings['customer_prompt'] }}</textarea>
                            </div>

                            <div class="form-group">
                                <label>Промпт для операторов (подсказки)</label>
                                <textarea name="claudeai_operator_prompt" class="form-control" rows="6"
                                          placeholder="Оставьте пустым для встроенного промпта">{{ $settings['operator_prompt'] }}</textarea>
                            </div>
                        </fieldset>

                        <div style="display:flex;gap:10px;align-items:center">
                            <button type="submit" class="btn btn-primary">
                                💾 Сохранить настройки
                            </button>
                            <a href="{{ route('claudeai.stats') }}" class="btn btn-default">
                                📊 Статистика
                            </a>
                            <a href="{{ route('claudeai.docs') }}" class="btn btn-default">
                                📖 Документация
                            </a>
                        </div>
                    </form>

                </div>
            </div>

            {{-- Cost calculator --}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">💰 Калькулятор стоимости</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>Модель</th>
                                <th>Input (1M токенов)</th>
                                <th>Output (1M токенов)</th>
                                <th>~50 диалогов/день</th>
                                <th>~250 диалогов/день</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="success">
                                <td><strong>Haiku 4.5</strong></td>
                                <td>$0.80</td>
                                <td>$4.00</td>
                                <td><strong>~$11/мес</strong></td>
                                <td>~$55/мес</td>
                            </tr>
                            <tr>
                                <td>Sonnet 4.6</td>
                                <td>$3.00</td>
                                <td>$15.00</td>
                                <td>~$44/мес</td>
                                <td>~$220/мес</td>
                            </tr>
                            <tr>
                                <td>Opus 4.6</td>
                                <td>$15.00</td>
                                <td>$75.00</td>
                                <td>~$220/мес</td>
                                <td>~$1100/мес</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

@section('javascript')
    @parent
    testBtnEl = document.getElementById('test-btn');
    if (testBtnEl) {
        testBtnEl.addEventListener('click', function() {
            var btn = this;
            var result = document.getElementById('test-result');
            btn.disabled = true;
            btn.textContent = '⏳ Проверяю...';
            result.textContent = '';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '{{ route("claudeai.test") }}');
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            xhr.onload = function() {
                btn.disabled = false;
                btn.textContent = '🔌 Проверить подключение';
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        result.innerHTML = '<span style="color:#059669;font-weight:600">✅ Подключение работает! Модель: ' + (data.model || '') + '</span>';
                    } else {
                        result.innerHTML = '<span style="color:#dc2626;font-weight:600">❌ ' + (data.error || 'Ошибка') + '</span>';
                    }
                } catch(e) {
                    result.innerHTML = '<span style="color:#dc2626">❌ Ошибка: ' + xhr.status + '</span>';
                }
            };
            xhr.onerror = function() {
                btn.disabled = false;
                btn.textContent = '🔌 Проверить подключение';
                result.innerHTML = '<span style="color:#dc2626">❌ Ошибка соединения</span>';
            };
            xhr.send('{}');
        });
    }
@endsection
@endsection
