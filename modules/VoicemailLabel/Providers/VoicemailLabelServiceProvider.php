<?php

namespace Modules\VoicemailLabel\Providers;

use Illuminate\Support\ServiceProvider;

class VoicemailLabelServiceProvider extends ServiceProvider
{
    const CHANNEL = 200; // Unique channel ID for voicemail
    const CHANNEL_NAME = '55-35-59';

    /**
     * Boot the application events.
     */
    public function boot()
    {
        $this->registerConfig();
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Hook to display "55-35-59" label
        \Eventy::addFilter('channel.name', function($name, $channel) {
            if ($channel == self::CHANNEL) {
                return self::CHANNEL_NAME;
            }
            return $name;
        }, 20, 2);

        // Hook to add phone icon
        \Eventy::addFilter('channel.icon', function($icon, $channel) {
            if ($channel == self::CHANNEL) {
                return '<i class="glyphicon glyphicon-phone"></i>';
            }
            return $icon;
        }, 20, 2);

        // Register channel in channels list
        \Eventy::addFilter('channels.list', function($channels) {
            $channels[self::CHANNEL] = self::CHANNEL_NAME;
            return $channels;
        }, 20);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfig();
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path('voicemaillabel', 'Config/config.php') => config_path('voicemaillabel.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path('voicemaillabel', 'Config/config.php'), 'voicemaillabel'
        );
    }
}
