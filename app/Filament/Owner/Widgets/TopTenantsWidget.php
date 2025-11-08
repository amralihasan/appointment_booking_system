<?php

namespace App\Filament\Owner\Widgets;

use App\Models\Tenant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopTenantsWidget extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 3;

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return __('filament.top_tenants_by_appointments');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tenant::query()
                    ->withCount('appointments')
                    ->orderBy('appointments_count', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('filament.slug'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'danger',
                        'trial' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('appointments_count')
                    ->label(__('filament.appointments'))
                    ->counts('appointments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('filament.users'))
                    ->counts('users')
                    ->sortable(),
            ])
            ->defaultSort('appointments_count', 'desc');
    }
}

