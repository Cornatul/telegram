<?php

namespace Cornatul\Telegram;

use Cornatul\Telegram\Services\TelegramService;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
        public final function boot(): void
        {
            //register config file
            $this->publishes([
                __DIR__.'/Config/telegram.php' => config_path('telegram.php'),
            ], 'config');
        }
        public final function register(): void
        {
            //register command
            $this->commands([
                Commands\ReadTelegramMessages::class,
            ]);

            $this->app->singleton(TelegramService::class, function (Application $app) {
                return new TelegramService(
                    config('telegram.bots.mybot.token')
                );
            });
        }
}
