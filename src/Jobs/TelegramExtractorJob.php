<?php

namespace Cornatul\Telegram\Jobs;

use Cornatul\Feeds\DTO\ArticleDto;
use Cornatul\Social\Models\SocialAccountConfiguration;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\OAuth2\Client\Provider\LinkedIn;
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

        //send the linkedin message
        $this->shareessage($dto);
    }




    private function shareessage($dto)
    {

        $configuration = SocialAccountConfiguration::inRandomOrder()->get()->first();

        $config = json_decode($configuration->configuration);

        $token = json_decode($configuration->token);


        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer ' . $token->token->access_token,
            'Content-Type' => 'application/json',
            'X-Restli-Protocol-Version' => '2.0.0',
        ];

        $body = [
            'author' => 'urn:li:person:' . $token->user->id,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $dto->summary
                    ],
                    'shareMediaCategory' => 'ARTICLE',
                    'media' => [
                        [
                            'status' => 'READY',
                            'description' => [
                                'text' => $dto->summary,
                            ],
                            'originalUrl' => "https://lzomedia.com",
                            'title' => [
                                'text' => $dto->title,
                            ],
                            'thumbnails' => [
                                [
                                    'image' => $dto->banner,
                                    'resolvedUrl' => $dto->banner,
                                ],
                            ],
                        ],
                    ],

                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        return $client->request('POST', 'https://api.linkedin.com/v2/ugcPosts', [
            'headers' => $headers,
            'json' => $body,
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
