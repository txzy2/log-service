<?php

namespace App\Events;

use App\Models\Incident;
use App\Models\IncidentType;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Incident $incident,
        public IncidentType $incidentType
    ) {}

}
