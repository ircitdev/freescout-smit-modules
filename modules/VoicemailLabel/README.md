# VoicemailLabel — канал «голосовая почта»

Регистрирует канал 200 (хуки `channel.icon`, `channel.name`, `channels.list`): значок телефона и подпись «55-35-59» у обращений,
пришедших с голосовой почты. Используется вместе с модулем VoiceMail.

## Установка

```bash
cp -r VoicemailLabel /var/www/freescout/Modules/VoicemailLabel
cd /var/www/freescout
php artisan module:enable VoicemailLabel
php artisan cache:clear
```

Папка модуля должна называться ровно `VoicemailLabel`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
