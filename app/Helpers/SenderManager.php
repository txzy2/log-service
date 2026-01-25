<?php

namespace App\Helpers;

use App\Values\TelegramSendData;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class SenderManager extends Helpers
{

    /**
     * telegramSendMessage - отправляет сообщение в телеграм
     *
     * @param TelegramSendData $data
     * @return void
     */
    public static function telegramSendMessage(TelegramSendData $data): void
    {
        $lineBreak = "\n";
        $bold = ['*', '*'];
        $code = ['```json', '```'];
        $dateTime = "$bold[0][=== " . date('H:i:s d-m-Y') . " ===]$bold[1]" . $lineBreak . $lineBreak;
        $appName = config('app.name') ?? ".env is not filled";

        $msgTitle = "$bold[0]Address: $bold[1]" . $appName . $lineBreak . "$bold[0]ERROR CLASS:$bold[1] $data->class" . $lineBreak . $lineBreak;

        $response = "$bold[0]Error INFO: $bold[1]$lineBreak";
        $response .= empty($text) ? "MESSAGE IS EMPTY" : $code[0] . "$lineBreak$text$lineBreak" . $code[1];

        $additionalInfoString = "$bold[0]Additional INFO:$bold[1]$lineBreak";
        $additionalInfoString .= $code[0] . " " . json_encode($data->additionalInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . $code[1];

        $preparedMessage = $dateTime . $msgTitle . "$bold[0]$data->title$bold[1]" . $lineBreak . $lineBreak . $response . $lineBreak . $additionalInfoString;

        try {
            Telegram::sendMessage([
                'chat_id' => config('app.chat_id'),
                'text' => $preparedMessage,
                'parse_mode' => 'Markdown',
            ]);

            Log::channel('telegramLogging')->error(static::getClassName() . "::telegramSendMessage SUCCESS SEND", [$preparedMessage]);
        } catch (Exception $e) {
            Log::channel('telegramLogging')->error(static::getClassName() . "::telegramSendMessage ERROR", [$e->getMessage()]);
        }
    }

}
