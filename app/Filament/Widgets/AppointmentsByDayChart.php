<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class AppointmentsByDayChart extends ChartWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 3;

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return __('filament.appointments_by_day');
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

        // Get appointments for selected month
        $appointments = Appointment::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->whereBetween('date_time', [$monthStart, $monthEnd])
            ->get();

        // Initialize day counts (using day of week numbers)
        $dayCounts = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];

        // Group appointments by day of week
        foreach ($appointments as $appointment) {
            $appointmentDate = Carbon::parse($appointment->date_time, 'UTC')
                ->setTimezone($userTimezone);
            $dayOfWeek = $appointmentDate->dayOfWeek; // 0 = Sunday, 6 = Saturday
            $dayCounts[$dayOfWeek]++;
        }

        // Prepare chart data with translated day names
        $dayNames = [
            0 => __('filament.sunday'),
            1 => __('filament.monday'),
            2 => __('filament.tuesday'),
            3 => __('filament.wednesday'),
            4 => __('filament.thursday'),
            5 => __('filament.friday'),
            6 => __('filament.saturday'),
        ];
        
        $labels = array_values($dayNames);
        $data = array_values($dayCounts);

        return [
            'datasets' => [
                [
                    'label' => __('filament.appointments'),
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

