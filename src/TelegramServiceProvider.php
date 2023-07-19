<?php

namespace Cornatul\Telegram;

use Cornatul\Telegram\Commands\ReadTelegramMessages;
use Cornatul\Telegram\Services\TelegramService;
use Illuminate\Console\Scheduling\Schedule;
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

            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command(ReadTelegramMessages::class)->everySecond();
            });
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
