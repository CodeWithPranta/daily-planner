<div class="space-y-5">
    {{-- Mobile settings nav (horizontal segmented) --}}
    <div class="flex gap-2 overflow-x-auto no-scrollbar">
        <flux:navlist class="flex-row gap-1" aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="route('profile.edit')" wire:navigate icon="user" :current="request()->routeIs('profile.edit')">
                {{ __('Profile') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('security.edit')" wire:navigate icon="shield-check" :current="request()->routeIs('security.edit')">
                {{ __('Security') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate icon="swatch" :current="request()->routeIs('appearance.edit')">
                {{ __('Appearance') }}
            </flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator variant="subtle" />

    <div>
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>
    </div>

    <div class="w-full">
        {{ $slot }}
    </div>
</div>