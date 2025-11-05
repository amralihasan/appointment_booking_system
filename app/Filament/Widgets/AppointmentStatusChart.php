<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class AppointmentStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Appointments by Status';

    protected static ?int $sort = 5;

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

        // Get appointments for selected month grouped by status
        $appointments = Appointment::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->whereBetween('date_time', [$monthStart, $monthEnd])
            ->get();

        // Initialize status counts
        $statusCounts = [
            'booked' => 0,
            'canceled' => 0,
            'completed' => 0,
        ];

        // Group appointments by status
        foreach ($appointments as $appointment) {
            $status = $appointment->status;
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }

        // Prepare chart data
        $labels = [
            'booked' => 'Booked',
            'canceled' => 'Canceled',
            'completed' => 'Completed',
        ];

        $data = [
            $statusCounts['booked'],
            $statusCounts['canceled'],
            $statusCounts['completed'],
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Appointments',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.5)', // green for booked
                        'rgba(239, 68, 68, 0.5)',  // red for canceled
                        'rgba(59, 130, 246, 0.5)', // blue for completed
                    ],
                    'borderColor' => [
                        'rgb(16, 185, 129)',
                        'rgb(239, 68, 68)',
                        'rgb(59, 130, 246)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => array_values($labels),
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

