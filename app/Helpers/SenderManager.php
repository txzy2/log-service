<?php

namespace App\Helpers;

use App\Enums\SendTemplateType;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Services\IncidentNotifications\SenderResolver;
use App\Values\TelegramSendData;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Throwable;

class SenderManager extends Helpers
{
    public function __construct(
        private readonly IncidentType $incidentType,
        private readonly Incident $incident,
    ){ }

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

    /*
     * telegramSendMessage - отправляет сообщение в телеграм
     *
     * @param string $message
     * @return void
     */

    /**
     * processNotify - отправляет сообщение об инциденте на сервис рассылки
     *
     * @return void
     */
    public function processNotify(): void
    {
        $this->incidentType->load('sendTemplate'); // Подгружаем таблицу send_template, т.к она связана через send_template_id

        try {
            $senderObj = SenderResolver::resolve(SendTemplateType::tryFrom($this->incidentType->alias));
            Log::channel('debug')->info("Incident senderObj", [$senderObj]);
            $senderObj->send($this->incident, $this->incidentType);
        } catch (Throwable $e) {
            Log::channel('debug')->warning("preparePushOrMail Exception {$e->getMessage()}");
        }
    }
}
