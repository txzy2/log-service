<?php

namespace App\Helpers;

use App\Models\IncidentType;
use App\Enums\SendTemplateType;
use App\Models\SendTemplate;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class SenderManager
{
    private const ERROR_CLASS = __CLASS__;

    /**
     * preparePushOrMail - отправляет сообщение об инциденте на сервис рассылки
     *
     * @param object $data
     * @return void
     */
    public static function preparePushOrMail(object $data, int $sendTemplateId): void
    {
        $existIncidentType = IncidentType::where('send_template_id', $sendTemplateId)->first();
        if (!$existIncidentType) {
            Log::channel("debug")->error(self::ERROR_CLASS . "::sendToSendService ERROR SEND MAIL TO SENDER SERVICE", [$data]);
            return;
        }

        $existSendTemplate = SendTemplate::where('id', $sendTemplateId)->first();

        match (SendTemplateType::from($existIncidentType->alias)) {
            SendTemplateType::PUSH_MAIL => static::prepareAndSendEmail($existSendTemplate->to, $existSendTemplate->template, $data),
            default => Log::channel("debug")
                ->error(
                    self::ERROR_CLASS . "::sendToSendService ERROR SEND TYPE",
                    [
                        'DATA' => $data,
                        'TEMPLATE_ID' => $existSendTemplate->send_template_id
                    ]
                ),
        };
    }

    protected static function prepareAndSendEmail(string $to, string $template, object $data): void
    {
        $replacements = [
            '{{inn}}' => '',
            '{{kpp}}' => '',
            '{{bank_acc}}' => '',
            '{{transit}}' => $data->incident_object ?? ''
        ];

        $mapping = [
            'ИНН' => '{{inn}}',
            'КПП' => '{{kpp}}',
            'Банковский счет' => '{{bank_acc}}'
        ];

        $incidentObjects = json_decode($data->incident_object_alias, true);

        if (is_array($incidentObjects)) {
            foreach ($incidentObjects as $item) {
                if (isset($mapping[$item['key']])) {
                    $replacements[$mapping[$item['key']]] = $item['value'];
                }
            }
        }

        $template = str_replace(array_keys($replacements), array_values($replacements), $template);
        static::sendIncidentMessage($to, $template);
    }

    /**
     * SendIncidentMessage - отправляет сообщение об инциденте
     *
     * @param string $recipient - кому отправлять
     * @param string $template - текст сообщения
     * @return void
     * @throws GuzzleException
     */
    private static function sendIncidentMessage(string $recipient, string $template): void
    {
        $emails = str_contains($recipient, ',')
            ? array_map('trim', explode(',', $recipient))
            : [$recipient];

        $cleanedMessage = [];
        foreach ($emails as $email) {
            $cleanedMessage[] = [
                "to" => $email,
                "subject" => "Уведомление",
                "body" => $template,
                "isHTML" => true
            ];
        }

        $token = static::generateMailToken($cleanedMessage);
        try {
            $client = new \GuzzleHttp\Client();
            $result = $client->post(config('app.ws_messages_url') . "/api/v1/send_mail", [
                'headers' => ['Content-type' => 'application/json'],
                'json' => [
                    "token" => $token,
                    "another_registration_service" => "ws-pg",
                    "messages" => $cleanedMessage
                ]
            ]);

            Log::channel('debug')->info(static::ERROR_CLASS . '::sendeMessages RESPONSE', [$result]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            Log::channel("debug")->error(self::ERROR_CLASS . "::sendIncidentMessage \ClientException FROM SEND SERVICE", [$e->getMessage()]);
        } catch (\Exception $e) {
            Log::channel("debug")->error(self::ERROR_CLASS . "::sendIncidentMessage \Exception" . $e->getMessage());
        }
    }

    /**
     * sendIncidentMessage - отправляет сообщение об инциденте
     *
     * @param array $messages
     * @return string
     */
    protected static function generateMailToken(array $messages): string
    {
        $messages = json_encode($messages);
        $key = config('app.ws_pg_key');

        return hash('sha256', $key . $messages . $key, false);
    }

    /*
     * telegramSendMessage - отправляет сообщение в телеграм
     *
     * @param string $message
     * @return void
     */
    public static function telegramSendMessage(string $class, string $title, string $text, array $additionalInfo = []): void
    {
        $lineBreak = "\n";
        $bold = ['*', '*'];
        $code = ['```json', '```'];
        $dateTime = "$bold[0][=== " . date('H:i:s d-m-Y') . " ===]$bold[1]" . $lineBreak . $lineBreak;
        $appName = config('app.name') ?? ".env is not filled";

        $msgTitle = "$bold[0]Address: $bold[1]" . $appName . $lineBreak . "$bold[0]ERROR CLASS:$bold[1] $class" . $lineBreak . $lineBreak;

        $response = "$bold[0]Error INFO: $bold[1]$lineBreak";
        $response .= empty($text) ? "MESSAGE IS EMPTY" : $code[0] . "$lineBreak$text$lineBreak" . $code[1];

        $additionalInfoString = "$bold[0]Additional INFO:$bold[1]$lineBreak";
        $additionalInfoString .= $code[0] . " " . json_encode($additionalInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . $code[1];

        $preparedMessage = $dateTime . $msgTitle . "$bold[0]$title$bold[1]" . $lineBreak . $lineBreak . $response . $lineBreak . $additionalInfoString;

        try {
            Telegram::sendMessage([
                'chat_id' => config('app.chat_id'),
                'text' => $preparedMessage,
                'parse_mode' => 'Markdown',
            ]);

            Log::channel('telegramLogging')->error(self::ERROR_CLASS . "::telegramSendMessage SUCCESS SEND", [$preparedMessage]);
        } catch (\Exception $e) {
            Log::channel('telegramLogging')->error(self::ERROR_CLASS . "::telegramSendMessage ERROR", [$e->getMessage()]);
        }
    }
}
