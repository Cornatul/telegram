<?php

namespace Cornatul\Telegram\Commands;

use Cornatul\Telegram\Jobs\TelegramExtractorJob;
use Cornatul\Telegram\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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
     * @throws \Exception
     */
    public final function handle(TelegramService $telegram):void
    {
        $this->info('Reading the latest messages from the Telegram and action on them.');

        $messages = $telegram->getUpdates([
            'offset' => -1,
            'limit' => 1,
            'timeout' => 0,
        ]);

        if (count($messages) > 0) {
            $message = $messages[0];

            $chatMessage = $message->getMessage()->get('text');

            $url = $this->extractUrl($chatMessage);


            if (Cache::has("telegram_url_{$url}") === true) {

               $this->output->success('This url has already been processed');
               return;
            }


            if ($url) {

                Cache::put("telegram_url_{$url}", $url);


                $this->info('URL found: ' . $url);

                $chatID = $message->getMessage()->get('chat')['id'];

                dispatch(new TelegramExtractorJob($chatID, $url));
            }


        }

    }

    /**
     * @throws \Exception
     */
    private function extractUrl(string $string):string
    {
        $pattern = '/\bhttps?:\/\/\S+/i';

        // Perform a regular expression match to find the URL in the string
        if (preg_match($pattern, $string, $matches)) {
            return $matches[0];
        }

        // Return null if no valid URL is found in the string
		throw new \Exception('No url found in the message skipping...');
    }
}
