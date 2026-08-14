@php /** @var \App\Models\TimerSession|null $session */ @endphp
<div wire:poll.1s="refreshElapsed"
     x-data="{ timerOn: {{ $session ? 'true' : 'false' }} }"
     x-effect="
         const main = document.querySelector('main');
         if (main) {
             main.classList.toggle('pb-timer', timerOn);
             main.classList.toggle('pb-nav', !timerOn);
         }
     "
>
@if ($session)
    <div
        class="fixed inset-x-0 z-30 mx-auto w-full max-w-md md:max-w-lg"
        style="bottom: calc(env(safe-area-inset-bottom, 0px) + 5rem);"
    >
        <div class="mx-2 rounded-2xl border border-zinc-200/80 bg-white shadow-lg dark:border-zinc-800/80 dark:bg-zinc-900">
            @if ($showStopForm)
                {{-- Stop form --}}
                <div class="p-4">
                    <div class="flex items-center justify-between pb-3">
                        <flux:heading size="sm">{{ __('Stop Timer') }}</flux:heading>
                        <button type="button" wire:click="$set('showStopForm', false)" class="rounded-lg p-1 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <flux:icon name="x-mark" class="size-4" />
                        </button>
                    </div>
                    <div class="mb-2 text-sm text-zinc-600 dark:text-zinc-300">
                        {{ $session->task->title }} · <span class="font-mono font-semibold">{{ $elapsed }}</span>
                    </div>
                    <form wire:submit="stopTimer" class="space-y-3">
                        <flux:textarea wire:model="stopNotes" :label="__('Notes')" placeholder="{{ __('What did you work on?') }}" />
                        <div class="grid gap-1.5">
                            <flux:label>{{ __('Progress') }}: <span class="font-bold">{{ $stopProgress }}%</span></flux:label>
                            <input type="range" min="0" max="100" step="5" wire:model.live="stopProgress" class="w-full accent-brand-600" />
                        </div>
                        <flux:button type="submit" variant="primary" class="w-full">{{ __('Stop & Log') }}</flux:button>
                    </form>
                </div>
            @else
                {{-- Timer bar --}}
                <div class="flex items-center gap-3 p-3">
                    <span class="flex size-10 flex-none items-center justify-center rounded-xl @if($session->status === \App\Enums\TimerStatus::Running) bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400 @else bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400 @endif">
                        @if($session->status === \App\Enums\TimerStatus::Running)
                            <span class="relative flex size-3">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-500 opacity-75"></span>
                                <span class="relative inline-flex size-3 rounded-full bg-emerald-500"></span>
                            </span>
                        @else
                            <flux:icon name="pause" variant="solid" class="size-5" />
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ $session->task->title }}</p>
                        <p class="font-mono text-lg font-bold tabular-nums">{{ $elapsed }}</p>
                    </div>
                    <div class="flex flex-none items-center gap-1">
                        @if($session->status === \App\Enums\TimerStatus::Running)
                            <button type="button" wire:click="pause" wire:loading.attr="disabled" class="rounded-lg p-2.5 text-amber-600 transition hover:bg-amber-50 dark:hover:bg-amber-500/10" aria-label="{{ __('Pause') }}">
                                <flux:icon name="pause" class="size-5" variant="solid" />
                                <span wire:loading role="status" class="sr-only">{{ __('Pausing...') }}</span>
                            </button>
                        @else
                            <button type="button" wire:click="resume" wire:loading.attr="disabled" class="rounded-lg p-2.5 text-emerald-600 transition hover:bg-emerald-50 dark:hover:bg-emerald-500/10" aria-label="{{ __('Resume') }}">
                                <flux:icon name="play" class="size-5" variant="solid" />
                                <span wire:loading role="status" class="sr-only">{{ __('Resuming...') }}</span>
                            </button>
                        @endif
                        <button type="button" wire:click="openStopForm" class="rounded-lg p-2.5 text-rose-500 transition hover:bg-rose-50 dark:hover:bg-rose-500/10" aria-label="{{ __('Stop') }}">
                            <flux:icon name="stop" class="size-5" variant="solid" />
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
</div>