<?php

namespace App\Filament\Resources\ServiceQuestionResource\Pages;

use App\Filament\Resources\ServiceQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class CreateServiceQuestion extends CreateRecord
{
    protected static string $resource = ServiceQuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert options from repeater format to array
        if (isset($data['options']) && is_array($data['options'])) {
            $data['options'] = array_column($data['options'], 'value');
        }
        
        // Ensure service_id is set if not provided
        if (!isset($data['service_id']) || empty($data['service_id'])) {
            // Try to get service from query parameter or default to first service
            $serviceId = request()->query('service_id');
            if ($serviceId) {
                $data['service_id'] = $serviceId;
            }
        }
        
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // ServiceQuestion doesn't have a direct tenant relationship,
        // so we save it directly instead of through tenant association
        return static::getModel()::create($data);
    }
}
