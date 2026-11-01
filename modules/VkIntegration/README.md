# VkIntegration — канал ВКонтакте

Гибрид: PHP-модуль внутри FreeScout и отдельный мост на Python (**в репозиторий не входит**).

Что делает модуль:

* регистрирует канал 10 «ВКонтакте» (хуки `channel.name`, `channel.icon`, `channels.list`) и страницу настроек
  (меню «Управление» и настройки почтового ящика);
* принимает Callback API ВКонтакте на `POST /vk/webhook`: подтверждение, проверка секрета, пересылка события мосту;
* отдаёт мосту настройки на `GET /vk/config` и принимает его отчёты на `POST /vk/bridge-status`;
* из админки проверяет связь, перезапускает и останавливает мост, показывает его журнал;
* ответ оператора уходит клиенту через мост (хук `conversation.send_reply`).

Мост слушает порт 8901 (адрес — настройка `vkintegration.bridge_url`), проверка живости — `GET /status`.

## Безопасность

* Обязательно задайте секрет моста в настройках модуля: при пустом секрете вебхук принимает любые запросы.
* `/vk/config` и `/vk/bridge-status` принимают секрет только в заголовке `X-Secret-Key`.
* Тексты сообщений попадают в журналы только при `VK_DEBUG=true` в `.env` (`/tmp/vk_callback.log` и записи `[VK DEBUG]`).

## Установка

```bash
cp -r VkIntegration /var/www/freescout/Modules/VkIntegration
cd /var/www/freescout
php artisan module:enable VkIntegration
php artisan cache:clear
```

Папка модуля должна называться ровно `VkIntegration`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
