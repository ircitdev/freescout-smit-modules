# SystemFilter — автоудаление системных писем

Не даёт мусору попадать в очередь: отчёты о недоставке, автоответчики, DMARC-отчёты, письма от `mailer-daemon@`, `noreply@` и т. п.
удаляются в момент создания обращения (хук `conversation.created_by_customer`). Работают регулярные выражения темы и списки адресов
в `Providers/SystemFilterServiceProvider.php`. `Listeners/BounceEmailFilter.php` в модуле не подключён — это заготовка; список слов на
странице настроек `/systemfilter/settings` (меню «Управление») относится именно к ней.

## Установка

```bash
cp -r SystemFilter /var/www/freescout/Modules/SystemFilter
cd /var/www/freescout
php artisan module:enable SystemFilter
php artisan cache:clear
```

Папка модуля должна называться ровно `SystemFilter`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
