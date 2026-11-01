# BalanceMonitor — балансы сервисов в шапке

Показывает администраторам в шапке FreeScout три баланса: облачный сервер (ServerSpace, с числом дней до исчерпания), API нейросети
(OpenAI) и телефония (Mango Office). Красным подсвечиваются значения ниже порога: ServerSpace меньше $5, OpenAI меньше $1,
Mango меньше 50 ₽ (пороги — в `Providers/BalanceMonitorServiceProvider.php`).

Настройки — «Настройки → Balance Monitor» (баланс OpenAI вводится вручную). Ключи в `.env`: `SERVERSPACE_API_KEY`, `MANGO_API_KEY`,
`MANGO_SECRET_KEY`.

Ежедневный отчёт в Telegram с теми же балансами у нас делал отдельный скрипт на сервере, в модуль он не входит.

## Установка

```bash
cp -r BalanceMonitor /var/www/freescout/Modules/BalanceMonitor
cd /var/www/freescout
php artisan module:enable BalanceMonitor
php artisan cache:clear
```

Папка модуля должна называться ровно `BalanceMonitor`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
