<?php

namespace App\Helpers;

use App\Models\IncidentType;

class SenderManager
{
    public static function preparePushOrMail($data)
    {
        $getSendType = IncidentType::where('id', $data->incident_type_id)->first();
        if (!$getSendType) {
            \Illuminate\Support\Facades\Log::channel("debug")->error("SENDERMANAGER::sendToSendService ERROR SEND MAIL TO SENDER SERVICE", [$data]);
            return;
        }

        $sendType = $getSendType->send_template_id;
        $template = $getSendType->sendTemplate->template;
        $recipient = $getSendType->sendTemplate->to;;

        match ($sendType) {
            1 => self::sendIncidentMessage($recipient, $template),
                // "telegram" => self::sendTelegram($data),
            default => \Illuminate\Support\Facades\Log::channel("debug")->error("SENDERMANAGER::sendToSendService ERROR SEND TYPE", [$data]),
        };
    }

    protected static function generateMailToken(array $messages): string
    {
        $messages = json_encode($messages);

        $key = config('app.ws_pg_key');
        $sign = hash('sha256', $key . $messages . $key, false);

        return $sign;
    }


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
            \Illuminate\Support\Facades\Log::channel("debug")->info("SEND INCIDENT MESSAGE RETURN", [$return]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel("debug")->error("Ошибка при отправке сообщения: " . $e->getMessage());
        }
    }
}
