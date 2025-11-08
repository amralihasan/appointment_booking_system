<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label(__('filament.employee'))
                    ->relationship('employees', 'full_name', modifyQueryUsing: function (Builder $query) {
                        $tenant = Filament::getTenant();
                        if ($tenant) {
                            $query->where('tenant_id', $tenant->id);
                        }
                        return $query;
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        return $record->full_name;
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label(__('filament.photo'))
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (!$record) return null;
                        $initials = strtoupper(substr($record->first_name ?? '', 0, 1) . substr($record->last_name ?? '', 0, 1));
                        return "https://ui-avatars.com/api/?name={$initials}&background=6366f1&color=fff&size=200&bold=true";
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('filament.name'))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('filament.email'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('filament.phone'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament.active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $tenant = Filament::getTenant();
                        if ($tenant) {
                            $query->where('tenant_id', $tenant->id);
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('first_name', 'asc');
    }
}
