<?php

namespace App\Livewire\Analytics;

use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.app-shell', ['active' => 'analytics'])]
#[Title('Analytics')]
class Index extends Component
{
    public string $range = 'week';

    public function updatedRange(): void
    {
        // Triggers re-render with new data
    }

    public function render(): View
    {
        $userId = (int) Auth::id();
        $service = app(AnalyticsService::class);

        $data = $service->getAnalytics($userId, $this->range);

        return view('livewire.analytics.index', [
            'data' => $data,
        ]);
    }
}
