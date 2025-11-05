<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Appointments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('tenant_id', auth()->user()->tenant_id))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $service = \App\Models\Service::find($state);
                            if ($service) {
                                $set('duration', $service->duration);
                            }
                        }
                    }),

                Forms\Components\DateTimePicker::make('date_time')
                    ->required()
                    ->timezone(auth()->user()->timezone ?? 'Africa/Cairo')
                    ->native(false)
                    ->seconds(false),

                Forms\Components\TextInput::make('duration')
                    ->required()
                    ->numeric()
                    ->suffix('minutes')
                    ->disabled(),

                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'booked' => 'Booked',
                        'canceled' => 'Canceled',
                        'completed' => 'Completed',
                    ])
                    ->default('booked')
                    ->native(false),

                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('client_name')
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_time')
                    ->label('Time')
                    ->getStateUsing(function ($record) {
                        $start = Carbon::parse($record->date_time)
                            ->timezone(auth()->user()->timezone ?? 'Africa/Cairo');
                        $end = $start->copy()->addMinutes($record->duration);
                        return $start->format('h:i A') . ' - ' . $end->format('h:i A');
                    })
                    ->sortable()
                    ->searchable(false),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration (min)')
                    ->numeric()
                    ->sortable(),

                    Tables\Columns\SelectColumn::make('status')
                        ->label('Status')
                        ->options(function ($record) {
                            $options = [
                                'booked' => 'Booked',
                                'canceled' => 'Canceled',
                                'completed' => 'Completed',
                            ];
                            
                            // If status is canceled, remove 'booked' option
                            if ($record && $record->status === 'canceled') {
                                unset($options['booked']);
                            }
                            
                            return $options;
                        })
                        ->selectablePlaceholder(false)
                        ->sortable()
                        ->afterStateUpdated(function ($record, $state) {
                            // Prevent changing from 'canceled' to 'booked' (double check)
                            if ($record->status === 'canceled' && $state === 'booked') {
                                throw new \Exception('Cannot change status from Canceled to Booked.');
                            }
                            $record->update(['status' => $state]);
                        })
                        ->disabled(fn ($record) => $record->status === 'completed'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes),
            ])
            ->groups([
                Group::make('date_time')
                    ->date()
                    ->label('Date')
                    ->getTitleFromRecordUsing(fn ($record) => Carbon::parse($record->date_time)
                        ->timezone(auth()->user()->timezone ?? 'Africa/Cairo')
                        ->locale(app()->getLocale())
                        ->translatedFormat('d F Y'))
                    ->collapsible()
                    ->orderQueryUsing(function (Builder $query, string $direction) {
                        // Sort groups by date, nearest first (ascending)
                        return $query->orderByRaw("DATE(date_time) ASC");
                    }),
            ])
            ->defaultGroup('date_time')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'booked' => 'Booked',
                        'canceled' => 'Canceled',
                        'completed' => 'Completed',
                    ]),
                Tables\Filters\Filter::make('show_past')
                    ->label('Show Past Appointments'),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Default: show only incoming appointments (date_time >= now)
                // Check if the show_past filter is active - if not, filter to incoming only
                $filters = $this->tableFilters ?? [];
                $showPastActive = isset($filters['show_past']['isActive']) && $filters['show_past']['isActive'] === true;
                
                    if (!$showPastActive) {
                        $userTimezone = auth()->user()->timezone ?? 'Africa/Cairo';
                        $now = Carbon::now($userTimezone)->utc(); // Convert to UTC for database comparison
                        return $query->where('date_time', '>=', $now);
                    }
                
                return $query;
            })
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tenant_id'] = auth()->user()->tenant_id;
                        $data['user_id'] = auth()->id();
                        $data['contact_id'] = $this->ownerRecord->id;
                        $data['client_name'] = $this->ownerRecord->first_name . ' ' . $this->ownerRecord->last_name;
                        $data['client_phone'] = $this->ownerRecord->mobile;
                        $data['client_email'] = $this->ownerRecord->email;
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date_time', 'asc');
    }

    protected function canCreate(): bool
    {
        return true;
    }

    protected function canEdit(Model $record): bool
    {
        return true;
    }

    protected function canDelete(Model $record): bool
    {
        return true;
    }
}
