<?php

namespace App\Jobs;

use App\Models\SendStatusQueue;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CLASS_NAME = __CLASS__;
    public int $tries = 3;
    protected array $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        Log::channel('debug')->info('queue send is working', []);
        $jobId = $this->job->getJobId();
        SendStatusQueue::create(["job_id" => $jobId, "status" => "started", "message" => json_encode($this->data)]);

        $emails = str_contains($this->data['to'], ',')
            ? array_map('trim', explode(',', $this->data['to']))
            : [$this->data['to']];

        $cleanedMessage = [];
        foreach ($emails as $email) {
            $cleanedMessage[] = [
                "to" => $email,
                "subject" => "Уведомление",
                "body" => $this->data['template'],
                "isHTML" => true
            ];
        }
        SendStatusQueue::where("job_id", $jobId)->update(["status" => "process", "message" => json_encode($emails)]);

        try {
            $client = new Client();
            $response = $client->post(config('app.ws_messages_url') . "/api/v1/send_mail", [
                'headers' => ['Content-type' => 'application/json'],
                'json' => [
                    "token" => $this->generateMailToken($cleanedMessage),
                    "another_registration_service" => "ws-pg",
                    "messages" => $cleanedMessage
                ]
            ]);
            $responseBody = $response->getBody()->getContents();
            $result = json_decode($responseBody, true);

            $status = (isset($result['success']) && !$result['success']) ? "failed" : "sended";
            Log::channel('debug')->info(self::CLASS_NAME . '::sendeMessages RESPONSE', [$responseBody]);
            SendStatusQueue::where("job_id", $jobId)->update(["status" => $status, "message" => $responseBody]);
        } catch (ClientException $e) {
            $error = $e->getMessage();
            Log::channel("debug")->error(self::CLASS_NAME . "::sendIncidentMessage \ClientException FROM SEND SERVICE", [$error]);
            SendStatusQueue::where("job_id", $jobId)->update(["job_id" => $jobId, "status" => "failed", "message" => "CLIENT EXCEPTION: " . $error]);
        } catch (Exception $e) {
            $error = $e->getMessage();
            Log::channel("debug")->error(self::CLASS_NAME . "::sendIncidentMessage \Exception" . $error);
            SendStatusQueue::where("job_id", $jobId)->update(["job_id" => $jobId, "status" => "failed", "message" => "CLIENT EXCEPTION: " . $error]);
        }
    }

    /**
     * generateMailToken - генерирует токен для сервиса рассылки
     *
     * @param array $messages
     * @return string
     */
    protected function generateMailToken(array $messages): string
    {
        $messages = json_encode($messages);
        $key = config('app.ws_pg_key');

        return hash('sha256', $key . $messages . $key, false);
    }
}
