# KnowledgeBase — внутренняя база знаний оператора

Одна страница `/knowledgebase`: карточки сценариев поддержки (коды S01–S13 и S17: нет интернета, обрыв оптики, медиаконвертер,
низкая скорость, роутер, Wi-Fi, оплата, подключение, расторжение…), дерево решений и шаблоны диалогов с поиском и фильтрами.
Весь контент лежит в одном шаблоне
`Resources/views/index.blade.php` — для своей отрасли переписывается за вечер.

> **Внимание: имя.** У платного FreeScout есть собственный модуль «Knowledge Base» с таким же названием и алиасом. Вместе они не уживутся: если он у вас стоит, переименуйте этот модуль (папка, namespace и алиас в `module.json`).

## Установка

```bash
cp -r KnowledgeBase /var/www/freescout/Modules/KnowledgeBase
cd /var/www/freescout
php artisan module:enable KnowledgeBase
php artisan cache:clear
```

Папка модуля должна называться ровно `KnowledgeBase`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
