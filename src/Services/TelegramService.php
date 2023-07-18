<?php

namespace Cornatul\Telegram\Services;

use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class TelegramService extends Api
{
    public function __construct(string $token, bool $botUsername = false)
    {
        parent::__construct($token, $botUsername);
    }


    final public function getUpdates(array $params = [], bool $shouldDispatchEvents = false): array
    {
        return parent::getUpdates($params, false);
    }
}
