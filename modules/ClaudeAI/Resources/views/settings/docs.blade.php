@extends('layouts.app')

@section('title', 'Claude AI — Документация')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title" style="display:flex;align-items:center;gap:8px;justify-content:space-between">
                        <span><span style="font-size:20px">📖</span> Claude AI — Документация</span>
                        <a href="{{ route('claudeai.settings') }}" class="btn btn-default btn-sm">⬅ Настройки</a>
                    </h3>
                </div>
                <div class="panel-body" style="line-height:1.7">

                    <style>
                        .docs-section { margin-bottom: 30px; }
                        .docs-section h3 { color: #1f2937; border-bottom: 2px solid #10b981; padding-bottom: 6px; margin-top: 24px; }
                        .docs-section h4 { color: #374151; margin-top: 16px; }
                        .docs-section code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
                        .docs-section pre { background: #1f2937; color: #e5e7eb; padding: 14px; border-radius: 8px; overflow-x: auto; font-size: 13px; }
                        .docs-section table { width: 100%; border-collapse: collapse; margin: 12px 0; }
                        .docs-section table th { background: #f9fafb; text-align: left; padding: 8px 12px; border: 1px solid #e5e7eb; font-weight: 600; }
                        .docs-section table td { padding: 8px 12px; border: 1px solid #e5e7eb; }
                        .docs-section .badge-feature { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
                        .docs-section .badge-ready { background: #d1fae5; color: #065f46; }
                        .docs-section .badge-planned { background: #fef3c7; color: #92400e; }
                        .docs-toc { background: #f9fafb; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
                        .docs-toc a { text-decoration: none; color: #059669; }
                        .docs-toc a:hover { text-decoration: underline; }
                    </style>

                    {{-- Table of Contents --}}
                    <div class="docs-toc">
                        <strong>📑 Содержание:</strong>
                        <ol style="margin:8px 0 0;padding-left:20px">
                            <li><a href="#overview">Обзор модуля</a></li>
                            <li><a href="#install">Установка</a></li>
                            <li><a href="#settings">Настройки</a></li>
                            <li><a href="#features">Функционал</a></li>
                            <li><a href="#operator">Подсказки оператору</a></li>
                            <li><a href="#drafts">Черновики ответов</a></li>
                            <li><a href="#billing">Интеграция с биллингом</a></li>
                            <li><a href="#kb">База знаний</a></li>
                            <li><a href="#prompts">System prompts</a></li>
                            <li><a href="#tools">Tool Use (функции)</a></li>
                            <li><a href="#scenarios">Сценарии S01-S21</a></li>
                            <li><a href="#pricing">Стоимость</a></li>
                            <li><a href="#troubleshooting">Устранение неполадок</a></li>
                            <li><a href="#api">Технический справочник</a></li>
                        </ol>
                    </div>

                    {{-- 1. Overview --}}
                    <div class="docs-section" id="overview">
                        <h3>1. Обзор модуля</h3>
                        <p>
                            <strong>Claude AI</strong> — модуль FreeScout для интеллектуальной поддержки клиентов
                            на базе Claude API (Anthropic). Модуль помогает операторам быстрее решать проблемы клиентов
                            и автоматически генерирует черновики ответов.
                        </p>

                        <h4>Возможности:</h4>
                        <table>
                            <tr>
                                <td>🧠 Подсказки оператору</td>
                                <td>AI анализирует тикет и выдаёт диагноз, решение, готовый ответ</td>
                                <td><span class="badge-feature badge-ready">Готово</span></td>
                            </tr>
                            <tr>
                                <td>✍️ Черновики ответов</td>
                                <td>Генерация вежливого ответа клиенту с учётом контекста</td>
                                <td><span class="badge-feature badge-ready">Готово</span></td>
                            </tr>
                            <tr>
                                <td>🔗 Интеграция с биллингом</td>
                                <td>Claude запрашивает данные абонента через REST API</td>
                                <td><span class="badge-feature badge-ready">Готово</span></td>
                            </tr>
                            <tr>
                                <td>📚 База знаний</td>
                                <td>Статьи FreeScout KB автоматически включаются в контекст</td>
                                <td><span class="badge-feature badge-ready">Готово</span></td>
                            </tr>
                            <tr>
                                <td>🏷️ Классификация</td>
                                <td>Автоматическое определение сценария S01-S21</td>
                                <td><span class="badge-feature badge-ready">Готово</span></td>
                            </tr>
                            <tr>
                                <td>💬 Автоответ клиентам</td>
                                <td>Автоматический ответ на новые тикеты (с проверкой оператором)</td>
                                <td><span class="badge-feature badge-planned">В разработке</span></td>
                            </tr>
                        </table>
                    </div>

                    {{-- 2. Install --}}
                    <div class="docs-section" id="install">
                        <h3>2. Установка</h3>
                        <ol>
                            <li>Скопировать папку <code>ClaudeAI</code> в <code>/var/www/freescout/Modules/</code></li>
                            <li>Перейти в <strong>Управление → Claude AI</strong></li>
                            <li>Ввести API ключ с <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></li>
                            <li>Нажать <strong>«Проверить подключение»</strong></li>
                            <li>Сохранить настройки</li>
                        </ol>
                        <p>Никаких миграций БД, Composer-зависимостей или перезапусков не требуется.</p>
                    </div>

                    {{-- 3. Settings --}}
                    <div class="docs-section" id="settings">
                        <h3>3. Настройки</h3>
                        <table>
                            <tr><th>Параметр</th><th>Описание</th><th>По умолчанию</th></tr>
                            <tr><td><code>API ключ</code></td><td>Ключ Anthropic API (sk-ant-...)</td><td>—</td></tr>
                            <tr><td><code>Модель</code></td><td>Claude Haiku 4.5 / Sonnet 4.6 / Opus 4.6</td><td>Haiku 4.5</td></tr>
                            <tr><td><code>Max tokens</code></td><td>Максимальная длина ответа</td><td>1024</td></tr>
                            <tr><td><code>Temperature</code></td><td>Креативность (0 = точно, 1 = творчески)</td><td>0.3</td></tr>
                            <tr><td><code>Подсказка оператору</code></td><td>Показывать кнопку AI в сайдбаре тикета</td><td>✅ Включено</td></tr>
                            <tr><td><code>Автоответ</code></td><td>Генерировать черновик ответа</td><td>❌ Выключено</td></tr>
                            <tr><td><code>Авто-классификация</code></td><td>Определять сценарий (S01-S21)</td><td>✅ Включено</td></tr>
                            <tr><td><code>Требовать проверку</code></td><td>Оператор проверяет перед отправкой</td><td>✅ Включено</td></tr>
                            <tr><td><code>База знаний</code></td><td>Подгружать статьи KB в контекст Claude</td><td>✅ Включено</td></tr>
                            <tr><td><code>URL биллинга</code></td><td>Адрес Smit Billing REST API</td><td>—</td></tr>
                            <tr><td><code>Слова эскалации</code></td><td>При этих словах не автоотвечать</td><td>оператор, человек, живой</td></tr>
                        </table>
                    </div>

                    {{-- 4. Features --}}
                    <div class="docs-section" id="features">
                        <h3>4. Функционал</h3>

                        <h4 id="operator">4.1. Подсказки оператору</h4>
                        <p>В сайдбаре каждого тикета появляется кнопка <strong>🧠 Claude AI</strong>.</p>
                        <p>При нажатии Claude анализирует текст тикета и данные абонента, выдавая:</p>
                        <ul>
                            <li><strong>📋 Сценарий</strong> — классификация S01-S21</li>
                            <li><strong>🔍 Диагноз</strong> — вероятная причина проблемы</li>
                            <li><strong>✅ Что проверить</strong> — пошаговый чеклист</li>
                            <li><strong>🛠 Решение</strong> — рекомендуемые действия</li>
                            <li><strong>💬 Ответ клиенту</strong> — готовый текст для копирования</li>
                            <li><strong>⚠️ Эскалация</strong> — нужна ли передача и куда</li>
                        </ul>

                        <h4 id="drafts">4.2. Черновики ответов</h4>
                        <p>Кнопка <strong>✍️ Черновик</strong> генерирует вежливый ответ клиенту с учётом:</p>
                        <ul>
                            <li>Всей истории переписки в тикете</li>
                            <li>Данных абонента из биллинга</li>
                            <li>Статей базы знаний</li>
                            <li>Сценариев диагностики</li>
                        </ul>
                        <p>Ответ появляется с кнопкой <strong>📋 Копировать</strong> — оператор вставляет в окно ответа.</p>
                    </div>

                    {{-- 5. Billing --}}
                    <div class="docs-section" id="billing">
                        <h3>5. Интеграция с биллингом</h3>
                        <p>Модуль обращается к Smit Billing REST API v2 для получения данных абонента:</p>
                        <ul>
                            <li>Поиск по номеру договора или телефону</li>
                            <li>Баланс, тариф, статус подключения</li>
                            <li>Адрес, услуги, блокировки</li>
                            <li>Активация обещанного платежа</li>
                        </ul>
                        <p>При переезде биллинга на другой сервер — достаточно обновить URL в настройках.</p>
                        <pre>URL: https://billing.example.com
API: /rest_api/v2/Abonents/?qf={contract_number}</pre>
                    </div>

                    {{-- 6. KB --}}
                    <div class="docs-section" id="kb">
                        <h3>6. База знаний</h3>
                        <p>
                            Если в FreeScout установлен модуль <strong>Knowledge Base</strong>,
                            Claude автоматически получает все опубликованные статьи в контекст.
                        </p>
                        <p>Это позволяет AI давать ответы, основанные на вашей базе знаний, а не выдумывать.</p>
                        <p>Статьи кешируются на 1 час. Для сброса кеша: <code>php artisan cache:clear</code></p>
                    </div>

                    {{-- 7. Prompts --}}
                    <div class="docs-section" id="prompts">
                        <h3>7. System prompts</h3>
                        <p>Модуль содержит 2 встроенных промпта:</p>

                        <h4>Промпт для клиентов (customer.txt)</h4>
                        <p>Используется для генерации ответов клиентам. Включает:</p>
                        <ul>
                            <li>Роль и тон общения (дружелюбный, эмпатичный)</li>
                            <li>21 сценарий обращений (S01-S21)</li>
                            <li>Деревья диагностики (нет интернета, низкая скорость, расторжение)</li>
                            <li>Тарифы и цены</li>
                            <li>Проблемные зоны (районы с частыми авариями)</li>
                            <li>Глоссарий (ONU, LOS, PPPoE, РОБОР)</li>
                            <li>Строгие правила (не обещать сроки, не критиковать, удержание)</li>
                        </ul>

                        <h4>Промпт для операторов (operator.txt)</h4>
                        <p>Используется для подсказок. Выдаёт структурированный ответ:</p>
                        <pre>📋 СЦЕНАРИЙ: S04
🔍 ДИАГНОЗ: Низкая скорость, возможна деградация линии
✅ ПРОВЕРИТЬ:
1. Сессия активна, трафик в KB → S09
2. Замер скорости через кабель
🛠 РЕШЕНИЕ:
1. Попросить замер на speedtest.yandex.ru через кабель
2. Если скорость низкая — создать диагностический тикет
💬 ОТВЕТ КЛИЕНТУ:
"Давайте проверим! Подключите компьютер кабелем..."
⚠️ ЭСКАЛАЦИЯ: Не нужна</pre>

                        <p>Вы можете заменить промпты на свои в настройках модуля.</p>
                    </div>

                    {{-- 8. Tools --}}
                    <div class="docs-section" id="tools">
                        <h3>8. Tool Use (функции)</h3>
                        <p>Claude может вызывать функции для получения данных из биллинга:</p>
                        <table>
                            <tr><th>Функция</th><th>Описание</th><th>Статус</th></tr>
                            <tr><td><code>check_subscriber</code></td><td>Поиск абонента по договору/адресу</td><td><span class="badge-feature badge-ready">Готово</span></td></tr>
                            <tr><td><code>check_session</code></td><td>Проверка RADIUS-сессии</td><td><span class="badge-feature badge-planned">Планируется</span></td></tr>
                            <tr><td><code>check_mass_outage</code></td><td>Проверка массовой аварии</td><td><span class="badge-feature badge-planned">Планируется</span></td></tr>
                            <tr><td><code>activate_promise_pay</code></td><td>Активация обещанного платежа</td><td><span class="badge-feature badge-ready">Готово</span></td></tr>
                        </table>
                    </div>

                    {{-- 9. Scenarios --}}
                    <div class="docs-section" id="scenarios">
                        <h3>9. Сценарии S01-S21</h3>
                        <table>
                            <tr><th>Код</th><th>Сценарий</th><th>Действие</th></tr>
                            <tr><td>S01</td><td>Нет интернета</td><td>Диагностика → перезагрузка → заявка</td></tr>
                            <tr><td>S02</td><td>Горит LOS (обрыв оптики)</td><td>Обязательный выезд</td></tr>
                            <tr><td>S03</td><td>Медиаконвертер</td><td>Проверка индикаторов, замена</td></tr>
                            <tr><td>S04</td><td>Низкая скорость</td><td>Замер через кабель, диагностика</td></tr>
                            <tr><td>S05</td><td>Роутер</td><td>Перезагрузка, настройка PPPoE</td></tr>
                            <tr><td>S06</td><td>Wi-Fi</td><td>Проверка канала, расположения</td></tr>
                            <tr><td>S07</td><td>Баланс/оплата</td><td>Информация, обещанный платёж</td></tr>
                            <tr><td>S08</td><td>Новое подключение</td><td>Сбор данных, заявка</td></tr>
                            <tr><td>S09</td><td>Сессия в KB</td><td>Деградация линии → диагностический тикет</td></tr>
                            <tr><td>S10</td><td>Обрыв кабеля</td><td>Выезд</td></tr>
                            <tr><td>S11</td><td>Повторное обращение (3+)</td><td>Автоэскалация руководителю</td></tr>
                            <tr><td>S14</td><td>Смена тарифа</td><td>Показать варианты, активировать</td></tr>
                            <tr><td>S15</td><td>IPTV</td><td>Проверка услуги, перезагрузка приставки</td></tr>
                            <tr><td>S17</td><td>Расторжение</td><td>⚠️ Удержание! Потом заморозка/расторжение</td></tr>
                            <tr><td>S18</td><td>Массовая авария</td><td>Проверка реестра, информирование</td></tr>
                            <tr><td>S20</td><td>Переезд</td><td>Проверка покрытия</td></tr>
                            <tr><td>S21</td><td>Юрлицо</td><td>Сбор данных → коммерческий отдел</td></tr>
                        </table>
                    </div>

                    {{-- 10. Pricing --}}
                    <div class="docs-section" id="pricing">
                        <h3>10. Стоимость</h3>
                        <table>
                            <tr><th>Модель</th><th>Input</th><th>Output</th><th>~50 диалогов/день</th><th>~250/день</th></tr>
                            <tr class="success">
                                <td><strong>Haiku 4.5 ⭐</strong></td>
                                <td>$0.80/1M</td><td>$4/1M</td>
                                <td><strong>~$11/мес</strong></td><td>~$55/мес</td>
                            </tr>
                            <tr>
                                <td>Sonnet 4.6</td>
                                <td>$3/1M</td><td>$15/1M</td>
                                <td>~$44/мес</td><td>~$220/мес</td>
                            </tr>
                            <tr>
                                <td>Opus 4.6</td>
                                <td>$15/1M</td><td>$75/1M</td>
                                <td>~$220/мес</td><td>~$1100/мес</td>
                            </tr>
                        </table>
                        <p><strong>Рекомендация:</strong> начните с Haiku 4.5. При необходимости переключите на Sonnet 4.6 в настройках (без перезапуска).</p>
                    </div>

                    {{-- 11. Troubleshooting --}}
                    <div class="docs-section" id="troubleshooting">
                        <h3>11. Устранение неполадок</h3>
                        <table>
                            <tr><th>Проблема</th><th>Решение</th></tr>
                            <tr>
                                <td>Кнопки AI не появляются в тикете</td>
                                <td>Проверьте что <code>Подсказка оператору</code> включена в настройках. Очистите кеш: <code>php artisan cache:clear</code></td>
                            </tr>
                            <tr>
                                <td>Ошибка "API ключ не настроен"</td>
                                <td>Введите ключ в настройках и нажмите «Проверить подключение»</td>
                            </tr>
                            <tr>
                                <td>Ошибка 401 / authentication_error</td>
                                <td>Неверный API ключ. Получите новый на <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></td>
                            </tr>
                            <tr>
                                <td>Ошибка 429 / rate_limit</td>
                                <td>Превышен лимит запросов. Подождите минуту или повысьте тариф Anthropic</td>
                            </tr>
                            <tr>
                                <td>Медленные ответы (>30 сек)</td>
                                <td>Переключитесь на Haiku 4.5 (самая быстрая модель)</td>
                            </tr>
                            <tr>
                                <td>Claude выдумывает данные</td>
                                <td>Убедитесь что URL биллинга указан и доступен. Claude должен получать данные через Tool Use</td>
                            </tr>
                            <tr>
                                <td>Модуль не появляется в меню</td>
                                <td>Проверьте что вы вошли как администратор. Модуль доступен только admin-пользователям</td>
                            </tr>
                        </table>

                        <h4>Логи</h4>
                        <pre>tail -f /var/www/freescout/storage/logs/laravel.log | grep ClaudeAI</pre>
                        <p>Каждый запрос к API логируется с метками <code>[ClaudeAI]</code>, включая использование токенов.</p>
                    </div>

                    {{-- 12. API Reference --}}
                    <div class="docs-section" id="api">
                        <h3>12. Технический справочник</h3>

                        <h4>Маршруты</h4>
                        <table>
                            <tr><th>Метод</th><th>URL</th><th>Описание</th></tr>
                            <tr><td>GET</td><td><code>/claudeai/settings</code></td><td>Страница настроек</td></tr>
                            <tr><td>POST</td><td><code>/claudeai/settings</code></td><td>Сохранение настроек</td></tr>
                            <tr><td>POST</td><td><code>/claudeai/test</code></td><td>Тест API подключения</td></tr>
                            <tr><td>POST</td><td><code>/claudeai/operator-hint</code></td><td>Подсказка оператору (AJAX)</td></tr>
                            <tr><td>POST</td><td><code>/claudeai/auto-reply</code></td><td>Генерация черновика (AJAX)</td></tr>
                            <tr><td>GET</td><td><code>/claudeai/stats</code></td><td>Статистика использования</td></tr>
                            <tr><td>GET</td><td><code>/claudeai/docs</code></td><td>Эта документация</td></tr>
                        </table>

                        <h4>Файлы модуля</h4>
                        <pre>ClaudeAI/
├── module.json                     # Метаданные
├── Config/config.php               # Конфигурация
├── Services/ClaudeService.php      # Claude API клиент + Tool Use
├── Http/Controllers/               # Контроллеры
├── Http/routes.php                 # Маршруты
├── Providers/                      # ServiceProvider + хуки
└── Resources/
    ├── prompts/customer.txt        # Промпт для клиентов
    ├── prompts/operator.txt        # Промпт для операторов
    └── views/settings/             # Blade-шаблоны</pre>

                        <h4>Claude API</h4>
                        <p>Модуль использует <a href="https://docs.anthropic.com/en/api/messages" target="_blank">Claude Messages API</a> с Tool Use:</p>
                        <pre>POST https://api.anthropic.com/v1/messages
Headers:
  x-api-key: sk-ant-...
  anthropic-version: 2023-06-01
  Content-Type: application/json

Body:
{
  "model": "claude-haiku-4-5-20251001",
  "max_tokens": 1024,
  "temperature": 0.3,
  "system": "...",
  "messages": [...],
  "tools": [...]
}</pre>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
