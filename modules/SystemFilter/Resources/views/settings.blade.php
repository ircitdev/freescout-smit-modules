@extends('layouts.app')

@section('title_full', 'SystemFilter - Автоматическая фильтрация системных писем')

@section('body_attrs')@parent data-systemfilter_settings_page="true"@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-xs-12">
            <h1>SystemFilter - Автоматическая фильтрация системных писем</h1>

            <div class="panel-group" id="systemfilter-accordion">

                <!-- Обзор модуля -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a data-toggle="collapse" data-parent="#systemfilter-accordion" href="#collapse-overview">
                                <i class="glyphicon glyphicon-filter"></i> Обзор модуля
                            </a>
                        </h4>
                    </div>
                    <div id="collapse-overview" class="panel-collapse collapse in">
                        <div class="panel-body">
                            <h3>Что делает этот модуль?</h3>
                            <p>
                                <strong>SystemFilter</strong> автоматически отслеживает все входящие письма и удаляет системные сообщения
                                (bounce emails, уведомления о недоставке, автоответы почтовых серверов) <strong>ДО того</strong>,
                                как из них будут созданы тикеты в FreeScout.
                            </p>

                            <h4>Зачем это нужно?</h4>
                            <ul>
                                <li><strong>Чистота базы тикетов</strong> - системные письма не засоряют список обращений</li>
                                <li><strong>Экономия времени</strong> - сотрудники не тратят время на просмотр технических сообщений</li>
                                <li><strong>Улучшение статистики</strong> - только реальные обращения клиентов учитываются в отчётах</li>
                                <li><strong>Автоматизация</strong> - работает полностью автоматически, без ручного вмешательства</li>
                            </ul>

                            <h4>Как это работает?</h4>
                            <ol>
                                <li>Клиент или система отправляет письмо на почту FreeScout</li>
                                <li>FreeScout получает письмо и готовится создать тикет</li>
                                <li><strong>SystemFilter перехватывает событие</strong> создания тикета с приоритетом 1 (самый первый)</li>
                                <li>Модуль проверяет тему письма на наличие ключевых слов bounce-сообщений</li>
                                <li>Если найдено совпадение - тикет немедленно удаляется, запись появляется в логах</li>
                                <li>Если совпадений нет - тикет создаётся как обычно</li>
                            </ol>

                            <div class="alert alert-info">
                                <i class="glyphicon glyphicon-info-sign"></i>
                                <strong>Важно:</strong> Модуль работает для <strong>ВСЕХ mailbox</strong> в системе.
                                Это глобальный фильтр, который защищает все почтовые ящики от системных писем.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ключевые слова фильтрации -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a data-toggle="collapse" data-parent="#systemfilter-accordion" href="#collapse-keywords">
                                <i class="glyphicon glyphicon-list"></i> Ключевые слова фильтрации
                            </a>
                        </h4>
                    </div>
                    <div id="collapse-keywords" class="panel-collapse collapse">
                        <div class="panel-body">
                            <h3>Список отслеживаемых ключевых слов</h3>
                            <p>Модуль проверяет тему письма (Subject) на наличие следующих слов (регистр не важен):</p>

                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Ключевое слово</th>
                                        <th>Описание</th>
                                        <th>Пример темы письма</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>undeliverable</code></td>
                                        <td>Недоставленное письмо</td>
                                        <td>"Undeliverable: Your message to user@example.com"</td>
                                    </tr>
                                    <tr>
                                        <td><code>report</code></td>
                                        <td>Отчёт о доставке</td>
                                        <td>"Delivery Status Notification Report"</td>
                                    </tr>
                                    <tr>
                                        <td><code>failed</code></td>
                                        <td>Ошибка отправки</td>
                                        <td>"Mail delivery failed: returning message to sender"</td>
                                    </tr>
                                    <tr>
                                        <td><code>delivery</code></td>
                                        <td>Уведомление о доставке</td>
                                        <td>"Delivery Status Notification (Failure)"</td>
                                    </tr>
                                    <tr>
                                        <td><code>bounce</code></td>
                                        <td>Возврат письма</td>
                                        <td>"Bounced email notification"</td>
                                    </tr>
                                    <tr>
                                        <td><code>mailer-daemon</code></td>
                                        <td>Системный отправитель</td>
                                        <td>"Mail delivery failed from MAILER-DAEMON"</td>
                                    </tr>
                                    <tr>
                                        <td><code>delivery status notification</code></td>
                                        <td>Полная фраза DSN</td>
                                        <td>"Delivery Status Notification (DSN)"</td>
                                    </tr>
                                    <tr>
                                        <td><code>failure notice</code></td>
                                        <td>Уведомление об ошибке</td>
                                        <td>"Failure Notice: Message not delivered"</td>
                                    </tr>
                                    <tr>
                                        <td><code>returned mail</code></td>
                                        <td>Возвращённое письмо</td>
                                        <td>"Returned mail: see transcript for details"</td>
                                    </tr>
                                    <tr>
                                        <td><code>undelivered</code></td>
                                        <td>Не доставлено</td>
                                        <td>"Undelivered Mail Returned to Sender"</td>
                                    </tr>
                                    <tr>
                                        <td><code>mail delivery failed</code></td>
                                        <td>Полная фраза ошибки</td>
                                        <td>"Mail Delivery Failed - User unknown"</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="alert alert-warning">
                                <i class="glyphicon glyphicon-warning-sign"></i>
                                <strong>Примечание:</strong> Поиск выполняется без учёта регистра. Слово "UNDELIVERABLE",
                                "Undeliverable" или "undeliverable" будут найдены одинаково.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Примеры работы -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a data-toggle="collapse" data-parent="#systemfilter-accordion" href="#collapse-examples">
                                <i class="glyphicon glyphicon-screenshot"></i> Примеры работы модуля
                            </a>
                        </h4>
                    </div>
                    <div id="collapse-examples" class="panel-collapse collapse">
                        <div class="panel-body">
                            <h3>Примеры фильтрации</h3>

                            <h4>Пример 1: Недоставленное письмо (будет отфильтровано)</h4>
                            <pre>From: MAILER-DAEMON@mail.example.com
To: support@example.com
Subject: Undeliverable: Your message to client@domain.com
Date: 2025-11-24 10:30:00

This is an automatically generated Delivery Status Notification.
Your message could not be delivered to the following recipients:

client@domain.com
    Reason: User unknown</pre>
                            <p><strong>Результат:</strong> <span class="label label-danger">Тикет НЕ создан</span> - письмо удалено автоматически</p>

                            <hr>

                            <h4>Пример 2: Обычное клиентское письмо (НЕ будет отфильтровано)</h4>
                            <pre>From: client@example.com
To: support@example.com
Subject: Вопрос по подключению интернета
Date: 2025-11-24 10:35:00

Добрый день! Хочу подключить интернет по адресу ул. Ленина, 15.</pre>
                            <p><strong>Результат:</strong> <span class="label label-success">Тикет создан</span> - обычное обращение клиента</p>
                        </div>
                    </div>
                </div>

                <!-- Настройка и установка -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a data-toggle="collapse" data-parent="#systemfilter-accordion" href="#collapse-setup">
                                <i class="glyphicon glyphicon-cog"></i> Настройка и установка
                            </a>
                        </h4>
                    </div>
                    <div id="collapse-setup" class="panel-collapse collapse">
                        <div class="panel-body">
                            <h3>Установка модуля</h3>
                            <p>Модуль уже установлен и активирован. Никаких дополнительных настроек не требуется!</p>

                            <h4>Структура файлов модуля:</h4>
                            <pre>/var/www/freescout/Modules/SystemFilter/
├── module.json
├── Config/config.php
├── Providers/SystemFilterServiceProvider.php
├── Listeners/BounceEmailFilter.php
├── Http/routes.php
├── Http/Controllers/SystemFilterController.php
└── Resources/views/settings.blade.php</pre>

                            <div class="alert alert-success">
                                <i class="glyphicon glyphicon-ok-sign"></i>
                                <strong>Готово!</strong> Модуль работает полностью автоматически.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Логирование и отладка -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a data-toggle="collapse" data-parent="#systemfilter-accordion" href="#collapse-logging">
                                <i class="glyphicon glyphicon-file"></i> Логирование и отладка
                            </a>
                        </h4>
                    </div>
                    <div id="collapse-logging" class="panel-collapse collapse">
                        <div class="panel-body">
                            <h3>Где искать логи?</h3>
                            <p>Все действия модуля записываются в стандартный лог Laravel:</p>
                            <pre><code>/var/www/freescout/storage/logs/laravel.log</code></pre>

                            <h4>Команды для просмотра логов:</h4>
                            <pre># Последние 50 строк лога
tail -50 /var/www/freescout/storage/logs/laravel.log

# Поиск только записей SystemFilter
grep "SystemFilter" /var/www/freescout/storage/logs/laravel.log

# Живой просмотр логов
tail -f /var/www/freescout/storage/logs/laravel.log | grep "SystemFilter"</pre>
                        </div>
                    </div>
                </div>

            </div>

            <div class="margin-top margin-bottom">
                <a href="{{ route('modules') }}" class="btn btn-default">
                    <i class="glyphicon glyphicon-arrow-left"></i> Вернуться к списку модулей
                </a>
            </div>

        </div>
    </div>
</div>
@endsection

@section('javascript')
    @parent
    $(document).ready(function() {
        $('#collapse-overview').collapse('show');
    });
@endsection
