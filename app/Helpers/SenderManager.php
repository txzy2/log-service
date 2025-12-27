<?php

namespace App\Helpers;

use App\Models\IncidentType;
use App\Enums\SendTemplateType;
use App\Jobs\SendMail;
use App\Models\Incident;
use App\Models\SendTemplate;
use App\Services\TemplateServiceFactory;
use App\Values\TelegramSendData;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class SenderManager extends Helpers
{
    /**
     * preparePushOrMail - отправляет сообщение об инциденте на сервис рассылки
     *
     * @param object $data
     * @return void
     */
    public static function preparePushOrMail(Incident $data, IncidentType $incidentType): void {
        $incidentType->load('sendTemplate'); // Подгружаем таблицу send_template, т.к она связана через send_temolate_id
        $data->save();

        match (SendTemplateType::from($incidentType->alias)) {
            SendTemplateType::PUSH_MAIL => static::prepareAndSendEmail(
                $incidentType->sendTemplate->to,
                $incidentType->sendTemplate->template,
                $data
            ),
            default => Log::channel("debug")
                ->error(
                    static::getClassName() . "::sendToSendService ERROR SEND TYPE",
                    [
                        'DATA' => $data,
                        'TEMPLATE_ID' => $incidentType->send_template_id
                    ]
                ),
        };
    }

    protected static function prepareAndSendEmail(string $to, string $template, Incident $data): void {
        try {
            $preparer = TemplateServiceFactory::create($data->class);
            $processedTemplate = $preparer->prepare($data, $template);

            if ($processedTemplate['success']) {
                SendMail::dispatch([
                    "to" => json_decode($to, true),
                    "template" => $processedTemplate['data']
                ])->onQueue('sendMailJob');
            }
        } catch (\InvalidArgumentException $e) {
            $error = $e->getMessage();
            Log::channel("debug")->error(static::getClassName() . '::prepareAndSendEmail EXCEPTION PREPEARE EMAIL', [$error]);
            static::telegramSendMessage(new TelegramSendData(
                static::getClassName(),
                "EXCEPTION PREPEARE EMAIL",
                $error,
                [
                    "data" => $data,
                    "to" => $to
                ]
            ));
        }
    }

    /*
     * telegramSendMessage - отправляет сообщение в телеграм
     *
     * @param string $message
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
        } catch (\Exception $e) {
            Log::channel('telegramLogging')->error(static::getClassName() . "::telegramSendMessage ERROR", [$e->getMessage()]);
        }
    }
}
