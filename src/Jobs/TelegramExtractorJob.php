<?php

namespace Cornatul\Telegram\Jobs;

use Cornatul\Feeds\DTO\ArticleDto;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramExtractorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Api $telegram;

    private int $chatID;

    private string $url;

    /**
     * @throws TelegramSDKException
     */
    public function __construct(int $chatID, string $url)
    {
        $this->telegram = new Api(config('telegram.bot_token'));
        $this->chatID = $chatID;
        $this->url = $url;

    }

    /**
     *  //todo replace this to use the config logic
     * @throws TelegramSDKException
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function handle(): void
    {

        $client = new \GuzzleHttp\Client();

        $response = $client->post("https://v1.nlpapi.org/article", [
            'json' => [
                'link' => $this->url
            ]
        ]);

        $collection = collect(
            json_decode(
                $response->getBody()->getContents(),
                false,
                512,
                JSON_THROW_ON_ERROR
            )
        );

        $dto = ArticleDto::from($collection->get('data'));

        $this->telegram->sendMessage([
            'chat_id' => $this->chatID,
            'text' => "{$dto->title}\n{$dto->summary}\n{$dto->banner}",
        ]);

    }


    public function fail($exception = null)
    {
        $this->telegram->sendMessage([
            'chat_id' => $this->chatID,
            'text' => $exception->getMessage(),
        ]);
    }

}
