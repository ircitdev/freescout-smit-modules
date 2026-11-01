# UrgencyButton — массовая смена срочности

В списке обращений рядом с массовыми действиями (хук `bulk_actions.before_delete`) появляется выбор срочности:
«Обычная», «Высокая», «🔥 Пожар». Модуль рисует выпадающий список и отправляет выбранное обращениям действием
`bulk_set_urgency` в `conversations.ajax`. **Серверного обработчика этого действия в модуле нет** — запись срочности
(например, в пользовательское поле) нужно добавить у себя. Это скорее заготовка, чем готовый модуль.

## Установка

```bash
cp -r UrgencyButton /var/www/freescout/Modules/UrgencyButton
cd /var/www/freescout
php artisan module:enable UrgencyButton
php artisan cache:clear
```

Папка модуля должна называться ровно `UrgencyButton`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
