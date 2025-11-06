<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Service;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class ServicesChart extends ChartWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 2;

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return __('filament.most_used_services');
    }

    public ?string $filter = null;

    public function mount(): void
    {
        parent::mount();
        
        // Set default filter to current month
        if (!$this->filter) {
            $user = auth()->user();
            $userTimezone = $user->timezone ?? 'Africa/Cairo';
            $this->filter = Carbon::now($userTimezone)->format('Y-m');
        }
    }

    protected function getFilters(): ?array
    {
        $user = auth()->user();
        $userTimezone = $user->timezone ?? 'Africa/Cairo';
        $now = Carbon::now($userTimezone);
        
        // Generate options for the last 12 months
        $filters = [];
        for ($i = 0; $i < 12; $i++) {
            $date = $now->copy()->subMonths($i);
            $key = $date->format('Y-m');
            $label = $date->format('F Y');
            $filters[$key] = $label;
        }
        
        return $filters;
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $userTimezone = $user->timezone ?? 'Africa/Cairo';
        
        // Use filter if set, otherwise use current month
        if ($this->filter) {
            $selectedDate = Carbon::createFromFormat('Y-m', $this->filter, $userTimezone);
        } else {
            $selectedDate = Carbon::now($userTimezone);
        }
        
        // Get selected month start and end in UTC
        $monthStart = $selectedDate->copy()->startOfMonth()->utc();
        $monthEnd = $selectedDate->copy()->endOfMonth()->utc();

        // Get appointments for current month grouped by service
        $appointments = Appointment::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->whereBetween('date_time', [$monthStart, $monthEnd])
            ->with('service')
            ->get();

        // Group by service and count
        $serviceCounts = $appointments->groupBy('service_id')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take(5); // Top 5 services

        // Get service names
        $serviceIds = $serviceCounts->keys();
        $services = Service::whereIn('id', $serviceIds)
            ->get()
            ->keyBy('id');

        // Prepare chart data
        $labels = [];
        $data = [];

        foreach ($serviceCounts as $serviceId => $count) {
            $service = $services->get($serviceId);
            if ($service) {
                $labels[] = $service->name;
                $data[] = $count;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => __('filament.appointments'),
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)', // blue
                        'rgba(16, 185, 129, 0.5)', // green
                        'rgba(245, 158, 11, 0.5)', // yellow
                        'rgba(239, 68, 68, 0.5)',  // red
                        'rgba(139, 92, 246, 0.5)', // purple
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(139, 92, 246)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}

