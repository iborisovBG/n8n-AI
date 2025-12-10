<?php

namespace Tests\Unit;

use App\Events\AdScriptTaskFailed;
use App\Listeners\SendSlackNotificationOnTaskFailure;
use App\Models\AdScriptTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendSlackNotificationOnTaskFailureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test listener formats Slack message correctly.
     */
    public function test_listener_formats_slack_message_correctly(): void
    {
        $task = AdScriptTask::factory()->failed()->create([
            'reference_script' => 'Test script content',
            'outcome_description' => 'Test outcome description',
            'error_details' => 'Test error message',
        ]);

        $event = new AdScriptTaskFailed($task, 'Test error message');
        $listener = new SendSlackNotificationOnTaskFailure;

        $reflection = new \ReflectionClass($listener);
        $method = $reflection->getMethod('formatSlackMessage');
        $method->setAccessible(true);

        $message = $method->invoke($listener, $event);

        $this->assertArrayHasKey('text', $message);
        $this->assertArrayHasKey('blocks', $message);
        $this->assertEquals('🚨 Ad Script Task Failed', $message['text']);
        $this->assertIsArray($message['blocks']);
        $this->assertCount(5, $message['blocks']); // header, section (fields), section (script), section (outcome), actions
    }

    /**
     * Test listener includes task ID in message.
     */
    public function test_listener_includes_task_id_in_message(): void
    {
        $task = AdScriptTask::factory()->failed()->create();
        $event = new AdScriptTaskFailed($task, 'Error message');

        $listener = new SendSlackNotificationOnTaskFailure;
        $reflection = new \ReflectionClass($listener);
        $method = $reflection->getMethod('formatSlackMessage');
        $method->setAccessible(true);

        $message = $method->invoke($listener, $event);

        $taskIdField = $message['blocks'][1]['fields'][0];
        $this->assertStringContainsString((string) $task->id, $taskIdField['text']);
    }

    /**
     * Test listener includes error message in Slack message.
     */
    public function test_listener_includes_error_message(): void
    {
        $task = AdScriptTask::factory()->failed()->create();
        $errorMessage = 'Custom error message';
        $event = new AdScriptTaskFailed($task, $errorMessage);

        $listener = new SendSlackNotificationOnTaskFailure;
        $reflection = new \ReflectionClass($listener);
        $method = $reflection->getMethod('formatSlackMessage');
        $method->setAccessible(true);

        $message = $method->invoke($listener, $event);

        $errorField = $message['blocks'][1]['fields'][3];
        $this->assertStringContainsString($errorMessage, $errorField['text']);
    }

    /**
     * Test listener truncates long scripts.
     */
    public function test_listener_truncates_long_scripts(): void
    {
        $longScript = str_repeat('A', 500);
        $task = AdScriptTask::factory()->failed()->create([
            'reference_script' => $longScript,
        ]);

        $event = new AdScriptTaskFailed($task, 'Error');
        $listener = new SendSlackNotificationOnTaskFailure;

        $reflection = new \ReflectionClass($listener);
        $method = $reflection->getMethod('formatSlackMessage');
        $method->setAccessible(true);

        $message = $method->invoke($listener, $event);

        $scriptSection = $message['blocks'][2]['text']['text'];
        // Slack adds "*Reference Script:*\n" prefix, so we account for that
        $this->assertLessThanOrEqual(250, strlen($scriptSection)); // 200 limit + prefix overhead
    }

    /**
     * Test listener sends notification when webhook URL is configured.
     */
    public function test_listener_sends_notification_when_webhook_configured(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        Config::set('services.slack.webhook_url', 'https://hooks.slack.com/test');

        $task = AdScriptTask::factory()->failed()->create();
        $event = new AdScriptTaskFailed($task, 'Test error');

        $listener = new SendSlackNotificationOnTaskFailure;
        $listener->handle($event);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.slack.com/test' &&
                   $request->hasHeader('Content-Type', 'application/json');
        });
    }

    /**
     * Test listener skips notification when webhook URL is not configured.
     */
    public function test_listener_skips_notification_when_webhook_not_configured(): void
    {
        Log::shouldReceive('debug')
            ->once()
            ->with('Slack webhook URL not configured, skipping notification');

        Config::set('services.slack.webhook_url', null);

        $task = AdScriptTask::factory()->failed()->create();
        $event = new AdScriptTaskFailed($task, 'Test error');

        $listener = new SendSlackNotificationOnTaskFailure;
        $listener->handle($event);

        Http::assertNothingSent();
    }

    /**
     * Test listener handles HTTP errors gracefully.
     */
    public function test_listener_handles_http_errors_gracefully(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Bad Request'], 400),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send Slack notification', \Mockery::type('array'));

        Config::set('services.slack.webhook_url', 'https://hooks.slack.com/test');

        $task = AdScriptTask::factory()->failed()->create();
        $event = new AdScriptTaskFailed($task, 'Test error');

        $listener = new SendSlackNotificationOnTaskFailure;
        $listener->handle($event);

        // Should not throw exception
        $this->assertTrue(true);
    }
}
