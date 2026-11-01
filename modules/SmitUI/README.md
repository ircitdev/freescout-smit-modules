# SmitUI — оформление и удобства интерфейса FreeScout

Фирменное оформление и мелкие доработки: шапка и мобильная шапка, режим монтажника (урезанный интерфейс), стили для канбана и
бейджей, скрытие ненужных почтовых ящиков, тёмная тема, переключатель темы, «стеклянные» окна, ограничение ширины, скрытие тегов и
меток. Сами бейджи каналов рисуют модули каналов, здесь только стили. Для канбана нужен платный модуль Kanban.
Опции: `smit_constrain_width_*`, `smit_glass_modals_*`, `smit_hide_labels_*`, `smit_hide_tags_*`, `smit_show_bg_*`.

Фоновые картинки и логотипы в репозиторий не включены: положите свои в `Public/img`. Модуль ждёт в вашей установке ещё и свои файлы
`/js/freescout_ai_button.js`, `/css/custom-colors.css` и `img/logo-brand.png` — их здесь нет, соответствующих элементов без них не будет.

## Установка

```bash
cp -r SmitUI /var/www/freescout/Modules/SmitUI
cd /var/www/freescout
php artisan module:enable SmitUI
php artisan cache:clear
```

Папка модуля должна называться ровно `SmitUI`. Проверялось на FreeScout 1.8.x.

## Лицензия

AGPL-3.0, как и сам FreeScout. См. `LICENSE` в корне репозитория.
