<?php

use App\Models\AdScriptTask;
use Livewire\Volt\Component;

new class extends Component {
    public AdScriptTask $task;

    public function layout(): string
    {
        return 'components.layouts.app';
    }

    public function title(): string
    {
        return __('Task Details');
    }

    public function mount(int $id): void
    {
        $this->task = AdScriptTask::findOrFail($id);
    }

    public function refresh(): void
    {
        $this->task->refresh();

        // Stop polling if task is no longer pending
        if ($this->task->status !== 'pending') {
            $this->dispatch('stop-polling');
        }
    }

    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'neutral',
        };
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Task Details') }}</flux:heading>
            <flux:subheading>{{ __('View and manage your advertising script task') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" href="{{ route('ad-scripts.index') }}" wire:navigate>
                {{ __('Back to List') }}
            </flux:button>
            @if($task->status === 'pending')
                <flux:button variant="ghost" wire:click="refresh" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Refresh') }}</span>
                    <span wire:loading>{{ __('Refreshing...') }}</span>
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Status Badge -->
    <div class="flex items-center gap-3">
        <flux:badge :variant="$this->getStatusColor($task->status)" size="lg">
            {{ ucfirst($task->status) }}
        </flux:badge>
        <span class="text-sm text-neutral-500 dark:text-neutral-400">
            {{ __('Created') }} {{ $task->created_at->format('M d, Y H:i') }}
            @if($task->status === 'completed' && $task->created_at && $task->updated_at)
                • {{ __('Processed in') }} {{ $task->created_at->diffInSeconds($task->updated_at) }}s
            @endif
        </span>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Reference Script -->
        <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-xs dark:border-neutral-700 dark:bg-neutral-900">
            <flux:heading size="sm" class="mb-4">{{ __('Reference Script') }}</flux:heading>
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <p class="whitespace-pre-wrap text-sm">{{ $task->reference_script }}</p>
            </div>
        </div>

        <!-- Outcome Description -->
        <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-xs dark:border-neutral-700 dark:bg-neutral-900">
            <flux:heading size="sm" class="mb-4">{{ __('Desired Outcome') }}</flux:heading>
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <p class="whitespace-pre-wrap text-sm">{{ $task->outcome_description }}</p>
            </div>
        </div>
    </div>

    @if($task->status === 'completed')
        <!-- Results -->
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-xs dark:border-neutral-700 dark:bg-neutral-900">
                <flux:heading size="sm" class="mb-4">{{ __('Improved Script') }}</flux:heading>
                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <p class="whitespace-pre-wrap text-sm">{{ $task->new_script }}</p>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-xs dark:border-neutral-700 dark:bg-neutral-900">
                <flux:heading size="sm" class="mb-4">{{ __('Analysis') }}</flux:heading>
                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <p class="whitespace-pre-wrap text-sm">{{ $task->analysis }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($task->status === 'failed')
        <!-- Error Details -->
        <flux:callout variant="danger">
            <flux:heading size="sm" class="mb-2">{{ __('Error Details') }}</flux:heading>
            <p class="text-sm">{{ $task->error_details }}</p>
        </flux:callout>
    @endif

    @if($task->status === 'pending')
        <!-- Auto-refresh for pending tasks -->
        <flux:callout variant="neutral">
            {{ __('This task is being processed. The page will automatically refresh when completed.') }}
        </flux:callout>

        <div
            wire:poll.5s="refresh"
            wire:poll.keep-alive
        ></div>
    @endif
</div>
