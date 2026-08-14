<div x-data="{ showReason: $wire.status === 'partially_completed' || $wire.status === 'skipped' }" x-effect="showReason = $wire.status === 'partially_completed' || $wire.status === 'skipped'">
    {{-- Back link --}}
    <a href="{{ route('today') }}" wire:navigate class="mb-4 inline-flex items-center gap-1 text-sm text-zinc-500 transition hover:text-brand-600 dark:text-zinc-400">
        <flux:icon name="arrow-left" class="size-4" />
        {{ __('Back to Today') }}
    </a>

    {{-- Task header --}}
    @php
        $taskStatus = $task->status;
        $plannedH = intdiv($task->planned_minutes, 60);
        $plannedM = $task->planned_minutes % 60;
        $actualH = intdiv($task->actual_minutes, 60);
        $actualM = $task->actual_minutes % 60;
    @endphp
    <x-card padding="false" class="overflow-hidden">
        <div class="p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-bold tracking-tight">{{ $task->title }}</h1>
                    @if ($task->description)
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $task->description }}</p>
                    @endif
                </div>
                <span class="flex-none rounded-full px-3 py-1 text-xs font-medium {{ $task->badgeClass() }}">{{ $taskStatus->label() }}</span>
            </div>

            {{-- Meta row --}}
            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                @if ($task->category)
                    <span class="inline-flex items-center gap-1 rounded-lg bg-zinc-100 px-2.5 py-1 dark:bg-zinc-800">
                        <span class="size-2.5 rounded-full" style="background-color: {{ $task->category->color }}"></span>
                        {{ $task->category->name }}
                    </span>
                @endif
                @if ($task->goal)
                    <span class="inline-flex items-center gap-1 rounded-lg bg-zinc-100 px-2.5 py-1 dark:bg-zinc-800">
                        <flux:icon name="rocket-launch" class="size-3.5" />
                        {{ $task->goal->title }}
                    </span>
                @endif
                <span class="inline-flex items-center gap-1 rounded-lg bg-zinc-100 px-2.5 py-1 dark:bg-zinc-800">
                    <flux:icon name="clock" class="size-3.5" />
                    @if($plannedH) {{ $plannedH }}h @endif @if($plannedM) {{ $plannedM }}m @endif {{ __('planned') }}
                </span>
                <span class="inline-flex items-center gap-1 rounded-lg {{ $task->priorityBadge() }} px-2.5 py-1">
                    {{ $task->priority->label() }}
                </span>
            </div>

            {{-- Progress bar --}}
            <div class="mt-4">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Progress') }}</span>
                    <span class="font-semibold">{{ $task->progress_percentage }}%</span>
                </div>
                <div class="mt-1 h-2.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                    <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $task->progress_percentage }}%"></div>
                </div>
            </div>

            {{-- Time summary --}}
            <div class="mt-3 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                <span>{{ __('Actual time') }}: @if($actualH) {{ $actualH }}h @endif @if($actualM) {{ $actualM }}m @endif</span>
                <span>{{ __('Logged via') }} {{ $updates->count() }} {{ __('updates') }}</span>
            </div>
        </div>
    </x-card>

    {{-- Action buttons --}}
    @if (! $taskStatus->isFinished())
        <div class="mt-4 grid grid-cols-2 gap-3">
            @if ($hasActiveTimer)
                <div class="col-span-2 flex items-center justify-center gap-2 rounded-2xl bg-emerald-50 p-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <flux:icon name="signal" class="size-4" />
                    {{ __('Timer running for this task') }}
                </div>
            @else
                <button type="button" wire:click="startTimer" class="col-span-2 inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 p-3 text-sm font-medium text-white shadow-sm transition active:scale-95">
                    <flux:icon name="play" class="size-5" variant="solid" />
                    {{ __('Start Timer') }}
                </button>
            @endif
            <button type="button" wire:click="openUpdateForm" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-zinc-200 bg-white p-3 text-sm font-medium transition active:scale-95 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:icon name="arrow-up-circle" class="size-5 text-brand-600 dark:text-brand-400" />
                {{ __('Log Progress') }}
            </button>
            <button type="button" wire:click="completeTask" wire:confirm="{{ __('Mark this task as completed?') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-zinc-200 bg-white p-3 text-sm font-medium transition active:scale-95 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:icon name="check-circle" class="size-5 text-emerald-500" />
                {{ __('Complete') }}
            </button>
            <button type="button" wire:click="skipTask" wire:confirm="{{ __('Skip this task?') }}" class="col-span-2 inline-flex items-center justify-center gap-2 rounded-2xl border border-zinc-200 bg-white p-3 text-sm font-medium text-zinc-600 transition active:scale-95 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                <flux:icon name="forward" class="size-5 text-zinc-400" />
                {{ __('Skip Task') }}
            </button>
        </div>
    @endif

    {{-- Progress update form --}}
    @if ($showUpdateForm)
        <x-card padding="false" class="mt-4 overflow-hidden">
            <form wire:submit="saveProgress" class="space-y-4 p-5">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Log Progress') }}</flux:heading>
                    <button type="button" wire:click="$set('showUpdateForm', false)" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('Close') }}">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <flux:textarea wire:model="notes" :label="__('What did you do?')" placeholder="{{ __('Describe what you accomplished...') }}" />

                <div class="grid gap-1.5">
                    <flux:label>{{ __('Progress') }}: <span class="font-bold">{{ $progress }}%</span></flux:label>
                    <input type="range" min="0" max="100" step="5" wire:model.live="progress" class="w-full accent-brand-600" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model="worked_hours" :label="__('Hours')" type="number" min="0" max="16" />
                    <flux:input wire:model="worked_minutes" :label="__('Minutes')" type="number" min="0" max="59" />
                </div>

                <flux:select wire:model="status" :label="__('Status')">
                    <option value="in_progress">{{ __('In Progress') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                    <option value="partially_completed">{{ __('Partially Completed') }}</option>
                    <option value="skipped">{{ __('Skipped') }}</option>
                </flux:select>

                @if (in_array($status, ['partially_completed', 'skipped'], true))
                    <flux:textarea wire:model="reason" :label="__('Why was it not completed?')" placeholder="{{ __('What got in the way?') }}" />
                @endif

                <div class="flex justify-end gap-2 pt-1">
                    <flux:button type="button" variant="ghost" wire:click="$set('showUpdateForm', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </x-card>
    @endif

    {{-- Progress history --}}
    <div class="mt-5 mb-2"><h2 class="text-base font-semibold">{{ __('Progress History') }}</h2></div>
    @if ($updates->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="clock" class="size-8 text-zinc-300 dark:text-zinc-700" />
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No progress logged yet.') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($updates as $update)
                <x-card padding="false" class="overflow-hidden" wire:key="upd-{{ $update->id }}">
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-medium {{ \App\Concerns\HasColorBadge::badgeClasses($update->status->color()) }}">{{ $update->status->label() }}</span>
                            <span class="text-xs text-zinc-400">{{ $update->created_at->translatedFormat('g:i A') }}</span>
                        </div>
                        <div class="mt-2 flex items-center gap-4 text-sm">
                            <span class="font-medium">{{ $update->progress_percentage }}%</span>
                            <span class="text-zinc-500 dark:text-zinc-400">
                                @if(intdiv($update->worked_minutes, 60)) {{ intdiv($update->worked_minutes, 60) }}h @endif @if($update->worked_minutes % 60) {{ $update->worked_minutes % 60 }}m @endif {{ __('worked') }}
                            </span>
                        </div>
                        @if ($update->notes)
                            <p class="mt-2 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-301">{{ $update->notes }}</p>
                        @endif
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</div>