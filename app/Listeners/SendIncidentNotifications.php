<?php

namespace App\Listeners;

use App\Events\IncidentCreated;
use App\Events\IncidentUpdatedAfterLifecycle;
use App\Helpers\SenderManager;
use App\Values\TelegramSendData;
use Illuminate\Support\Facades\Log;

class SendIncidentNotifications
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(IncidentCreated|IncidentUpdatedAfterLifecycle $event): void
    {
        Log::channel('debug')->info("Try to send incident}", [$event->incidentType->alias]);
        if (!empty($event->incidentType->alias)) {
            SenderManager::preparePushOrMail($event->incident, $event->incidentType);
        }

        if($event->incident->count > 1) {
            SenderManager::telegramSendMessage(new TelegramSendData(
                __CLASS__,
                "ОШИБКА ОБНОВИЛАСЬ ДЛЯ ({$event->incident->hash_sum})",
                (string)$event->incident->incident_text,
                [
                    'SERVICE AND SOURCE' => $event->incident->service . '|' . $event->incident->source,
                    'count' => $event->incident->count,
                ]
            ));
        }

    }
}
