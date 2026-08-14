<?php

namespace App\Livewire\Timer;

use App\Enums\TimerStatus;
use App\Models\TimerSession;
use App\Services\TimerService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Bar extends Component
{
    public ?int $sessionId = null;

    public string $elapsed = '00:00:00';

    public bool $showStopForm = false;

    public string $stopNotes = '';

    public int $stopProgress = 0;

    public function mount(): void
    {
        $this->loadActiveTimer();
    }

    #[On('timer-started')]
    #[On('timer-stopped')]
    public function loadActiveTimer(): void
    {
        $session = app(TimerService::class)->getActiveForUser((int) Auth::id());

        if ($session) {
            $this->sessionId = $session->id;
            $this->elapsed = $session->elapsedHuman();
            $this->stopProgress = $session->task->progress_percentage;
        } else {
            $this->sessionId = null;
            $this->elapsed = '00:00:00';
            $this->showStopForm = false;
        }
    }

    public function pause(TimerService $service): void
    {
        $session = $this->getSession();
        if ($session) {
            $service->pause($session);
            $this->dispatch('timer-updated');
            $this->loadActiveTimer();
        }
    }

    public function resume(TimerService $service): void
    {
        $session = $this->getSession();
        if ($session) {
            $service->resume($session);
            $this->dispatch('timer-updated');
            $this->loadActiveTimer();
        }
    }

    public function openStopForm(): void
    {
        $session = $this->getSession();
        if ($session) {
            $this->stopProgress = $session->task->progress_percentage;
            $this->showStopForm = true;
        }
    }

    public function stopTimer(TimerService $service): void
    {
        $session = $this->getSession();
        if (! $session) {
            return;
        }

        $validated = $this->validate([
            'stopNotes' => ['nullable', 'string', 'max:2000'],
            'stopProgress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $service->stop($session, $validated['stopNotes'], $validated['stopProgress']);

        $this->showStopForm = false;
        $this->sessionId = null;
        $this->elapsed = '00:00:00';
        $this->stopNotes = '';
        $this->stopProgress = 0;

        $this->dispatch('timer-stopped');
        Flux::toast(text: __('Timer stopped. Progress logged.'), variant: 'success');
    }

    public function refreshElapsed(): void
    {
        $session = $this->getSession();
        if ($session && $session->status !== TimerStatus::Stopped) {
            $this->elapsed = $session->elapsedHuman();
        }
    }

    protected function getSession(): ?TimerSession
    {
        return $this->sessionId
            ? TimerSession::with('task.category')->find($this->sessionId)
            : null;
    }

    public function render(): View
    {
        $session = $this->getSession();

        return view('livewire.timer.bar', [
            'session' => $session,
        ]);
    }
}
