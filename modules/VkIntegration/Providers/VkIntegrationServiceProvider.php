<?php

namespace Modules\VkIntegration\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class VkIntegrationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        // routes loaded via start.php
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    public function hooks()
    {
        // Hook to add VK channel icon to conversations list
//        \Eventy::addFilter('channel.name', function($name, $channel) {
//            if ($channel == \Modules\VkIntegration\Entities\VkChannel::CHANNEL) {
//                return 'VK';
//            }
//            return $name;
//        }, 20, 2);

//        // Hook to add VK icon
//        \Eventy::addFilter('channel.icon', function($icon, $channel) {
//            if ($channel == \Modules\VkIntegration\Entities\VkChannel::CHANNEL) {
//                return '<i class="fab fa-vk" style="color: #4680C2;"></i>';
//            }
//            return $icon;
//        }, 20, 2);

//         // Hook to register VK channel in channels list
//         \Eventy::addFilter('channels.list', function($channels) {
//             $channels[\Modules\VkIntegration\Entities\VkChannel::CHANNEL] = 'VK';
//             return $channels;
//         }, 20);

        // Hook to process outgoing messages
        \Eventy::addAction('conversation.send_reply', function($conversation, $threads, $customer) {
            if ($conversation->channel == \Modules\VkIntegration\Entities\VkChannel::CHANNEL) {
                \Modules\VkIntegration\Entities\VkChannel::sendReply($conversation, $threads);
            }
        }, 20, 3);

        // Hook to add settings menu in mailbox settings
        \Eventy::addAction('settings.menu', function($mailbox) {
            if (auth()->user()->isAdmin()) {
                echo \View::make('vkintegration::partials/settings_menu', [])->render();
            }
        }, 35);

        // Hook to add to Manage menu (after SystemFilter)
        \Eventy::addAction('menu.manage.after_mailboxes', function() {
            if (\Auth::user() && \Auth::user()->isAdmin()) {
                echo \View::make('vkintegration::partials/manage_menu', [])->render();
            }
        }, 41);

        // Отображаем плашку VK для тикетов в списке
////        \Eventy::addAction('conversations_table.before_subject', function($conversation) {
////            if ($conversation->channel == \Modules\VkIntegration\Entities\VkChannel::CHANNEL) {
////                echo '<span class="fs-tag pull-left" style="background-color: #4a76a8;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-comment"></small> VK</span></span>';
////            }
////        }, 20);

        // Отображаем плашку VK в просмотре тикета
//        \Eventy::addAction('conversation.after_subject', function($conversation, $mailbox) {
//            if ($conversation->channel == \Modules\VkIntegration\Entities\VkChannel::CHANNEL) {
//                echo '<span class="fs-tag fs-tag-md" style="background-color: #4a76a8;"><span class="fs-tag-name" style="color: white;"><small class="glyphicon glyphicon-comment"></small> VK</span></span>';
//            }
//        }, 20, 2);
    }

    public function register()
    {
        $this->registerConfig();
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('vkintegration.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'vkintegration'
        );
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/vkintegration');
        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/vkintegration';
        }, \Config::get('view.paths')), [$sourcePath]), 'vkintegration');
    }

    public function provides()
    {
        return [];
    }
}
