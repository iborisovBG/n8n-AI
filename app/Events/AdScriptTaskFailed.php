<?php

namespace App\Events;

use App\Models\AdScriptTask;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdScriptTaskFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public AdScriptTask $task,
        public string $errorMessage
    ) {}
}
