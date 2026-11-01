# AidaSessions — просмотр сессий ИИ-чата

Страницы `/ai-sessions` (список со счётчиками по веткам «Поддержка» и «Подключение»), `/ai-sessions/{id}` (переписка),
скачивание и удаление. Пункт в меню «Управление» и блок в карточке обращения (хук `conversation.before_prev_convs`).

Читает таблицы `aida_conversations` и `aida_messages` в базе FreeScout — их заполняет наш ИИ-бот. Без этих таблиц модуль бесполезен;
как пример «журнала ИИ-диалогов внутри helpdesk» подойдёт.

## Установка

```bash
cp -r AidaSessions /var/www/freescout/Modules/AidaSessions
cd /var/www/freescout
php artisan module:enable AidaSessions
php artisan cache:clear
```

Папка модуля должна называться ровно `AidaSessions`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
