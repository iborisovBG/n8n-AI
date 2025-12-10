<?php

use App\Models\AdScriptTask;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function tasks()
    {
        $query = AdScriptTask::query()
            ->orderBy('created_at', 'desc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reference_script', 'like', "%{$this->search}%")
                    ->orWhere('outcome_description', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query->paginate(15);
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

<div>
    <x-layouts.app :title="__('Ad Script Tasks')">
        <div class="flex h-full w-full flex-1 flex-col gap-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('Ad Script Tasks') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Manage and monitor your advertising script refactoring tasks') }}
                    </flux:subheading>
                </div>

                <flux:button variant="primary" href="{{ route('ad-scripts.create') }}" wire:navigate>
                    {{ __('Create New Task') }}
                </flux:button>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 sm:flex-row">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search scripts...') }}"
                    icon="magnifying-glass"
                    class="flex-1"
                />

                <flux:select wire:model.live="statusFilter" placeholder="{{ __('All Statuses') }}">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                    <option value="failed">{{ __('Failed') }}</option>
                </flux:select>
            </div>

            <!-- Tasks List -->
            <div class="flex flex-col gap-4">
                @php
                    $tasks = $this->tasks();
                @endphp
                @forelse ($tasks as $task)
                    <div
                        wire:key="task-{{ $task->id }}"
                        onclick="window.location.href='{{ route('ad-scripts.show', $task->id) }}'"
                        class="rounded-xl border border-neutral-200 bg-white p-6 shadow-xs dark:border-neutral-700 dark:bg-neutral-900 cursor-pointer hover:shadow-lg transition-shadow"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex-1 space-y-2">
                                <div class="flex items-center gap-3">
                                    <flux:badge :variant="$this->getStatusColor($task->status)">
                                        {{ ucfirst($task->status) }}
                                    </flux:badge>
                                    <span class="text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $task->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <p class="text-sm text-neutral-700 dark:text-neutral-300 line-clamp-2">
                                    {{ Str::limit($task->reference_script, 150) }}
                                </p>

                                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                    <strong>{{ __('Outcome:') }}</strong>
                                    {{ Str::limit($task->outcome_description, 100) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($task->status === 'completed' && $task->created_at && $task->updated_at)
                                    <span class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ __('Processed in') }} {{ $task->created_at->diffInSeconds($task->updated_at) }}s
                                    </span>
                                @endif

                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('ad-scripts.show', $task->id) }}"
                                    wire:navigate>
                                    {{ __('View') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @empty
                    <flux:callout variant="neutral">
                        {{ __('No tasks found.') }}

                        @if($search || $statusFilter)
                            <flux:link href="#" wire:click="$set('search', ''); $set('statusFilter', '')" class="ml-1">
                                {{ __('Clear filters') }}
                            </flux:link>
                        @endif
                    </flux:callout>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($tasks->hasPages())
                <div class="mt-4">
                    {{ $tasks->links() }}
                </div>
            @endif
        </div>
    </x-layouts.app>
</div>
