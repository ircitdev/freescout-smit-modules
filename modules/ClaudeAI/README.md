# ClaudeAI — ИИ-помощник на Claude API

Подсказки оператору и черновики ответов клиенту прямо в обращении (оператор проверяет и отправляет сам — автоматической отправки
в модуле нет). Страницы настроек в меню «Управление»: `/claudeai/settings`, проверка соединения, статистика, встроенная справка.

Настройки (хранятся в опциях FreeScout): `claudeai_api_key` (ключ Anthropic), `claudeai_model`, `claudeai_temperature`,
`claudeai_max_tokens`, `claudeai_use_kb`, `claudeai_operator_hint`, `claudeai_billing_url`. Промпты — `Resources/prompts/customer.txt`
(для клиента) и `operator.txt` (для оператора). Промпты читаются **из этих файлов**: поля промптов и часть флагов на странице
настроек (`auto_classify`, `escalation_keywords`, `max_auto_replies`, `require_review`) сохраняются, но сервисом не используются.

Промпты — **наши рабочие**, для интернет-провайдера: названия, цены и коды сценариев замените на свои.

## Установка

```bash
cp -r ClaudeAI /var/www/freescout/Modules/ClaudeAI
cd /var/www/freescout
php artisan module:enable ClaudeAI
php artisan cache:clear
```

Папка модуля должна называться ровно `ClaudeAI`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
