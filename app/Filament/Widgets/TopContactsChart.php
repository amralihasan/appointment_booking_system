<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Contact;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class TopContactsChart extends ChartWidget
{
    protected static ?string $heading = 'Top Contacts by Bookings';

    protected static ?int $sort = 4;

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

        // Get appointments for selected month grouped by contact
        $appointments = Appointment::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->whereNotNull('contact_id')
            ->whereBetween('date_time', [$monthStart, $monthEnd])
            ->with('contact')
            ->get();

        // Group by contact and count
        $contactCounts = $appointments->groupBy('contact_id')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take(5); // Top 5 contacts

        // Get contact names
        $contactIds = $contactCounts->keys();
        $contacts = Contact::whereIn('id', $contactIds)
            ->get()
            ->keyBy('id');

        // Prepare chart data
        $labels = [];
        $data = [];

        foreach ($contactCounts as $contactId => $count) {
            $contact = $contacts->get($contactId);
            if ($contact) {
                $name = trim($contact->first_name . ' ' . $contact->last_name);
                if (empty($name)) {
                    $name = $contact->mobile;
                }
                $labels[] = $name;
                $data[] = $count;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
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

