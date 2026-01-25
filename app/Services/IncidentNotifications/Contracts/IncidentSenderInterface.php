<?php

namespace App\Services\IncidentNotifications\Contracts;

use App\Models\Incident;
use App\Models\IncidentType;

interface IncidentSenderInterface
{
    const CLASS_NAME = __CLASS__;
    public function send(Incident $incident, IncidentType $incidentType): void;
}
