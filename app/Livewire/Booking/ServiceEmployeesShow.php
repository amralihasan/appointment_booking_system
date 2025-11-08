<?php

namespace App\Livewire\Booking;

use App\Models\Employee;
use App\Models\Service;
use App\Models\Tenant;
use Livewire\Component;

class ServiceEmployeesShow extends Component
{
    public ?Tenant $tenant = null;
    public ?Service $service = null;
    public string $tenantSlug;
    public string $serviceSlug;

    public function mount(string $tenantSlug, string $serviceSlug)
    {
        $this->tenantSlug = $tenantSlug;
        $this->serviceSlug = $serviceSlug;

        // Load tenant and service
        // Allow both 'active' and 'trial' status tenants
        $this->tenant = Tenant::where('slug', $tenantSlug)
            ->whereIn('status', ['active', 'trial'])
            ->firstOrFail();

        $this->service = Service::where('tenant_id', $this->tenant->id)
            ->where('slug', $serviceSlug)
            ->where('is_active', true)
            ->with(['employees' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('first_name')
                    ->orderBy('last_name');
            }])
            ->firstOrFail();
    }

    public function selectEmployee(int $employeeId)
    {
        // Redirect to booking page with employee context
        $employee = $this->service->employees()->find($employeeId);
        if ($employee) {
            return $this->redirect(route('booking.show', [
                'tenantSlug' => $this->tenant->slug,
                'serviceSlug' => $this->service->slug,
                'employeeSlug' => $employee->slug,
            ]), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.booking.service-employees-show')
            ->layout('livewire.layouts.booking-layout');
    }
}

