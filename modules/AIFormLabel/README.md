# AIFormLabel — метка ИИ у обращений из чата и формы

Показывает метку у обращений, которые пришли из каналов 100 (чат-виджет) и 101 (форма на сайте).
Номера каналов — наши собственные: их заводят интеграции чата и формы, поэтому в вашей установке номера могут быть другими
(правятся в коде модуля).

## Установка

```bash
cp -r AIFormLabel /var/www/freescout/Modules/AIFormLabel
cd /var/www/freescout
php artisan module:enable AIFormLabel
php artisan cache:clear
```

Папка модуля должна называться ровно `AIFormLabel`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
