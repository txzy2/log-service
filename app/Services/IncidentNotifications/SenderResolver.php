<?php

namespace App\Services\IncidentNotifications;

use App\Enums\SendTemplateType;
use App\Services\IncidentNotifications\Contracts\IncidentSenderInterface;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SenderResolver
{
    public static function resolve(?SendTemplateType $alias): ?IncidentSenderInterface
    {
        Log::channel('debug')->info("Incident senderResolver resolve()", [$alias]);
        $serviceName = "\\App\\Services\\IncidentNotifications\\Senders\\{$alias->value}Sender";
        if (!class_exists($serviceName)) {
            Log::channel("debug")->error("SenderResolver::resolve ERROR GET CLASS", ['serviceName' => $serviceName]);
            throw new InvalidArgumentException("$serviceName is not a valid service name");
        }
        return app($serviceName);
    }
}
