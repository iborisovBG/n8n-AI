<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendAdScriptToN8n;
use App\Models\AdScriptTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendAdScriptToN8nTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that job sends request to n8n successfully.
     */
    public function test_job_sends_request_to_n8n_successfully(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        config(['services.n8n.webhook_url' => 'https://n8n.example.com/webhook']);
        config(['services.n8n.api_key' => 'test-api-key']);

        $task = AdScriptTask::factory()->create();

        $job = new SendAdScriptToN8n($task);
        $job->handle();

        Http::assertSent(function ($request) use ($task) {
            return $request->url() === 'https://n8n.example.com/webhook'
                && $request->hasHeader('X-N8N-API-KEY', 'test-api-key')
                && $request['task_id'] === $task->id
                && $request['reference_script'] === $task->reference_script
                && $request['outcome_description'] === $task->outcome_description;
        });
    }

    /**
     * Test that job fails when n8n URL is not configured.
     */
    public function test_job_fails_when_n8n_url_not_configured(): void
    {
        config(['services.n8n.webhook_url' => null]);

        $task = AdScriptTask::factory()->create();

        $job = new SendAdScriptToN8n($task);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('N8N webhook URL is not configured');

        $job->handle();
    }

    /**
     * Test that job updates task status to failed on error.
     */
    public function test_job_updates_task_status_on_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        config(['services.n8n.webhook_url' => 'https://n8n.example.com/webhook']);
        config(['services.n8n.api_key' => 'test-api-key']);

        $task = AdScriptTask::factory()->create([
            'status' => 'pending',
        ]);

        $job = new SendAdScriptToN8n($task);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected to throw
        }

        $task->refresh();

        $this->assertEquals('failed', $task->status);
        $this->assertNotNull($task->error_details);
    }

    /**
     * Test that job handles timeout gracefully.
     */
    public function test_job_handles_timeout(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
        });

        config(['services.n8n.webhook_url' => 'https://n8n.example.com/webhook']);
        config(['services.n8n.api_key' => 'test-api-key']);

        $task = AdScriptTask::factory()->create([
            'status' => 'pending',
        ]);

        $job = new SendAdScriptToN8n($task);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected to throw
        }

        $task->refresh();

        $this->assertEquals('failed', $task->status);
        $this->assertNotNull($task->error_details);
    }
}
