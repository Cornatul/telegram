<?php


use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;

Route::post('/telegram/updates', function() {

    $telegram = new Api(config('telegram.bot_token'));
    $updates = $telegram->getUpdates([
        'chat_id' => '@lzoshare',
        'limit' => 100,
        'offset' => -1,
    ]);

  return new Response( ($updates));

});
