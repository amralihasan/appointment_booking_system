<?php

namespace App\Filament\Owner\Widgets;

use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class TenantStatsChart extends ChartWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 2;

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return __('filament.appointments_per_tenant');
    }

    public ?string $filter = 'appointments';

    protected function getFilters(): ?array
    {
        return [
            'appointments' => __('filament.appointments'),
            'users' => __('filament.users'),
        ];
    }

    protected function getData(): array
    {
        $tenants = Tenant::withCount(['appointments', 'users'])->get();

        if ($this->filter === 'users') {
            $labels = $tenants->pluck('name')->toArray();
            $data = $tenants->pluck('users_count')->toArray();
            $label = __('filament.users');
        } else {
            $labels = $tenants->pluck('name')->toArray();
            $data = $tenants->pluck('appointments_count')->toArray();
            $label = __('filament.appointments');
        }

        return [
            'datasets' => [
                [
                    'label' => $label,
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
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
                    'display' => true,
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

