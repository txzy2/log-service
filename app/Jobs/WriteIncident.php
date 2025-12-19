<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Models\IncidentType;
use App\Values\IncidentData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WriteIncident implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CLASS_NAME = __CLASS__;
    protected $data;
    public $tries = 16;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(IncidentData $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        [$code, $message] = $this->data->parseCodeAndMessage();
        $existType = IncidentType::where('code', $code)->first();
        $this->data->setNewMessage($message);

        $isJobOver = match (true) {
            $existType === null => Incident::saveData($this->data), // Сохраняем, если тип инцидента не найден
            default => Incident::processIncidentData($this->data, $existType), // Обновляем, если тип инцидента найден
        };

        if (!$isJobOver['success']) {
            \Illuminate\Support\Facades\Log::channel('debug')->warning('Error write incident to database', $isJobOver);
            $this->release(
                pow(2, $this->attempts())
            );
        } else {
            \Illuminate\Support\Facades\Log::channel('debug')->info('Incident successfully processed', [
                'code'   => $code,
                'result' => $isJobOver,
            ]);
        }
    }
}
