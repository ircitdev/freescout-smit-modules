<?php

namespace Modules\UrgencyButton\Providers;

use Illuminate\Support\ServiceProvider;

class UrgencyButtonServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->hooks();
    }

    public function register()
    {
    }

    public function hooks()
    {
        // Кнопка "Срочность" в bulk actions
        \Eventy::addAction('bulk_actions.before_delete', function($mailbox) {
            $options = [
                '1' => 'Обычная',
                '2' => 'Высокая',
                '3' => '🔥 Пожар'
            ];
            echo '<div class="btn-group">';
            echo '<button type="button" class="btn btn-default" data-toggle="dropdown" title="Срочность">';
            echo '<span class="glyphicon glyphicon-fire"></span>';
            echo '<span class="caret"></span>';
            echo '</button>';
            echo '<ul class="dropdown-menu conv-urgency">';
            foreach ($options as $key => $name) {
                echo '<li><a href="#" data-urgency="'.$key.'">'.$name.'</a></li>';
            }
            echo '</ul>';
            echo '</div>';
        });

        // JavaScript обработчик
        \Eventy::addAction('javascript', function() {
            echo '
            $(document).ready(function() {
                $(".conv-urgency a").click(function(e) {
                    e.preventDefault();
                    var urgency = $(this).data("urgency");
                    var conv_ids = [];
                    $(".conv-checkbox:checked").each(function() {
                        conv_ids.push($(this).val());
                    });
                    if (conv_ids.length == 0) {
                        alert("Выберите тикеты");
                        return;
                    }
                    fsAjax({
                        action: "bulk_set_urgency",
                        conversation_ids: conv_ids,
                        urgency: urgency
                    }, laroute.route("conversations.ajax"), function(response) {
                        if (typeof response.status != "undefined" && response.status == "success") {
                            window.location.reload();
                        } else {
                            alert("Ошибка: " + (response.msg || "неизвестная"));
                        }
                    }, true);
                });
            });
            ';
        });
    }
}
