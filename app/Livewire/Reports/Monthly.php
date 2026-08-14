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
#[Title('Monthly Report')]
class Monthly extends Component
{
    public ?int $monthOffset = null;

    public function mount(?int $monthOffset = null): void
    {
        $this->monthOffset = $monthOffset;
    }

    public function render(): View
    {
        $userId = (int) Auth::id();
        $referenceDate = $this->monthOffset !== null
            ? Carbon::today()->subMonths($this->monthOffset)
            : Carbon::today();

        $reportService = app(ReportService::class);
        $streakService = app(StreakService::class);

        $summary = $reportService->monthlySummary($userId, $referenceDate);
        $currentStreak = $streakService->currentStreak($userId);
        $longestStreak = $streakService->longestStreak($userId);

        return view('livewire.reports.monthly', [
            'summary' => $summary,
            'currentStreak' => $currentStreak,
            'longestStreak' => $longestStreak,
            'trend' => $reportService->trendData($summary->scores),
        ]);
    }
}
