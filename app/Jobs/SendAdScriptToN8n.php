<?php

namespace App\Jobs;

use App\Events\AdScriptTaskFailed;
use App\Models\AdScriptTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendAdScriptToN8n implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AdScriptTask $task
    ) {
        $this->tries = config('services.n8n.retry.max_attempts', 3);
        $this->backoff = config('services.n8n.retry.delay_seconds', 5);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrl = config('services.n8n.webhook_url');
        $apiKey = config('services.n8n.api_key');

        if (! $webhookUrl) {
            $errorMessage = 'N8N webhook URL is not configured';
            Log::error($errorMessage);
            $this->markTaskAsFailed($errorMessage);
            throw new \Exception($errorMessage);
        }

        try {
            $callbackUrl = config('app.url').'/api/ad-scripts/'.$this->task->id.'/result';

            $response = Http::timeout(config('services.n8n.timeout', 120))
                ->withHeaders([
                    'X-N8N-API-KEY' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($webhookUrl, [
                    'task_id' => $this->task->id,
                    'reference_script' => $this->task->reference_script,
                    'outcome_description' => $this->task->outcome_description,
                    'callback_url' => $callbackUrl,
                ]);

            if (! $response->successful()) {
                $errorMessage = "N8N webhook returned status {$response->status()}: {$response->body()}";
                Log::error($errorMessage, [
                    'task_id' => $this->task->id,
                    'status' => $response->status(),
                ]);
                $this->markTaskAsFailed($errorMessage);
                throw new \Exception($errorMessage);
            }

            Log::info('Successfully sent ad script task to n8n', [
                'task_id' => $this->task->id,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorMessage = "Connection error: {$e->getMessage()}";
            Log::error($errorMessage, [
                'task_id' => $this->task->id,
                'exception' => $e->getMessage(),
            ]);
            $this->markTaskAsFailed($errorMessage);
            throw $e;
        } catch (\Exception $e) {
            $errorMessage = "Failed to send task to n8n: {$e->getMessage()}";
            Log::error($errorMessage, [
                'task_id' => $this->task->id,
                'exception' => $e->getMessage(),
            ]);
            $this->markTaskAsFailed($errorMessage);
            throw $e;
        }
    }

    /**
     * Mark task as failed and fire event.
     */
    protected function markTaskAsFailed(string $errorMessage): void
    {
        $this->task->update([
            'status' => 'failed',
            'error_details' => $errorMessage,
        ]);

        event(new AdScriptTaskFailed($this->task, $errorMessage));
    }
}
