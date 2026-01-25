<?php

namespace App\Services\IncidentNotifications\Senders;

use App\Helpers\SenderManager;
use App\Jobs\SendMail;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Services\IncidentNotifications\Contracts\IncidentSenderInterface;
use App\Services\TemplateServiceFactory;
use App\Values\TelegramSendData;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EmailSender implements IncidentSenderInterface
{

    public function __construct(
        private readonly TemplateServiceFactory $templateServiceFactory
    ) {}

    public function send(Incident $incident, IncidentType $incidentType): void
    {
        Log::channel('debug')->info(static::CLASS_NAME . "::prepareAndSendEmail is WORK", [$incident]);
        $to = $incidentType->SendTemplate->to;
        try {
            $preparer = $this->templateServiceFactory->create($incident->class);
            $processedTemplate = $preparer->prepare($incident, $incidentType->sendTemplate->template);

            if ($processedTemplate['success']) {
                SendMail::dispatch([
                    "to" => json_decode($to, true),
                    "template" => $processedTemplate['data']
                ])->onQueue('sendMailJob');
            }
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
            Log::channel("debug")->error(static::CLASS_NAME . '::prepareAndSendEmail EXCEPTION PREPARE EMAIL', [$error]);
            SenderManager::telegramSendMessage(new TelegramSendData(
                static::CLASS_NAME,
                "EXCEPTION PREPARE EMAIL",
                $error,
                [
                    "data" => $incident,
                    "to" => $to
                ]
            ));
        }
    }

}
