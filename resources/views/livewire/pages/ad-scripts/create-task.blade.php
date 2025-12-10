<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Create New Task') }}</flux:heading>
        <flux:subheading>{{ __('Submit an advertising script for AI-powered refactoring') }}</flux:subheading>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
        <form wire:submit="create" class="flex flex-col gap-6">
            <!-- Reference Script -->
            <flux:field>
                <flux:label>{{ __('Reference Script') }}</flux:label>
                <flux:textarea
                    wire:model="reference_script"
                    placeholder="{{ __('Enter the original advertising script that needs to be improved...') }}"
                    rows="8"
                    required
                />
                <flux:error name="reference_script" />
                <flux:description>{{ __('The original script that you want to improve') }}</flux:description>
            </flux:field>

            <!-- Outcome Description -->
            <flux:field>
                <flux:label>{{ __('Desired Outcome') }}</flux:label>
                <flux:textarea
                    wire:model="outcome_description"
                    placeholder="{{ __('Describe what you want to achieve: tone, target audience, length, style, etc.') }}"
                    rows="4"
                    required
                />
                <flux:error name="outcome_description" />
                <flux:description>{{ __('Describe the desired improvements: tone, target audience, length, style, etc.') }}</flux:description>
            </flux:field>

            @error('form')
                <flux:callout variant="danger">
                    {{ $message }}
                </flux:callout>
            @enderror

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <flux:button variant="ghost" href="{{ route('ad-scripts.index') }}" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Create Task') }}</span>
                    <span wire:loading>{{ __('Creating...') }}</span>
                </flux:button>
            </div>
        </form>
    </div>
</div>
