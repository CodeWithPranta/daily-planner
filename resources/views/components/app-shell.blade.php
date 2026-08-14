@props([
    'title' => null,
    'active' => null,
    'wide' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
        <meta name="theme-color" content="#6366f1" />
        <link rel="manifest" href="/manifest.webmanifest" />

        <title>
            {{ filled($title) ? $title.' - '.config('app.name', 'Daily Planner') : config('app.name', 'Daily Planner') }}
        </title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/icon-512.png">
        <link rel="apple-touch-icon" href="/icon-192.png" sizes="192x192">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
        <script>
            window.__pwaInstall = null;
            window.addEventListener('beforeinstallprompt', function(e) {
                e.preventDefault();
                window.__pwaInstall = e;
                window.dispatchEvent(new Event('pwa-install-ready'));
            });
        </script>
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <div class="mx-auto flex min-h-screen w-full max-w-md flex-col md:max-w-lg">
            {{-- Sticky top header --}}
            <header class="pt-safe sticky top-0 z-40 border-b border-zinc-200/80 bg-zinc-50/80 backdrop-blur-lg dark:border-zinc-800/80 dark:bg-zinc-950/80">
                <div class="flex items-center justify-between gap-3 px-4 pb-3">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2" aria-label="{{ config('app.name') }}">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white shadow-lg shadow-brand-600/30 dark:bg-brand-500">
                            <x-app-logo-icon class="size-6 fill-current" />
                        </span>
                        <span class="flex flex-col leading-tight">
                            <span class="text-sm font-semibold">{{ config('app.name') }}</span>
                            @isset($title)
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $title }}</span>
                            @endisset
                        </span>
                    </a>

                    <flux:dropdown position="bottom" align="end">
                        <button type="button" class="flex items-center gap-2 rounded-full p-1 pr-2 transition hover:bg-zinc-200/60 dark:hover:bg-zinc-800/60" aria-label="{{ __('Account menu') }}">
                            @if (auth()->user()->avatar_url)
                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="size-9 rounded-full object-cover" />
                            @else
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                    class="size-9 text-xs"
                                />
                            @endif
                        </button>

                        <flux:menu class="min-w-56">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                @if (auth()->user()->avatar_url)
                                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="size-10 rounded-full object-cover" />
                                @else
                                    <flux:avatar
                                        :name="auth()->user()->name"
                                        :initials="auth()->user()->initials()"
                                    />
                                @endif
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate text-xs">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                            <flux:menu.separator />
                            <flux:menu.radio.group>
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                    {{ __('Profile & Settings') }}
                                </flux:menu.item>
                                <flux:menu.item :href="route('appearance.edit')" icon="swatch" wire:navigate>
                                    {{ __('Appearance') }}
                                </flux:menu.item>
                            </flux:menu.radio.group>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item
                                    as="button"
                                    type="submit"
                                    icon="arrow-right-start-on-rectangle"
                                    class="w-full cursor-pointer"
                                    data-test="logout-button"
                                >
                                    {{ __('Log out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </header>

            {{-- Scrollable main content --}}
            <main class="@if($wide) px-0 @else px-4 @endif flex-1 pb-nav py-4">
                {{ $slot }}
            </main>

            {{-- Bottom navigation --}}
            <x-bottom-nav :active="$active" />
        </div>

        {{-- Global timer bar --}}
        <livewire:timer.bar :key="'timer-bar-'.uniqid()" />

        {{-- PWA install banner --}}
        <div x-data="{
                show: false,
                prompt: null,
                init() {
                    const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                    if (standalone) return;
                    if (window.__pwaInstall) { this.prompt = window.__pwaInstall; this.show = true; }
                    window.addEventListener('pwa-install-ready', () => { this.prompt = window.__pwaInstall; this.show = true; });
                },
                install() {
                    if (this.prompt) {
                        this.prompt.prompt();
                        this.prompt.userChoice.then(() => { this.show = false; window.__pwaInstall = null; });
                    }
                }
             }"
             x-init="init()"
             x-show="show" x-transition
             class="fixed inset-x-0 bottom-0 z-50 mx-auto w-full max-w-md md:max-w-lg"
             style="bottom: calc(env(safe-area-inset-bottom, 0px) + 5rem);">
            <div class="mx-2 flex items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-lg dark:border-zinc-800/80 dark:bg-zinc-900">
                <div class="flex size-9 flex-none items-center justify-center rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    <flux:icon name="arrow-down-tray" class="size-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold">{{ __('Install App') }}</p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Add to your device') }}</p>
                </div>
                <button type="button" @click="install()" class="flex-none rounded-full bg-brand-600 px-4 py-1.5 text-xs font-semibold text-white shadow-lg shadow-brand-600/20 transition active:scale-95">
                    {{ __('Install') }}
                </button>
                <button type="button" @click="show = false" class="flex-none rounded-lg p-1.5 text-zinc-400 transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('Dismiss') }}">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>