<?php

namespace App\Filament\Resources\ServiceQuestionResource\Pages;

use App\Filament\Resources\ServiceQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceQuestions extends ListRecords
{
    protected static string $resource = ServiceQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
