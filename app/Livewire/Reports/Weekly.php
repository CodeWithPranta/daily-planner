<?php

namespace App\Livewire\Reports;

use App\Services\ReportService;
use App\Services\StreakService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.app-shell', ['active' => 'analytics'])]
#[Title('Weekly Report')]
class Weekly extends Component
{
    public ?int $weekOffset = null;

    public function mount(?int $weekOffset = null): void
    {
        $this->weekOffset = $weekOffset;
    }

    public function render(): View
    {
        $userId = (int) Auth::id();
        $referenceDate = $this->weekOffset !== null
            ? Carbon::today()->subWeeks($this->weekOffset)
            : Carbon::today();

        $reportService = app(ReportService::class);
        $streakService = app(StreakService::class);

        $summary = $reportService->weeklySummary($userId, $referenceDate);
        $currentStreak = $streakService->currentStreak($userId);
        $longestStreak = $streakService->longestStreak($userId);

        return view('livewire.reports.weekly', [
            'summary' => $summary,
            'currentStreak' => $currentStreak,
            'longestStreak' => $longestStreak,
            'trend' => $reportService->trendData($summary->scores),
        ]);
    }
}
