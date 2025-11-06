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

    protected static ?string $title = null;

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('filament.appointments');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_id')
                    ->label(__('filament.service_name'))
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
                    ->label(__('filament.date_time'))
                    ->required()
                    ->timezone(auth()->user()->timezone ?? 'Africa/Cairo')
                    ->native(false)
                    ->seconds(false),

                Forms\Components\TextInput::make('duration')
                    ->label(__('filament.duration'))
                    ->required()
                    ->numeric()
                    ->suffix(__('common.minutes'))
                    ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label(__('filament.status'))
                            ->required()
                            ->options([
                                'booked' => __('filament.booked'),
                                'canceled' => __('filament.canceled'),
                                'completed' => __('filament.completed'),
                            ])
                    ->default('booked')
                    ->native(false),

                Forms\Components\Textarea::make('notes')
                    ->label(__('filament.notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('client_name')
            ->emptyStateHeading(__('filament-tables::table.empty.heading', ['model' => __('filament.appointments')]))
            ->emptyStateDescription(__('filament-tables::table.empty.description', ['model' => __('filament.appointments')]))
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->label(__('filament.service_name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_time')
                    ->label(__('filament.time'))
                    ->getStateUsing(function ($record) {
                        $start = Carbon::parse($record->date_time)
                            ->timezone(auth()->user()->timezone ?? 'Africa/Cairo');
                        $end = $start->copy()->addMinutes($record->duration);
                        return $start->format('h:i A') . ' - ' . $end->format('h:i A');
                    })
                    ->sortable()
                    ->searchable(false),

                Tables\Columns\TextColumn::make('duration')
                    ->label(__('filament.duration_min'))
                    ->numeric()
                    ->sortable(),

                    Tables\Columns\SelectColumn::make('status')
                        ->label(__('filament.status'))
                        ->options(function ($record) {
                            $options = [
                                'booked' => __('filament.booked'),
                                'canceled' => __('filament.canceled'),
                                'completed' => __('filament.completed'),
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
                                throw new \Exception(__('filament.cannot_change_status'));
                            }
                            $record->update(['status' => $state]);
                        })
                        ->disabled(fn ($record) => $record->status === 'completed'),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('filament.notes'))
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes),
            ])
            ->groups([
                Group::make('date_time')
                    ->date()
                    ->label(__('filament.date'))
                    ->getTitleFromRecordUsing(function ($record) {
                        // Return Carbon instance - Filament will format it with locale
                        return Carbon::parse($record->date_time)
                            ->timezone(auth()->user()->timezone ?? 'Africa/Cairo')
                            ->locale(app()->getLocale());
                    })
                    ->getKeyFromRecordUsing(function ($record) {
                        // Return parseable date key (Y-m-d format)
                        $dateTime = $record->date_time instanceof Carbon 
                            ? $record->date_time->copy()->utc() 
                            : Carbon::parse($record->date_time, 'UTC')->utc();
                        return $dateTime->format('Y-m-d');
                    })
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
                        'booked' => __('filament.booked'),
                        'canceled' => __('filament.canceled'),
                        'completed' => __('filament.completed'),
                    ]),
                Tables\Filters\Filter::make('show_past')
                    ->label(__('filament.show_past_appointments')),
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
