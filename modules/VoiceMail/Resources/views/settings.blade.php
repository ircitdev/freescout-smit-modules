@extends('layouts.app')

@section('title', 'Голосовая почта')

@section('content')
<style>
.audio-menu { margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ddd; }
.audio-menu p { margin-bottom: 8px; font-size: 12px; }
.audio-menu audio { width: 100%; height: 28px; margin-bottom: 6px; }
.audio-label { font-weight: bold; color: #666; margin-bottom: 2px; display: block; font-size: 11px; }
</style>
<div class="container">
    <div class="row">
        <div class="col-md-12">

            <div class="section-heading margin-bottom">
                <h1><i class="glyphicon glyphicon-earphone"></i> Голосовая почта</h1>
            </div>

            {{-- Телефонные линии --}}
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="glyphicon glyphicon-phone-alt"></i> Телефонные линии</h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="panel panel-warning">
                                <div class="panel-heading">
                                    <h4 class="panel-title"><i class="glyphicon glyphicon-home"></i> 55-35-59 (B2C)</h4>
                                </div>
                                <div class="panel-body">
                                    <p><strong>Полный номер:</strong> <code>55-35-59</code></p>
                                    <p><strong>Внутренний ID:</strong> <code>70000000001</code></p>
                                    <hr>
                                    <p><strong>Назначение:</strong> Основной номер для физических лиц</p>
                                    <p><strong>AmoCRM Pipeline:</strong> <span class="label label-warning">Интернет SMIT (B2C)</span></p>
                                    <p><strong>Pipeline ID:</strong> <code>0</code></p>
                                    <p><strong>UTM Medium:</strong> <code>55-35-59</code></p>
                                    <div class="audio-menu">
                                        <p><i class="glyphicon glyphicon-volume-up"></i> <strong>Голосовое меню:</strong></p>
                                        <span class="audio-label">Приветствие</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                        <span class="audio-label">Подключить интернет</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                        <span class="audio-label">Поддержка</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    <h4 class="panel-title"><i class="glyphicon glyphicon-headphones"></i> 70000000002</h4>
                                </div>
                                <div class="panel-body">
                                    <p><strong>Полный номер:</strong> <code>+7 (937) 555-11-22</code></p>
                                    <p><strong>Внутренний ID:</strong> <code>70000000002</code></p>
                                    <hr>
                                    <p><strong>Назначение:</strong> Линия поддержки</p>
                                    <p><strong>AmoCRM Pipeline:</strong> <span class="label label-warning">Интернет SMIT (B2C)</span></p>
                                    <p><strong>Pipeline ID:</strong> <code>0</code></p>
                                    <p><strong>UTM Medium:</strong> <code>70000000002</code></p>
                                    <div class="audio-menu">
                                        <p><i class="glyphicon glyphicon-volume-up"></i> <strong>Голосовое меню:</strong></p>
                                        <span class="audio-label">Приветствие</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                        <span class="audio-label">Подключить интернет</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                        <span class="audio-label">Поддержка</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h4 class="panel-title"><i class="glyphicon glyphicon-briefcase"></i> 00-00-01 (B2B)</h4>
                                </div>
                                <div class="panel-body">
                                    <p><strong>Полный номер:</strong> <code>+7 (XXX) 000-00-01</code></p>
                                    <p><strong>Внутренний ID:</strong> <code>70000000003</code></p>
                                    <hr>
                                    <p><strong>Назначение:</strong> Юридические лица / Корпоративные клиенты</p>
                                    <p><strong>AmoCRM Pipeline:</strong> <span class="label label-primary">B2B/Корп</span></p>
                                    <p><strong>Pipeline ID:</strong> <code>0</code></p>
                                    <p><strong>UTM Medium:</strong> <code>00-00-01</code></p>
                                    <div class="audio-menu">
                                        <p><i class="glyphicon glyphicon-volume-up"></i> <strong>Голосовое меню:</strong></p>
                                        <span class="audio-label">Приветствие</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                        <span class="audio-label">Подключить интернет</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                        <span class="audio-label">Поддержка</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 00-00-02 --}}
                        <div class="col-md-4">
                            <div class="panel panel-danger">
                                <div class="panel-heading" style="background-color: #F48FB1; border-color: #F48FB1;">
                                    <h4 class="panel-title" style="color: white;"><i class="glyphicon glyphicon-earphone"></i> 00-00-02</h4>
                                </div>
                                <div class="panel-body">
                                    <p><strong>Полный номер:</strong> <code>+7 (XXX) 000-00-02</code></p>
                                    <p><strong>Внутренний ID:</strong> <code>70000000004</code></p>
                                    <hr>
                                    <p><strong>Назначение:</strong> ROBOR</p>
                                    <p><strong>AmoCRM Pipeline:</strong> <span class="label label-warning">Интернет SMIT (B2C)</span></p>
                                    <p><strong>UTM Medium:</strong> <code>00-00-02</code></p>
                                    <p><strong>Pipeline ID:</strong> <code>0</code></p>
                                    <p><strong>Цвет лейбла:</strong> <span style="background-color: #F48FB1; color: white; padding: 2px 8px; border-radius: 3px;">Розовый</span></p>
                                    <div class="audio-menu">
                                        <p><i class="glyphicon glyphicon-volume-up"></i> <strong>Голосовое меню:</strong></p>
                                        <span class="audio-label">Приветствие</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                        <span class="audio-label">Подключить интернет</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                        <span class="audio-label">Поддержка</span>
                                        <audio controls preload="metadata" crossorigin="anonymous"><source src="https://example.com/audio/" type="audio/mpeg"></audio>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <strong><i class="glyphicon glyphicon-info-sign"></i> Как определяется номер:</strong>
                        Номер линии извлекается из email письма Манго Телеком (поле <code>sip:user@vpbx</code> или тема письма).
                        В зависимости от номера выбирается воронка AmoCRM и UTM-метки.
                    </div>
                </div>
            </div>

            {{-- Описание модуля --}}
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="glyphicon glyphicon-info-sign"></i> Описание модуля</h3>
                </div>
                <div class="panel-body">
                    <p>Модуль автоматически обрабатывает входящие голосовые сообщения из <strong>Манго Телеком</strong> и выполняет следующие действия:</p>
                    <ol>
                        <li><strong>Получает письмо</strong> от Манго Телеком с вложенным MP3-файлом голосового сообщения</li>
                        <li><strong>Извлекает номер телефона</strong> звонящего из темы письма</li>
                        <li><strong>Расшифровывает аудио</strong> через OpenAI Whisper API</li>
                        <li><strong>Нормализует текст</strong> через GPT (исправляет ошибки распознавания)</li>
                        <li><strong>Фильтрует мусорные сообщения</strong> (односложные ответы, бессмыслица)</li>
                        <li><strong>Классифицирует обращение</strong> - поддержка существующего клиента или заявка на подключение</li>
                        <li><strong>Создаёт тикет</strong> в нужном почтовом ящике FreeScout</li>
                        <li><strong>Для заявок на подключение</strong> - валидирует адрес и создаёт контакт и лид в AmoCRM</li>
                    </ol>
                </div>
            </div>

            {{-- Форма настроек --}}
            <form class="form-horizontal" method="POST" action="">
                {{ csrf_field() }}

                {{-- Основные настройки --}}
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="glyphicon glyphicon-cog"></i> Основные настройки</h3>
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Модуль включен</label>
                            <div class="col-sm-9">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="settings[voicemail.enabled]"
                                               @if (old('settings[voicemail.enabled]', $settings['voicemail.enabled'] ?? 'on') == 'on') checked @endif>
                                        Обрабатывать входящие голосовые сообщения
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="voicemail.mailbox_id" class="col-sm-3 control-label">Mailbox ID (Входящие звонки)</label>
                            <div class="col-sm-3">
                                <input type="number" class="form-control" id="voicemail.mailbox_id"
                                       name="settings[voicemail.mailbox_id]"
                                       value="{{ old('settings.voicemail.mailbox_id', $settings['voicemail.mailbox_id'] ?? '7') }}">
                            </div>
                            <div class="col-sm-6">
                                <p class="help-block">Почтовый ящик "Звонки", куда приходят письма от МТТ. <strong>Текущее: 7</strong></p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="voicemail.support_mailbox_id" class="col-sm-3 control-label">Mailbox ID (Поддержка)</label>
                            <div class="col-sm-3">
                                <input type="number" class="form-control" id="voicemail.support_mailbox_id"
                                       name="settings[voicemail.support_mailbox_id]"
                                       value="{{ old('settings.voicemail.support_mailbox_id', $settings['voicemail.support_mailbox_id'] ?? '1') }}">
                            </div>
                            <div class="col-sm-6">
                                <p class="help-block">Ящик "Поддержка клиентов" для тикетов поддержки. <strong>Текущее: 1</strong></p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="voicemail.connection_mailbox_id" class="col-sm-3 control-label">Mailbox ID (Подключение)</label>
                            <div class="col-sm-3">
                                <input type="number" class="form-control" id="voicemail.connection_mailbox_id"
                                       name="settings[voicemail.connection_mailbox_id]"
                                       value="{{ old('settings.voicemail.connection_mailbox_id', $settings['voicemail.connection_mailbox_id'] ?? '5') }}">
                            </div>
                            <div class="col-sm-6">
                                <p class="help-block">Ящик "Подключение" для заявок на подключение. <strong>Текущее: 5</strong></p>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- OpenAI --}}
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="glyphicon glyphicon-cloud"></i> OpenAI API</h3>
                    </div>
                    <div class="panel-body">

                        {{-- Статус OpenAI API --}}
                        @if(isset($openaiStatus))
                            @if(isset($openaiStatus['success']) && $openaiStatus['success'])
                                <div class="alert alert-success">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <strong><i class="glyphicon glyphicon-ok-circle"></i> API ключ активен</strong><br>
                                            <small>Ключ: <code>{{ $openaiStatus['key_preview'] ?? 'N/A' }}</code></small>
                                        </div>
                                        <div class="col-sm-6 text-right">
                                            <span class="label {{ $openaiStatus['has_whisper'] ? 'label-success' : 'label-danger' }}">
                                                Whisper {{ $openaiStatus['has_whisper'] ? 'OK' : 'N/A' }}
                                            </span>
                                            <span class="label {{ $openaiStatus['has_gpt4o_mini'] ? 'label-success' : 'label-danger' }}">
                                                GPT-4o-mini {{ $openaiStatus['has_gpt4o_mini'] ? 'OK' : 'N/A' }}
                                            </span>
                                            <br><small>Доступно моделей: {{ $openaiStatus['models_count'] ?? 0 }}</small>
                                        </div>
                                    </div>
                                </div>
                            @elseif(isset($openaiStatus['error']))
                                <div class="alert alert-danger">
                                    <strong><i class="glyphicon glyphicon-exclamation-sign"></i> Ошибка OpenAI:</strong>
                                    {{ $openaiStatus['error'] }}
                                </div>
                            @endif
                        @endif

                        <div class="alert alert-info">
                            <strong>Используется для:</strong>
                            <ul class="margin-bottom-0">
                                <li><strong>Whisper API</strong> - расшифровка аудио в текст</li>
                                <li><strong>GPT-4</strong> - нормализация текста (извлечение адреса и проблемы)</li>
                            </ul>
                            <hr style="margin: 10px 0;">
                            <small><i class="glyphicon glyphicon-info-sign"></i> Баланс OpenAI недоступен через API. Проверить можно на <a href="https://platform.openai.com/usage" target="_blank">platform.openai.com/usage</a></small>
                        </div>

                        <div class="form-group">
                            <label for="voicemail.openai_api_key" class="col-sm-3 control-label">API Key</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="voicemail.openai_api_key"
                                       name="settings[voicemail.openai_api_key]"
                                       value="{{ old('settings.voicemail.openai_api_key', $settings['voicemail.openai_api_key'] ?? '') }}"
                                       placeholder="sk-proj-...">
                                <p class="help-block">Получить ключ: <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a> <strong>Настроен в константах.</strong></p>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- AmoCRM --}}
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="glyphicon glyphicon-briefcase"></i> AmoCRM (для заявок на подключение)</h3>
                    </div>
                    <div class="panel-body">

                        <div class="alert alert-info">
                            <strong><i class="glyphicon glyphicon-info-sign"></i> Логика создания лидов:</strong><br>
                            При классификации обращения как "подключение" модуль автоматически создаёт контакт и лид в AmoCRM.
                            <br><br>
                            <strong>UTM-метки:</strong>
                            <ul style="margin-bottom: 0; margin-top: 5px;">
                                <li><code>utm_source=call</code> — всегда</li>
                                <li><code>utm_medium</code> — номер телефона, на который звонили (55-35-59 / 70000000002 / 00-00-01 / 00-00-02)</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning">
                            <strong><i class="glyphicon glyphicon-random"></i> Выбор воронки:</strong>
                            <table class="table table-bordered" style="margin-top: 10px; margin-bottom: 0; background: white;">
                                <thead>
                                    <tr>
                                        <th>Телефонная линия</th>
                                        <th>Воронка</th>
                                        <th>Pipeline ID</th>
                                        <th>Stage ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>55-35-59</code>, <code>70000000002</code></td>
                                        <td><strong>B2C</strong> (Физ. лица)</td>
                                        <td><code>0</code></td>
                                        <td><code>0</code> (Первичный контакт)</td>
                                    </tr>
                                    <tr>
                                        <td><code>00-00-01</code></td>
                                        <td><strong>B2B/Корп</strong> (Юр. лица)</td>
                                        <td><code>0</code></td>
                                        <td><code>0</code> (Первичный контакт)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="form-group">
                            <label for="voicemail.amocrm_domain" class="col-sm-3 control-label">Домен AmoCRM</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control" id="voicemail.amocrm_domain"
                                       name="settings[voicemail.amocrm_domain]"
                                       value="{{ old('settings.voicemail.amocrm_domain', $settings['voicemail.amocrm_domain'] ?? 'your-account.amocrm.ru') }}"
                                       placeholder="your-account.amocrm.ru">
                            </div>
                            <div class="col-sm-3">
                                <p class="help-block"><strong>Текущее: your-account.amocrm.ru</strong></p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="voicemail.amocrm_token" class="col-sm-3 control-label">Access Token</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="voicemail.amocrm_token" rows="3"
                                          name="settings[voicemail.amocrm_token]"
                                          placeholder="eyJ0eXAiOiJKV1QiLCJh...">{{ old('settings.voicemail.amocrm_token', $settings['voicemail.amocrm_token'] ?? '') }}</textarea>
                                <p class="help-block">Настройки → Интеграции → Собственная интеграция → Ключи авторизации. <strong>Токен настроен в константах.</strong></p>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- SMSAero --}}
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="glyphicon glyphicon-phone"></i> SMS уведомления (SMSAero)</h3>
                    </div>
                    <div class="panel-body">

                        {{-- Баланс SMSAero --}}
                        @if(isset($smsBalance))
                            @if(isset($smsBalance['success']) && $smsBalance['success'])
                                <div class="alert alert-success">
                                    <strong><i class="glyphicon glyphicon-rub"></i> Баланс SMSAero:</strong>
                                    <span style="font-size: 18px; font-weight: bold;">{{ number_format($smsBalance['balance'], 2, ',', ' ') }} руб.</span>
                                </div>
                            @elseif(isset($smsBalance['error']))
                                <div class="alert alert-warning">
                                    <strong><i class="glyphicon glyphicon-warning-sign"></i> Ошибка получения баланса:</strong>
                                    {{ $smsBalance['error'] }}
                                </div>
                            @endif
                        @endif

                        <div class="alert alert-info">
                            <strong>Функциональность:</strong> При создании тикета клиенту отправляется SMS с номером обращения.
                            <br><strong>Защита от дублей:</strong> SMS кешируется на 30 минут для предотвращения повторной отправки.
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">SMS включены</label>
                            <div class="col-sm-9">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="settings[voicemail.sms_enabled]"
                                               @if (old('settings[voicemail.sms_enabled]', $settings['voicemail.sms_enabled'] ?? 'on') == 'on') checked @endif>
                                        Отправлять SMS уведомления клиентам
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="voicemail.smsaero_email" class="col-sm-3 control-label">Email (логин)</label>
                            <div class="col-sm-6">
                                <input type="email" class="form-control" id="voicemail.smsaero_email"
                                       name="settings[voicemail.smsaero_email]"
                                       value="{{ old('settings.voicemail.smsaero_email', $settings['voicemail.smsaero_email'] ?? '') }}"
                                       placeholder="admin@example.com">
                            </div>
                            <div class="col-sm-3">
                                <p class="help-block"><strong>Текущее: admin@example.com</strong></p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="voicemail.smsaero_api_key" class="col-sm-3 control-label">API Key</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="voicemail.smsaero_api_key"
                                       name="settings[voicemail.smsaero_api_key]"
                                       value="{{ old('settings.voicemail.smsaero_api_key', $settings['voicemail.smsaero_api_key'] ?? '') }}"
                                       placeholder="your_api_key">
                                <p class="help-block">Получить ключ: <a href="https://smsaero.ru/cabinet/settings/apikey/" target="_blank">smsaero.ru/cabinet/settings/apikey/</a> <strong>Настроен в .env</strong></p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Шаблоны SMS</label>
                            <div class="col-sm-9">
                                <div class="well well-sm">
                                    <strong>Подключение:</strong><br>
                                    <code>СМИТ. Ваша заявка на подключение №{ticketNumber} принята. Ожидайте звонка.</code>
                                    <hr style="margin: 10px 0;">
                                    <strong>Техподдержка:</strong><br>
                                    <code>СМИТ. Ваше обращение №{ticketNumber} принято. Ожидайте ответ специалиста.</code>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Биллинг --}}
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="glyphicon glyphicon-ruble"></i> Биллинг (поиск клиента)</h3>
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label for="voicemail.billing_api_url" class="col-sm-3 control-label">API URL</label>
                            <div class="col-sm-9">
                                <input type="url" class="form-control" id="voicemail.billing_api_url"
                                       name="settings[voicemail.billing_api_url]"
                                       value="{{ old('settings.voicemail.billing_api_url', $settings['voicemail.billing_api_url'] ?? 'http://legacy-billing.example.com/static/cassa_pay/phone.php') }}">
                                <p class="help-block">URL для поиска клиента по номеру телефона в биллинге</p>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- НОВЫЙ БЛОК: Ключевые слова анализа --}}
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="glyphicon glyphicon-search"></i> Ключевые слова анализа сообщений</h3>
                    </div>
                    <div class="panel-body">

                        <div class="alert alert-info">
                            <strong><i class="glyphicon glyphicon-info-sign"></i> Алгоритм классификации:</strong>
                            <ol style="margin-bottom: 0;">
                                <li>Сначала проверяется на <strong>мусорное сообщение</strong> (garbage)</li>
                                <li>Затем подсчитываются баллы для <strong>поддержки</strong> и <strong>подключения</strong></li>
                                <li>Если есть слова поддержки — это <span class="label label-primary">support</span></li>
                                <li>Если только адрес без слов поддержки — это <span class="label label-warning">connection</span></li>
                            </ol>
                        </div>

                        {{-- Мусорные сообщения --}}
                        <div class="panel panel-danger">
                            <div class="panel-heading">
                                <h4 class="panel-title"><i class="glyphicon glyphicon-trash"></i> Мусорные сообщения (GARBAGE) — игнорируются</h4>
                            </div>
                            <div class="panel-body">
                                <p><strong>Сообщение классифицируется как мусор если:</strong></p>
                                <ul>
                                    <li>Длина менее 5 символов</li>
                                    <li>Менее 3 значимых слов (длиной > 2 символов)</li>
                                    <li>Начинается с мусорной фразы</li>
                                </ul>

                                <p><strong>Список мусорных фраз:</strong></p>
                                <div class="well well-sm" style="background: #f2dede;">
                                    <code>да, нет, а, ага, угу, ну, что, алло, але</code><br>
                                    <code>спасибо, спаси, пока, до свидания, всего хорошего</code><br>
                                    <code>ок, окей, хорошо, ладно, понял, понятно</code><br>
                                    <code>ну да, да так, так да, ну конечно, конечно</code><br>
                                    <code>повторюсь, пока а а, а а а</code><br>
                                    <code>абонент не отвечает, временно недоступ</code><br>
                                    <code>не помню, ничего не, нигде не, не это</code><br>
                                    <code></code>
                                </div>

                                <p><strong>НО! Сообщение НЕ мусор, если содержит полезные слова:</strong></p>
                                <div class="well well-sm" style="background: #dff0d8;">
                                    <code>интернет, интернета, роутер, wifi, вайфай, вай-фай</code><br>
                                    <code>не работает, отключился, пропал, нет связи, нету</code><br>
                                    <code>подключ, тариф, баланс, долг, оплат</code><br>
                                    <code>проблема, помогите, помощь, настройк, дозвониться, дозвонить</code><br>
                                    <code>улица, ул, дом, квартира, город, район</code><br>
                                    <code>волгоград, саратов, краснодар</code>
                                </div>
                            </div>
                        </div>

                        {{-- Поддержка --}}
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h4 class="panel-title"><i class="glyphicon glyphicon-wrench"></i> Техподдержка (SUPPORT) — тикет без лида</h4>
                            </div>
                            <div class="panel-body">
                                <p><strong>Ключевые слова для классификации как "поддержка":</strong></p>

                                <div class="row">
                                    <div class="col-md-4">
                                        <p><em>Проблемы с интернетом:</em></p>
                                        <div class="well well-sm">
                                            <code>проблема, не работает, пропал</code><br>
                                            <code>нет интернета, нету интернета</code><br>
                                            <code>отключился, перестал, сломал</code><br>
                                            <code>ошибка, медленн, тормоз</code><br>
                                            <code>глюч, барахл, лаг</code>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <p><em>Оборудование:</em></p>
                                        <div class="well well-sm">
                                            <code>роутер, маршрутизатор, модем</code><br>
                                            <code>приставк, wi-fi, wifi</code><br>
                                            <code>вай-фай, вайфай, вай фай</code><br>
                                            <code>кабель, провод, разъём, порт</code>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <p><em>Настройки:</em></p>
                                        <div class="well well-sm">
                                            <code>настройк, настроить</code><br>
                                            <code>логин, пароль</code><br>
                                            <code>мак адрес, mac, ip адрес</code>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <p><em>Связь/дозвон:</em></p>
                                        <div class="well well-sm">
                                            <code>не могу дозвониться</code><br>
                                            <code>не дозвонить, не отвечает</code><br>
                                            <code>недоступ, занят</code>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <p><em>Финансы:</em></p>
                                        <div class="well well-sm">
                                            <code>долг, задолженность, баланс</code><br>
                                            <code>оплат, платёж, минус</code><br>
                                            <code>заблокир, блокировк</code>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <p><em>Жалобы:</em></p>
                                        <div class="well well-sm">
                                            <code>жалоба, претензи</code><br>
                                            <code>перерасчёт, перерасчет</code><br>
                                            <code>возврат, компенсац</code>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <p><em>Отключение:</em></p>
                                        <div class="well well-sm">
                                            <code>отключиться, хочу отключ</code><br>
                                            <code>расторг, расторжен</code><br>
                                            <code>прекратить, закрыть договор</code>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <p><em>Технические проблемы:</em></p>
                                        <div class="well well-sm">
                                            <code>перезагруж, перезапуст</code><br>
                                            <code>мигает, горит</code><br>
                                            <code>помогите, помощь, срочно</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Подключение --}}
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <h4 class="panel-title"><i class="glyphicon glyphicon-home"></i> Подключение (CONNECTION) — создаётся лид в AmoCRM</h4>
                            </div>
                            <div class="panel-body">
                                <p><strong>Ключевые слова для классификации как "подключение":</strong></p>

                                <div class="well well-sm" style="background: #fcf8e3;">
                                    <code>подключ, хочу интернет, провести интернет</code><br>
                                    <code>установить интернет, провайдер</code><br>
                                    <code>новый абонент, стать клиент</code><br>
                                    <code>узнать о тариф, какие тариф, сколько стоит подключ</code>
                                </div>

                                <div class="alert alert-warning" style="margin-top: 15px;">
                                    <strong><i class="glyphicon glyphicon-exclamation-sign"></i> Бонус за адрес (+3 балла):</strong><br>
                                    Если в сообщении есть <strong>адрес</strong> (улица/дом) и <strong>НЕТ</strong> слов поддержки — это заявка на подключение.
                                    <br><br>
                                    <strong>Признаки адреса:</strong>
                                    <code>улица, ул, ул., проспект, переулок, дом, д, д.</code>
                                </div>

                                <div class="alert alert-danger">
                                    <strong><i class="glyphicon glyphicon-ban-circle"></i> Валидация адреса перед созданием лида:</strong><br>
                                    Лид в AmoCRM создаётся только если адрес валидный (содержит ключевые слова: улица, дом, город, район и т.д.)
                                    <br>Мусорные адреса типа "да так да" или "алло" НЕ создадут лид.
                                </div>
                            </div>
                        </div>

                        {{-- Примеры классификации --}}
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title"><i class="glyphicon glyphicon-list-alt"></i> Примеры классификации</h4>
                            </div>
                            <div class="panel-body">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Текст сообщения</th>
                                            <th width="120">Результат</th>
                                            <th>Пояснение</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="danger">
                                            <td><code>да</code></td>
                                            <td><span class="label label-danger">garbage</span></td>
                                            <td>Односложный ответ — мусор</td>
                                        </tr>
                                        <tr class="danger">
                                            <td><code>алло</code></td>
                                            <td><span class="label label-danger">garbage</span></td>
                                            <td>Мусорная фраза</td>
                                        </tr>
                                        <tr class="danger">
                                            <td><code>абонент не отвечает или временно недоступен</code></td>
                                            <td><span class="label label-danger">garbage</span></td>
                                            <td>Системное сообщение — мусор</td>
                                        </tr>
                                        <tr class="info">
                                            <td><code>нет интернета</code></td>
                                            <td><span class="label label-primary">support</span></td>
                                            <td>Содержит "интернета" — полезное слово</td>
                                        </tr>
                                        <tr class="info">
                                            <td><code>роутер не работает</code></td>
                                            <td><span class="label label-primary">support</span></td>
                                            <td>Ключевые слова: роутер, не работает</td>
                                        </tr>
                                        <tr class="info">
                                            <td><code>хочу отключиться от интернета</code></td>
                                            <td><span class="label label-primary">support</span></td>
                                            <td>Отключение — это поддержка, не подключение!</td>
                                        </tr>
                                        <tr class="info">
                                            <td><code>у меня долг на балансе</code></td>
                                            <td><span class="label label-primary">support</span></td>
                                            <td>Финансовый вопрос — поддержка</td>
                                        </tr>
                                        <tr class="warning">
                                            <td><code>город улица пример дом 1</code></td>
                                            <td><span class="label label-warning">connection</span></td>
                                            <td>Только адрес, нет слов поддержки → бонус за адрес</td>
                                        </tr>
                                        <tr class="warning">
                                            <td><code>хочу подключить интернет улица ленина 15</code></td>
                                            <td><span class="label label-warning">connection</span></td>
                                            <td>Слово "подключ" + адрес</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Логика классификации --}}
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="glyphicon glyphicon-random"></i> Схема обработки звонков</h3>
                    </div>
                    <div class="panel-body">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="panel panel-danger">
                                    <div class="panel-heading">
                                        <h4 class="panel-title"><i class="glyphicon glyphicon-trash"></i> GARBAGE</h4>
                                    </div>
                                    <div class="panel-body">
                                        <p><strong>Мусорное сообщение</strong></p>
                                        <ul>
                                            <li>Тикет закрывается</li>
                                            <li>Лид НЕ создаётся</li>
                                            <li>SMS НЕ отправляется</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">
                                        <h4 class="panel-title"><i class="glyphicon glyphicon-wrench"></i> SUPPORT</h4>
                                    </div>
                                    <div class="panel-body">
                                        <p><strong>Техподдержка</strong></p>
                                        <ul>
                                            <li>Тикет в Mailbox #1</li>
                                            <li>Лид НЕ создаётся</li>
                                            <li>SMS: "Обращение принято"</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="panel panel-warning">
                                    <div class="panel-heading">
                                        <h4 class="panel-title"><i class="glyphicon glyphicon-home"></i> CONNECTION</h4>
                                    </div>
                                    <div class="panel-body">
                                        <p><strong>Заявка на подключение</strong></p>
                                        <ul>
                                            <li>Тикет в Mailbox #5</li>
                                            <li>Лид в AmoCRM (если адрес валидный)</li>
                                            <li>SMS: "Заявка принята"</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Кнопка сохранения --}}
                <div class="form-group">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="glyphicon glyphicon-floppy-disk"></i> Сохранить настройки
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection
