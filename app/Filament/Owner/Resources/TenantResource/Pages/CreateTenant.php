<?php

namespace App\Filament\Owner\Resources\TenantResource\Pages;

use App\Filament\Owner\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
