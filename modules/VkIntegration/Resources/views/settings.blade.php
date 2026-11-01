@extends('layouts.app')

@section('title_full', 'VK Integration - Настройки')

@section('content')
<div class="container">
    <div class="section-heading">
        <h1><i class="fab fa-vk" style="color: #4680C2;"></i> VK Integration v2.0</h1>
    </div>

    @include('partials/flash_messages')

    <div class="row">
        <div class="col-md-8">
            <form method="POST" action="{{ route('vkintegration.settings') }}" class="form-horizontal">
                {{ csrf_field() }}

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Основные настройки</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Включить интеграцию</label>
                            <div class="col-sm-9">
                                <input type="checkbox" name="vk_enabled" value="1" {{ $settings['vk_enabled'] ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">URL Bridge</label>
                            <div class="col-sm-9">
                                <input type="text" name="vk_bridge_url" class="form-control" value="{{ $settings['vk_bridge_url'] }}" placeholder="http://localhost:8901">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Mailbox по умолчанию</label>
                            <div class="col-sm-9">
                                <select name="vk_default_mailbox" class="form-control">
                                    @foreach($mailboxes as $mailbox)
                                        <option value="{{ $mailbox->id }}" {{ $settings['vk_default_mailbox'] == $mailbox->id ? 'selected' : '' }}>{{ $mailbox->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">VK API</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Access Token</label>
                            <div class="col-sm-9">
                                <input type="password" name="vk_access_token" class="form-control" value="{{ $settings['vk_access_token'] }}" placeholder="vk1.a...">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Group ID</label>
                            <div class="col-sm-9">
                                <input type="text" name="vk_group_id" class="form-control" value="{{ $settings['vk_group_id'] }}" placeholder="123456789">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Secret Key</label>
                            <div class="col-sm-9">
                                <input type="text" name="vk_secret_key" class="form-control" value="{{ $settings['vk_secret_key'] }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">Confirmation Code</label>
                            <div class="col-sm-9">
                                <input type="text" name="vk_confirmation_code" class="form-control" value="{{ $settings['vk_confirmation_code'] }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">FreeScout API Key</label>
                            <div class="col-sm-9">
                                <input type="text" name="freescout_api_key" class="form-control" value="{{ $settings['freescout_api_key'] }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-9">
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </div>
            </form>

            {{-- Сценарии диалога --}}
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="glyphicon glyphicon-random"></i> Сценарии диалога</h3>
                </div>
                <div class="panel-body">

                    <div class="alert alert-info">
                        <strong><i class="fab fa-vk"></i> Интеграция с биллингом:</strong><br>
                        При первом сообщении клиента бот запрашивает номер телефона и проверяет его в биллинге
                        <code>legacy-billing.example.com</code> для идентификации абонента.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="panel panel-warning">
                                <div class="panel-heading">
                                    <h4 class="panel-title"><i class="glyphicon glyphicon-user"></i> Сценарий 1: Новый клиент</h4>
                                </div>
                                <div class="panel-body">
<pre style="background: #fcf8e3; padding: 10px; font-size: 11px; white-space: pre-wrap;">
<strong>1. Клиент пишет в VK:</strong>
   "Привет"

<strong>2. Бот проверяет профиль VK:</strong>
   - Ищет телефон в профиле VK
   - Если найден - проверяет в биллинге
   - Если нет - запрашивает телефон

<strong>3. Бот отвечает:</strong>
   "Здравствуйте, Иван! Для начала
   подскажите, пожалуйста, ваш номер
   телефона, чтобы я мог проверить
   информацию по вашему договору"

<strong>4. Клиент пишет телефон:</strong>
   "89271234567"

<strong>5. Бот проверяет в биллинге:</strong>
   URL: legacy-billing.example.com/phone.php
</pre>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="panel panel-success">
                                <div class="panel-heading">
                                    <h4 class="panel-title"><i class="glyphicon glyphicon-ok"></i> Сценарий 2: Клиент найден</h4>
                                </div>
                                <div class="panel-body">
<pre style="background: #dff0d8; padding: 10px; font-size: 11px; white-space: pre-wrap;">
<strong>Биллинг вернул данные:</strong>
{
  "fullname": "Иванов Иван Иванович",
  "contract_number": "12345",
  "ballance": "150.00",
  "tariff": "Оптимальный 100",
  "address": "ул. Ленина 10 кв.5"
}

<strong>Бот отвечает:</strong>
"Спасибо! Информация найдена:
 Имя: Иван
 Договор: 12345
 Баланс: 150.00 руб.
 Тариф: Оптимальный 100

Чем могу помочь?"

<strong>Далее:</strong>
- Создаётся клиент в FreeScout
- Телефон добавляется в профиль
- Клиент описывает проблему
- Создаётся тикет
</pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    <h4 class="panel-title"><i class="glyphicon glyphicon-remove"></i> Сценарий 3: Клиент не найден</h4>
                                </div>
                                <div class="panel-body">
<pre style="background: #f2dede; padding: 10px; font-size: 11px; white-space: pre-wrap;">
<strong>Биллинг вернул ошибку</strong>

<strong>Бот отвечает:</strong>
"К сожалению, клиент с номером
+79271234567 не найден в нашей базе.

Если вы новый клиент, напишите
ваш вопрос, и я создам обращение
для вас."

<strong>Далее:</strong>
- Состояние сбрасывается
- Клиент может написать вопрос
- Тикет создаётся без биллинга
- В теме: "VK: {имя из VK}"
</pre>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    <h4 class="panel-title"><i class="glyphicon glyphicon-phone"></i> Сценарий 4: Телефон в VK</h4>
                                </div>
                                <div class="panel-body">
<pre style="background: #d9edf7; padding: 10px; font-size: 11px; white-space: pre-wrap;">
<strong>Если в профиле VK указан телефон:</strong>
- Бот автоматически проверяет его
- Не запрашивает телефон у клиента

<strong>Бот сразу отвечает:</strong>
"Здравствуйте, Иван! Я помогу вам
разобраться с вашим вопросом.
Чем могу помочь?"

<strong>При создании тикета:</strong>
- Данные из биллинга автоматически
- В примечании: ФИО, договор,
  баланс, тариф
- Телефон в профиле клиента
</pre>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Пример полного диалога --}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="glyphicon glyphicon-list-alt"></i> Пример полного диалога</h3>
                </div>
                <div class="panel-body">
<pre style="background: #f5f5f5; padding: 15px; font-size: 12px;">
<span style="color: #4680C2;"><strong>Клиент:</strong></span> Привет

<span style="color: #28a745;"><strong>Бот:</strong></span> Здравствуйте, Иван! Я помогу вам разобраться с вашим вопросом.
     Для начала подскажите, пожалуйста, ваш номер телефона,
     чтобы я мог проверить информацию по вашему договору

<span style="color: #4680C2;"><strong>Клиент:</strong></span> 89271234567

<span style="color: gray;">[Бот проверяет телефон в биллинге...]</span>

<span style="color: #28a745;"><strong>Бот:</strong></span> Спасибо! Информация найдена:
     Имя: Иван
     Договор: 12345
     Баланс: 150.00 руб.
     Тариф: Оптимальный 100

     Чем могу помочь?

<span style="color: #4680C2;"><strong>Клиент:</strong></span> У меня не работает интернет уже 2 часа

<span style="color: gray;">[Создаётся тикет в FreeScout...]</span>

<span style="color: #28a745;"><strong>Бот:</strong></span> Ваше обращение создано и передано специалисту.
     Номер обращения: #5678
     Мы свяжемся с вами в ближайшее время.

<span style="color: gray;"><strong>В FreeScout создан тикет:</strong></span>
   - Тема: "Иванов И.И. (#12345)"
   - Клиент: Иван VK (vk123456789@vk.com)
   - Телефон: +79271234567
   - Примечание: Договор 12345, баланс 150.00, тариф Оптимальный 100
</pre>
                </div>
            </div>

            {{-- Биллинг API --}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="glyphicon glyphicon-cloud"></i> Биллинг API</h3>
                </div>
                <div class="panel-body">
                    <p><strong>URL:</strong> <code>http://legacy-billing.example.com/static/cassa_pay/phone.php</code></p>
                    <p><strong>Метод:</strong> GET</p>
                    <p><strong>Параметры:</strong> <code>phone=+79XXXXXXXXX</code></p>

                    <div class="alert alert-success">
                        <strong>Пример успешного ответа:</strong>
<pre style="margin-top: 10px;">
{
  "client": {
    "fullname": "Иванов Иван Иванович",
    "contract_number": "12345",
    "ballance": "150.00",
    "tariff": "Оптимальный 100",
    "address": "ул. Ленина 10 кв.5"
  }
}
</pre>
                    </div>

                    <div class="alert alert-danger">
                        <strong>Клиент не найден:</strong> пустой ответ, отсутствует поле <code>client</code> или есть <code>error</code>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Статус Bridge</h3>
                </div>
                <div class="panel-body">
                    @if($bridgeStatus['online'])
                        <p><span class="label label-success">Online</span></p>
                        <p>Статус: {{ $bridgeStatus['status'] ?? 'N/A' }}</p>
                    @else
                        <p><span class="label label-danger">Offline</span></p>
                        <p class="text-muted">{{ $bridgeStatus['error'] ?? 'Bridge не отвечает' }}</p>
                    @endif
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Статистика</h3>
                </div>
                <div class="panel-body">
                    <p>Тикетов всего: <strong>{{ $stats['total_conversations'] }}</strong></p>
                    <p>Тикетов сегодня: <strong>{{ $stats['today_conversations'] }}</strong></p>
                    <p>VK клиентов: <strong>{{ $stats['total_customers'] }}</strong></p>
                </div>
            </div>

            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fab fa-vk"></i> Поддержка</h3>
                </div>
                <div class="panel-body">
                    <p>Вопросы по модулю — в Issues репозитория на GitHub.</p>
                </div>
            </div>

            {{-- Состояния диалога --}}
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Состояния диалога</h3>
                </div>
                <div class="panel-body">
                    <ul style="padding-left: 20px; margin-bottom: 0;">
                        <li><code>NEW</code> - новый пользователь</li>
                        <li><code>WAITING_PHONE</code> - ожидание телефона</li>
                        <li><code>WAITING_FIRST_MESSAGE</code> - ожидание вопроса</li>
                        <li><code>IN_CONVERSATION</code> - диалог активен</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
