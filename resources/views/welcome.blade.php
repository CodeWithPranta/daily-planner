<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="#6366f1" />
    <link rel="manifest" href="/manifest.webmanifest" />
    <title>{{ config('app.name', 'Daily Planner') }} — {{ __('Plan. Track. Achieve.') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icon-512.png">
    <link rel="apple-touch-icon" href="/icon-192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="/icon-512.png">
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
<body class="min-h-screen bg-white antialiased dark:bg-neutral-950 dark:text-zinc-100">
    <div class="relative mx-auto min-h-screen w-full max-w-md overflow-hidden">

        {{-- Decorative gradient blob --}}
        <div class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-brand-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute top-1/3 -left-24 size-64 rounded-full bg-violet-500/15 blur-3xl"></div>

        {{-- Top bar --}}
        <header class="relative z-10 flex items-center justify-between px-5 pt-safe pb-4">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white shadow-lg shadow-brand-600/30">
                    <svg viewBox="0 0 40 42" class="size-6 fill-current">
                        <rect x="6" y="3" width="28" height="36" rx="6" fill="currentColor" opacity="0.15"/>
                        <rect x="6" y="3" width="28" height="36" rx="6" stroke="currentColor" stroke-width="2.2" fill="none"/>
                        <path d="M14 3.5c0-1.38 1.12-2.5 2.5-2.5h7c1.38 0 2.5 1.12 2.5 2.5v3c0 1.38-1.12 2.5-2.5 2.5h-7C15.12 9 14 7.88 14 6.5v-3z" fill="currentColor"/>
                        <path d="M13 20l4 4 8-9" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        <path d="M13 30l4 4 8-9" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity="0.4"/>
                    </svg>
                </span>
                <span class="text-lg font-bold tracking-tight">{{ config('app.name', 'Daily Planner') }}</span>
            </div>
            <a href="{{ route('login') }}" wire:navigate class="rounded-full border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 active:scale-95 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                {{ __('Log in') }}
            </a>
        </header>

        {{-- Hero --}}
        <section class="relative z-10 px-5 pt-6 text-center">
            <h1 class="text-3xl font-bold tracking-tight">
                {{ __('Plan your day.') }}<br>
                <span class="bg-gradient-to-r from-brand-500 to-violet-500 bg-clip-text text-transparent">{{ __('Crush your goals.') }}</span>
            </h1>
            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Track tasks, time your sessions, review your day, and watch your productivity score climb.') }}
            </p>
        </section>

        {{-- Feature cards --}}
        <section class="relative z-10 mt-7 grid grid-cols-2 gap-3 px-5">
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-zinc-800/80 dark:bg-zinc-900">
                <div class="flex size-10 items-center justify-center rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <p class="mt-3 text-sm font-semibold">{{ __('Task Planning') }}</p>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Organize daily tasks with priorities') }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-zinc-800/80 dark:bg-zinc-900">
                <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <p class="mt-3 text-sm font-semibold">{{ __('Time Tracking') }}</p>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Timer sessions for deep work') }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-zinc-800/80 dark:bg-zinc-900">
                <div class="flex size-10 items-center justify-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125 9 7l5 5 7-7M21 13.125V21H3v-7.875" /></svg>
                </div>
                <p class="mt-3 text-sm font-semibold">{{ __('Analytics') }}</p>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Charts and weekly reports') }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-zinc-800/80 dark:bg-zinc-900">
                <div class="flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-2.25a3.75 3.75 0 0 1 2.83-3.63l3.01-.5Zm3.91-5.37a6 6 0 0 1-3.91 5.37l3.01-.5a3.75 3.75 0 0 1 2.83 2.84v2.88a6 6 0 0 0 3.91-10.59ZM12 2a6 6 0 0 0-3.91 10.59l3.01-.5a3.75 3.75 0 0 0 2.83 2.84v2.88a6 6 0 0 1-2.83-11.41Z" /></svg>
                </div>
                <p class="mt-3 text-sm font-semibold">{{ __('Goal Tracking') }}</p>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Long-term goals with progress') }}</p>
            </div>
        </section>

        {{-- Mock score ring preview --}}
        @auth
        <section class="relative z-10 mt-6 px-5">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center justify-center gap-2 rounded-2xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/30 transition active:scale-95">
                {{ __('Go to Dashboard') }}
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </a>
        </section>
        @else
        <section class="relative z-10 mt-6 flex justify-center px-5">
            <div class="flex items-center gap-4 rounded-3xl border border-zinc-200/80 bg-white p-5 dark:border-zinc-800/80 dark:bg-zinc-900">
                <div class="relative size-20">
                    <svg width="80" height="80" viewBox="0 0 80 80" class="-rotate-90">
                        <circle cx="40" cy="40" r="33" stroke="currentColor" stroke-width="7" class="text-zinc-200 dark:text-zinc-800" fill="none" />
                        <circle cx="40" cy="40" r="33" stroke="currentColor" stroke-width="7" class="text-brand-500" fill="none" stroke-linecap="round" stroke-dasharray="207" stroke-dashoffset="52" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-lg font-bold">75%</span>
                    </div>
                </div>
                <div class="flex flex-col gap-1 text-sm">
                    <span class="font-semibold">{{ __('Productivity Score') }}</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('See your daily score') }}</span>
                    <div class="flex gap-1">
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">5 {{ __('done') }}</span>
                        <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">2 {{ __('active') }}</span>
                    </div>
                </div>
            </div>
        </section>
        @endauth

        {{-- Dynamic CTA --}}
        @auth
        <section class="relative z-10 mt-6 px-5"
                 x-data="{
                    prompt: null,
                    showInstall: false,
                    init() {
                        if (window.__pwaInstall) { this.prompt = window.__pwaInstall; this.showInstall = true; }
                        window.addEventListener('pwa-install-ready', () => { this.prompt = window.__pwaInstall; this.showInstall = true; });
                    },
                    doInstall() {
                        if (this.prompt) {
                            this.prompt.prompt();
                            this.prompt.userChoice.then(() => { this.showInstall = false; window.__pwaInstall = null; });
                        }
                    }
                 }"
                 x-init="init()">
            <div x-show="showInstall" x-transition class="rounded-3xl bg-gradient-to-br from-brand-600 via-violet-600 to-brand-700 p-5 text-center shadow-xl shadow-brand-600/20">
                <p class="text-sm font-semibold text-white">{{ __('Install Daily Planner') }}</p>
                <p class="mb-3 text-[11px] text-white/70">{{ __('Add to your home screen for a full app experience.') }}</p>
                <button type="button"
                    @click="if ($data.deferredPrompt) { $data.deferredPrompt.prompt(); $data.deferredPrompt.userChoice.then(() => { $data.showInstall = false; }); }"
                    class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-semibold text-brand-700 shadow-lg transition active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    {{ __('Install App') }}
                </button>
            </div>
        </section>
        @else
        <section class="relative z-10 mt-7 px-5">
            <div class="rounded-3xl bg-gradient-to-br from-brand-600 via-violet-600 to-brand-700 p-6 text-center shadow-xl shadow-brand-600/20">
                {{-- Sparkle icon - simple pulse only --}}
                <div class="mb-3 flex justify-center">
                    <div class="flex size-11 items-center justify-center rounded-full bg-white/20 animate-pulse">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                        </svg>
                    </div>
                </div>
                <h2 class="text-xl font-bold text-white">{{ __('Get started — it\'s free!') }}</h2>
                <p class="mt-1 text-sm text-white/70">{{ __('Join today and start planning your productive life.') }}</p>

                {{-- Buttons --}}
                <div class="mt-5 flex flex-col gap-2.5">
                    <a href="{{ route('register') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold text-brand-700 shadow-lg transition hover:bg-zinc-100 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a3.375 3.375 0 0 1 3.375-3.375h1.5a3.375 3.375 0 0 1 3.375 3.375v.11" /></svg>
                        {{ __('Create Free Account') }}
                    </a>
                    <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-full border border-white/30 bg-white/10 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-white/20 active:scale-95">
                        {{ __('Already have an account? Log in') }}
                    </a>
                </div>
            </div>
        </section>
        @endauth

        {{-- Install App on your device --}}
        <section
            class="relative z-10 mt-5 px-5"
            x-data="{
                prompt: null,
                isStandalone: false,
                showHint: false,

                platform: 'unknown',
                browser: 'unknown',
                canInstall: false,

                init() {
                    this.detectDevice();

                    this.isStandalone =
                        window.matchMedia('(display-mode: standalone)').matches ||
                        window.navigator.standalone === true;

                    // Check if install prompt was already captured
                    if (window.__pwaInstall) {
                        this.prompt = window.__pwaInstall;
                        this.canInstall = true;
                    }

                    window.addEventListener('pwa-install-ready', () => {
                        this.prompt = window.__pwaInstall;
                        this.canInstall = true;
                    });

                    // If app gets installed
                    window.addEventListener('appinstalled', () => {
                        this.isStandalone = true;
                        this.prompt = null;
                        this.canInstall = false;
                        this.showHint = false;
                        window.__pwaInstall = null;
                    });
                },

                detectDevice() {
                    const ua = navigator.userAgent;

                    // Detect platform
                    const isIPad =
                        /iPad/.test(ua) ||
                        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

                    if (/iPhone|iPod/.test(ua) || isIPad) {
                        this.platform = 'ios';
                    } else if (/Android/.test(ua)) {
                        this.platform = 'android';
                    } else {
                        this.platform = 'desktop';
                    }

                    // Detect browser
                    if (this.platform === 'ios') {
                        if (/CriOS/.test(ua)) {
                            this.browser = 'chrome';
                        } else if (/FxiOS/.test(ua)) {
                            this.browser = 'firefox';
                        } else if (/EdgiOS/.test(ua)) {
                            this.browser = 'edge';
                        } else if (/Safari/.test(ua)) {
                            this.browser = 'safari';
                        }
                    } else {
                        if (/Edg\//.test(ua)) {
                            this.browser = 'edge';
                        } else if (/SamsungBrowser/.test(ua)) {
                            this.browser = 'samsung';
                        } else if (/OPR\//.test(ua)) {
                            this.browser = 'opera';
                        } else if (/Firefox\//.test(ua)) {
                            this.browser = 'firefox';
                        } else if (/Chrome\//.test(ua)) {
                            this.browser = 'chrome';
                        } else if (/Safari/.test(ua)) {
                            this.browser = 'safari';
                        }
                    }
                },

                install() {
                    // Native browser install prompt
                    if (this.prompt) {
                        this.prompt.prompt();

                        this.prompt.userChoice.then((choice) => {
                            if (choice.outcome === 'accepted') {
                                this.showHint = false;
                            }

                            this.prompt = null;
                            this.canInstall = false;
                            window.__pwaInstall = null;
                        });

                        return;
                    }

                    // Show device/browser-specific instructions
                    this.showHint = true;
                },

                get browserName() {
                    const names = {
                        chrome: 'Google Chrome',
                        safari: 'Safari',
                        edge: 'Microsoft Edge',
                        samsung: 'Samsung Internet',
                        firefox: 'Firefox',
                        opera: 'Opera',
                        unknown: 'your browser'
                    };

                    return names[this.browser] || 'your browser';
                }
            }"
            x-init="init()"
        >
            {{-- Install Button --}}
            <div
                x-show="!isStandalone"
                x-transition
                class="flex items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 dark:border-zinc-800/80 dark:bg-zinc-900"
            >
                <div class="flex size-10 flex-none items-center justify-center rounded-xl bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="size-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 16.5V3m0 13.5L7.5 12M12 16.5l4.5-4.5M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5"
                        />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold">
                        {{ __('Install on your device') }}
                    </p>

                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                        {{ __('Get a faster full-screen app experience') }}
                    </p>
                </div>

                <button
                    type="button"
                    @click="install()"
                    class="flex-none rounded-full bg-brand-600 px-4 py-2 text-xs font-semibold text-white shadow-lg shadow-brand-600/20 transition active:scale-95"
                >
                    {{ __('Install') }}
                </button>
            </div>


            {{-- Installation Guide --}}
            <div
                x-show="showHint && !isStandalone"
                x-transition
                class="mt-3 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-zinc-800/80 dark:bg-zinc-900"
            >

                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                    <div>
                        <p class="text-sm font-semibold">
                            {{ __('How to install') }}
                        </p>

                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                            <span x-text="browserName"></span>
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="showHint = false"
                        class="rounded-lg p-1 text-zinc-400 transition hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="size-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>


                <div class="space-y-4 p-4">


                    {{-- ========================= --}}
                    {{-- iOS Safari --}}
                    {{-- ========================= --}}

                    <template x-if="platform === 'ios' && browser === 'safari'">
                        <div class="space-y-4">

                            <div class="flex gap-3">
                                <span class="flex size-7 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                    1
                                </span>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ __('Tap the Share button') }}
                                    </p>

                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ __('You can find it in the Safari toolbar') }}
                                    </p>
                                </div>
                            </div>


                            <div class="border-t border-zinc-100 dark:border-zinc-800"></div>


                            <div class="flex gap-3">
                                <span class="flex size-7 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                    2
                                </span>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ __('Select "Add to Home Screen"') }}
                                    </p>

                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ __('Scroll through the share menu if needed') }}
                                    </p>
                                </div>
                            </div>


                            <div class="border-t border-zinc-100 dark:border-zinc-800"></div>


                            <div class="flex gap-3">
                                <span class="flex size-7 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                    3
                                </span>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ __('Tap "Add"') }}
                                    </p>

                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ __('The app will appear on your Home Screen') }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </template>


                    {{-- ========================= --}}
                    {{-- iOS Other Browsers --}}
                    {{-- ========================= --}}

                    <template x-if="platform === 'ios' && browser !== 'safari'">
                        <div class="space-y-3">

                            <div class="rounded-xl bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                {{ __('For the best installation experience on iPhone or iPad, open this website in Safari.') }}
                            </div>

                            <div class="flex gap-3">
                                <span class="flex size-7 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                    1
                                </span>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ __('Open this page in Safari') }}
                                    </p>

                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ __('Use the browser menu and choose "Open in Safari" if available') }}
                                    </p>
                                </div>
                            </div>


                            <div class="border-t border-zinc-100 dark:border-zinc-800"></div>


                            <div class="flex gap-3">
                                <span class="flex size-7 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                    2
                                </span>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ __('Tap the Share button') }}
                                    </p>

                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ __('Then choose "Add to Home Screen"') }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </template>


                    {{-- ========================= --}}
                    {{-- Android --}}
                    {{-- ========================= --}}

                    <template x-if="platform === 'android'">
                        <div class="space-y-4">

                            <div class="flex gap-3">
                                <span class="flex size-7 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                    1
                                </span>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ __('Open the browser menu') }}
                                    </p>

                                    <p
                                        class="text-[11px] text-zinc-500 dark:text-zinc-400"
                                        x-text="
                                            browser === 'chrome'
                                                ? 'Tap the three dots (⋮) in Google Chrome.'
                                                : browser === 'samsung'
                                                    ? 'Tap the menu button in Samsung Internet.'
                                                    : 'Open your browser menu.'
                                        "
                                    ></p>
                                </div>
                            </div>


                            <div class="border-t border-zinc-100 dark:border-zinc-800"></div>


                            <div class="flex gap-3">
                                <span class="flex size-7 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                    2
                                </span>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ __('Tap "Install app" or "Add to Home screen"') }}
                                    </p>

                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ __('The option name may be different depending on your browser') }}
                                    </p>
                                </div>
                            </div>


                            <div class="border-t border-zinc-100 dark:border-zinc-800"></div>


                            <div class="flex gap-3">
                                <span class="flex size-7 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                    3
                                </span>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ __('Confirm installation') }}
                                    </p>

                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ __('The app icon will appear on your device') }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </template>


                    {{-- ========================= --}}
                    {{-- Desktop --}}
                    {{-- ========================= --}}

                    <template x-if="platform === 'desktop'">
                        <div class="space-y-4">

                            <div class="flex gap-3">
                                <span class="flex size-7 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                    1
                                </span>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ __('Look for the Install icon') }}
                                    </p>

                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ __('It may appear in the browser address bar') }}
                                    </p>
                                </div>
                            </div>


                            <div class="border-t border-zinc-100 dark:border-zinc-800"></div>


                            <div class="flex gap-3">
                                <span class="flex size-7 flex-none items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">
                                    2
                                </span>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ __('Click "Install"') }}
                                    </p>

                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ __('Confirm to install the app on your computer') }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </template>

                </div>
            </div>


            {{-- Already Installed --}}
            <div
                x-show="isStandalone"
                x-transition
                class="flex items-center gap-3 rounded-2xl border border-emerald-300/50 bg-emerald-50 p-4 dark:bg-emerald-500/10"
            >
                <div class="flex size-10 flex-none items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="size-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                        />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                        {{ __('App installed!') }}
                    </p>

                    <p class="text-[11px] text-emerald-600 dark:text-emerald-500/80">
                        {{ __('You are using the installed version.') }}
                    </p>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="relative z-10 mt-8 px-5 pb-safe text-center">
            <p class="text-[11px] text-zinc-400 dark:text-zinc-600">{{ config('app.name', 'Daily Planner') }} — {{ __('Plan. Track. Achieve.') }}</p>
        </footer>

    </div>

    @fluxScripts
</body>
</html>