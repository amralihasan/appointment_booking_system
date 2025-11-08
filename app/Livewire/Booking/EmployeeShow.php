<?php

namespace App\Livewire\Booking;

use App\Models\Employee;
use App\Models\Tenant;
use Livewire\Component;

class EmployeeShow extends Component
{
    public ?Tenant $tenant = null;
    public ?Employee $employee = null;
    public string $tenantSlug;
    public string $employeeSlug;

    public function mount(string $tenantSlug, string $employeeSlug)
    {
        $this->tenantSlug = $tenantSlug;
        $this->employeeSlug = $employeeSlug;

        // Load tenant and employee
        // Allow both 'active' and 'trial' status tenants
        $this->tenant = Tenant::where('slug', $tenantSlug)
            ->whereIn('status', ['active', 'trial'])
            ->firstOrFail();

        $this->employee = Employee::where('tenant_id', $this->tenant->id)
            ->where('slug', $employeeSlug)
            ->where('is_active', true)
            ->with(['services' => function ($query) {
                $query->where('is_active', true);
            }])
            ->firstOrFail();
    }

    public function selectService(int $serviceId)
    {
        // Redirect to booking page with employee context
        $service = $this->employee->services()->find($serviceId);
        if ($service) {
            return $this->redirect(route('booking.show', [
                'tenantSlug' => $this->tenant->slug,
                'serviceSlug' => $service->slug,
                'employeeSlug' => $this->employee->slug,
            ]), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.booking.employee-show')
            ->layout('layouts.booking-layout');
    }
}

