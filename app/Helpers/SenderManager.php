<?php

namespace App\Helpers;

use App\Models\IncidentType;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class SenderManager
{

    /**
     * preparePushOrMail - отправляет сообщение об инциденте на сервис рассылки
     *
     * @param \App\Models\Incident $data
     * @return void
     */
    public static function preparePushOrMail($data)
    {
        $getSendType = IncidentType::where('id', $data->incident_type_id)->first();
        if (!$getSendType) {
            Log::channel("debug")->error("SENDERMANAGER::sendToSendService ERROR SEND MAIL TO SENDER SERVICE", [$data]);
            return;
        }

        $sendType = $getSendType->send_template_id;
        $template = $getSendType->sendTemplate->template;
        $recipient = $getSendType->sendTemplate->to;;

        match ($sendType) {
            1 => self::sendIncidentMessage($recipient, $template),
            // "telegram" => self::sendTelegram($data),
            default => Log::channel("debug")->error("SENDERMANAGER::sendToSendService ERROR SEND TYPE", [$data]),
        };
    }

    /**
     * SendIncidentMessage - отправляет сообщение об инциденте
     *
     * @param string $recipient - кому отправлять
     * @param string $template - текст сообщения
     * @return void
     */
    private static function sendIncidentMessage(string $recipient, string $template)
    {
        $emails = strpos($recipient, ',') !== false
            ? array_map('trim', explode(',', $recipient))
            : [$recipient];

        $cleanedMessage = [];
        foreach ($emails as $email) {
            $cleanedMessage[] = [
                "to" => $email,
                "subject" => "Уведомление",
                "body" => $template,
                "isHTML" => false
            ];
        }

        $token = self::generateMailToken($cleanedMessage);
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post("http://78.24.216.215:513/api/v1/send_mail", [
                'headers' => ['Content-type' => 'application/json'],
                'json' => [
                    "token" => $token,
                    "another_registration_service" => "ws-pg",
                    "messages" => $cleanedMessage
                ]
            ]);

            $return = json_decode($response->getBody()->getContents(), true);
            Log::channel("debug")->info("SEND INCIDENT MESSAGE RETURN", [$return]);
        } catch (\Exception $e) {
            Log::channel("debug")->error("Ошибка при отправке сообщения: " . $e->getMessage());
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

        return hash('sha256', $key . $messages . $key, false);;
    }

    /*
     * telegramSendMessage - отправляет сообщение в телеграм
     *
     * @param string $message
     * @return void
     */
    public static function telegramSendMessage(string $class, string $message): void
    {
        $message = "<b>" . "APP: " . config('app.name') . "</b>\n<b>FROM</b>: <code>$class</code>\n" . $message;

        try {
            Telegram::sendMessage([
                'chat_id' => config('app.chat_id'),
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
        } catch (\Exception $e) {
            Log::channel('telegramLogging')->error("ServiceManager::telegramSendMessage ERROR", [$e->getMessage()]);
        }
    }
}
