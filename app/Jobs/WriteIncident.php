<?php

namespace App\Jobs;

use App\Helpers\Parsers\Parser;
use App\Models\Incident;
use App\Models\IncidentType;
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
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        \Illuminate\Support\Facades\Log::channel('debug')->info('Write incident to database', $this->data);
        [$this->data['code'], $this->data['message']] = Parser::parseStr($this->data['message']);
        $existType = IncidentType::where('code', $this->data['code'])->first();

        $isJobOver = match (true) {
            $existType === null => Incident::saveData($this->data),            // Сохраняем, если тип инцидента не найден
            default => Incident::processIncidentData($this->data, $existType), // Обновляем, если тип инцидента найден
        };

        if (!$isJobOver['success']) {
            \Illuminate\Support\Facades\Log::channel('debug')->warning('Error write incident to database', $isJobOver);
            $this->release(
                pow(2, $this->attempts())
            );
        } else {
            \Illuminate\Support\Facades\Log::channel('debug')->info('Incident successfully processed', [
                'code' => $this->data['code'],
                'result' => $isJobOver
            ]);
        }
    }
}
