# SmitBilling — данные абонента из СмИТ Биллинга в карточке обращения

Боковая панель обращения: договор, тариф, баланс, услуги, адрес, телефоны и статус абонента; кнопка «Обновить» и ссылка «в биллинг».
В модуле есть и маршруты `/ping` и `/ai-assist`, но их кнопки в панели закомментированы, а сами маршруты рассчитаны на внутренний сервис
на `127.0.0.1:8900`, который здесь не опубликован.

Настройка в `.env`: `SMIT_BILLING_API` (адрес REST API v2), `AIDA_API_KEY` (ключ доступа; имя историческое), `SMITBILLING_VERIFY_SSL`
(по умолчанию `true`). Время кеша — `Config/config.php`.

## Установка

```bash
cp -r SmitBilling /var/www/freescout/Modules/SmitBilling
cd /var/www/freescout
php artisan module:enable SmitBilling
php artisan cache:clear
```

Папка модуля должна называться ровно `SmitBilling`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
