<?php

namespace App\Listeners;

use App\Events\AdScriptTaskFailed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSlackNotificationOnTaskFailure
{
    /**
     * Handle the event.
     */
    public function handle(AdScriptTaskFailed $event): void
    {
        $webhookUrl = config('services.slack.webhook_url');

        if (! $webhookUrl) {
            Log::debug('Slack webhook URL not configured, skipping notification');
            return;
        }

        try {
            $message = $this->formatSlackMessage($event);

            $response = Http::timeout(10)
                ->post($webhookUrl, $message);

            if (! $response->successful()) {
                Log::error('Failed to send Slack notification', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } else {
                Log::info('Slack notification sent successfully', [
                    'task_id' => $event->task->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Slack notification', [
                'error' => $e->getMessage(),
                'task_id' => $event->task->id,
            ]);
        }
    }

    /**
     * Format the Slack message.
     */
    protected function formatSlackMessage(AdScriptTaskFailed $event): array
    {
        $task = $event->task;
        $referenceScript = $task->reference_script;
        $outcomeDescription = $task->outcome_description;

        // Truncate long scripts to fit Slack's message limits
        if (strlen($referenceScript) > 200) {
            $referenceScript = substr($referenceScript, 0, 197).'...';
        }

        if (strlen($outcomeDescription) > 200) {
            $outcomeDescription = substr($outcomeDescription, 0, 197).'...';
        }

        return [
            'text' => '🚨 Ad Script Task Failed',
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '🚨 Ad Script Task Failed',
                    ],
                ],
                [
                    'type' => 'section',
                    'fields' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Task ID:*\n{$task->id}",
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Status:*\n{$task->status}",
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Created:*\n".$task->created_at->format('Y-m-d H:i:s'),
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => "*Error:*\n{$event->errorMessage}",
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Reference Script:*\n{$referenceScript}",
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Outcome Description:*\n{$outcomeDescription}",
                    ],
                ],
                [
                    'type' => 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'View Task',
                            ],
                            'url' => config('app.url')."/ad-scripts/{$task->id}",
                        ],
                    ],
                ],
            ],
        ];
    }
}

