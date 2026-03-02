<?php

namespace App\Jobs;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WriteIncident implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const CLASS_NAME = __CLASS__;
    public int $tries = 16;
    protected array $data;

    /**
     * Create a new job instance.
     *
     * @param array $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Handle the job - обрабатывает инцидент согласно принципу Information Expert
     *
     * @return void
     */
    public function handle(): void
    {
        $incident = Incident::fromArray($this->data);
        $isJobOver = $incident->process();

        if (!$isJobOver['success']) {
            Log::channel('debug')->warning('Error write incident to database', $isJobOver);
            $this->release(
                pow(2, $this->attempts())
            );
        } else {
            Log::channel('debug')->info('Incident successfully processed', [$isJobOver]);
        }
    }
}
