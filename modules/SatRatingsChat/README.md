# SatRatingsChat — оценки качества ответа в Telegram и ВКонтакте

Оценки ответа в чатах: после ответа оператора (хуки `chat_conversation.send_reply` и `conversation.send_reply`) клиенту уходит
просьба оценить ответ — в Telegram через перехват нажатий (middleware), во ВКонтакте через мост (`POST /satratingschat/vk-rate`).
Шкала 1–3. Просьба не чаще одного раза в 24 часа и только после настоящих ответов оператора (не заметок и не системных).
Оценка сохраняется через `SatRatingsHelper` платного модуля Satisfaction Ratings, а если его нет — прямо в поле `rating` реплики.
Адрес моста ВКонтакте — константа `VK_BRIDGE_URL` в `Providers/SatRatingsChatServiceProvider.php`.

Запрос моста `POST /satratingschat/vk-rate` идёт без сессии, поэтому защищён общим секретом: задайте `SATRATINGSCHAT_BRIDGE_SECRET` в `.env` и передавайте его в заголовке `X-Bridge-Secret`; без секрета ответ — 403.

## Установка

```bash
cp -r SatRatingsChat /var/www/freescout/Modules/SatRatingsChat
cd /var/www/freescout
php artisan module:enable SatRatingsChat
php artisan cache:clear
```

Папка модуля должна называться ровно `SatRatingsChat`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
