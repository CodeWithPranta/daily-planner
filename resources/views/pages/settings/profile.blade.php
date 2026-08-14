<?php

use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] #[Layout('components.app-shell', ['active' => 'profile'])] class extends Component
{
    use ProfileValidationRules, WithFileUploads;

    public string $name = '';

    public string $email = '';

    public ?string $gender = null;

    public ?string $birth_date = null;

    /** @var TemporaryUploadedFile|null */
    public $avatar = null;

    public bool $removeAvatar = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->gender = $user->gender;
        $this->birth_date = $user->birth_date?->format('Y-m-d');
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate(array_merge(
            $this->profileRules($user->id),
            ['avatar' => ['nullable', 'image', 'max:2048']],
        ));

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'gender' => $validated['gender'],
            'birth_date' => $validated['birth_date'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($this->removeAvatar && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $this->deletePublicFallback($user->avatar_path);
            $user->avatar_path = null;
            $this->removeAvatar = false;
        }

        if ($this->avatar) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
                $this->deletePublicFallback($user->avatar_path);
            }
            $path = $this->avatar->store('avatars', 'public');
            $this->copyToPublicFallback($path);
            $user->avatar_path = $path;
            $this->avatar = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }

    /**
     * Copy uploaded file to public/ as fallback when symlink is missing (cPanel).
     */
    private function copyToPublicFallback(string $path): void
    {
        if (is_link(public_path('storage'))) {
            return;
        }

        $source = Storage::disk('public')->path($path);
        $dest = public_path($path);
        $dir = dirname($dest);

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if (file_exists($source)) {
            @copy($source, $dest);
        }
    }

    /**
     * Delete fallback file from public/ (cPanel).
     */
    private function deletePublicFallback(string $path): void
    {
        if (is_link(public_path('storage'))) {
            return;
        }

        $dest = public_path($path);

        if (file_exists($dest)) {
            @unlink($dest);
        }
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your profile and account settings')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-5">
            {{-- Avatar upload --}}
            <div class="flex flex-col items-center gap-3">
                @php $avatarUrl = Auth::user()->avatar_url; @endphp
                <div class="relative">
                    @if ($avatar)
                        <img src="{{ $avatar->temporaryUrl() }}" alt="{{ __('Preview') }}" class="size-24 rounded-full object-cover border-4 border-brand-400 dark:border-brand-500" />
                    @elseif ($avatarUrl && ! $removeAvatar)
                        <img src="{{ $avatarUrl }}" alt="{{ Auth::user()->name }}" class="size-24 rounded-full object-cover border-4 border-zinc-200 dark:border-zinc-700" />
                    @else
                        <div class="flex size-24 items-center justify-center rounded-full bg-brand-600 text-2xl font-bold text-white">
                            {{ strtoupper(Auth::user()->initials()) }}
                        </div>
                    @endif
                    <div wire:loading wire:target="avatar" class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50">
                        <flux:icon name="arrow-path" class="size-6 animate-spin text-white" />
                    </div>
                </div>
                <div class="flex gap-2">
                    <label class="cursor-pointer rounded-full border border-zinc-200 px-3 py-1.5 text-xs font-medium transition hover:bg-zinc-100 active:scale-95 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        {{ __('Upload photo') }}
                        <input type="file" wire:model="avatar" accept="image/*" class="hidden" />
                    </label>
                    @if ($avatar)
                        <button type="button" wire:click="$set('avatar', null)" class="rounded-full border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50 active:scale-95 dark:border-rose-700 dark:text-rose-400 dark:hover:bg-rose-500/10">
                            {{ __('Cancel') }}
                        </button>
                    @elseif ($avatarUrl)
                        <button type="button" wire:click="$set('removeAvatar', true)" class="rounded-full border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50 active:scale-95 dark:border-rose-700 dark:text-rose-400 dark:hover:bg-rose-500/10">
                            {{ __('Remove') }}
                        </button>
                    @endif
                </div>
                @error('avatar')
                    <p class="text-xs text-rose-500">{{ $message }}</p>
                @enderror
                @if ($removeAvatar)
                    <p class="text-xs text-zinc-500">{{ __('Photo will be removed on save.') }}</p>
                @endif
                @if ($avatar)
                    <p class="text-xs text-brand-600 dark:text-brand-400">{{ __('Preview — click Save to confirm.') }}</p>
                @endif
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <flux:select wire:model="gender" :label="__('Gender')">
                <option value="">{{ __('Prefer not to say') }}</option>
                <option value="male">{{ __('Male') }}</option>
                <option value="female">{{ __('Female') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </flux:select>

            <flux:input wire:model="birth_date" :label="__('Birth date')" type="date" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>