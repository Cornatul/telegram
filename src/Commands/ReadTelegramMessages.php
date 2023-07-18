<?php

namespace Cornatul\Telegram\Commands;

use Cornatul\Telegram\Services\TelegramService;
use Illuminate\Console\Command;
use Telegram\Bot\Exceptions\TelegramSDKException;

class ReadTelegramMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:read';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for reading the latest messages from the Telegram and action on them.';

    /**
     * Execute the console command.
     * @throws TelegramSDKException
     */
    public function handle(TelegramService $telegram)
    {
        $this->info('Reading the latest messages from the Telegram and action on them.');

        $messages = $telegram->getUpdates([
            'offset' => -1,
            'limit' => 1,
            'timeout' => 0,
        ]);

        foreach ($messages as $message) {
            $this->info($message->getMessage()->get('text'));
        }
    }
}
