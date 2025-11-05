<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AvailabilityScheduleResource\Pages;
use App\Models\AvailabilitySchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AvailabilityScheduleResource extends Resource
{
    protected static ?string $model = AvailabilitySchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Availability';

    protected static ?string $modelLabel = 'Availability Schedule';

    protected static ?string $pluralModelLabel = 'Availability Schedules';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Schedule Information')
                    ->schema([
                        Forms\Components\Select::make('day_of_week')
                            ->required()
                            ->options([
                                0 => 'Sunday',
                                1 => 'Monday',
                                2 => 'Tuesday',
                                3 => 'Wednesday',
                                4 => 'Thursday',
                                5 => 'Friday',
                                6 => 'Saturday',
                            ])
                            ->native(false)
                            ->label('Day of Week')
                            ->helperText('Select the day, then add multiple time slots below'),

                        Forms\Components\Repeater::make('time_slots')
                            ->schema([
                                Forms\Components\TimePicker::make('start_time')
                                    ->required()
                                    ->seconds(false)
                                    ->label('Start Time'),

                                Forms\Components\TimePicker::make('end_time')
                                    ->required()
                                    ->seconds(false)
                                    ->after('start_time')
                                    ->label('End Time'),

                                Forms\Components\Toggle::make('is_active')
                                    ->default(true)
                                    ->label('Active'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Add Another Time Slot')
                            ->itemLabel(fn (array $state): ?string => 
                                $state['start_time'] && $state['end_time'] 
                                    ? $state['start_time'] . ' - ' . $state['end_time']
                                    : 'New Time Slot'
                            )
                            ->required()
                            ->minItems(1)
                            ->reorderableWithButtons(),

                        Forms\Components\Toggle::make('is_active_global')
                            ->default(true)
                            ->label('All slots active by default')
                            ->helperText('This will set the default active status for all time slots'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\TextColumn::make('day_of_week')
                        ->formatStateUsing(fn (int $state): string => match ($state) {
                            0 => 'Sunday',
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday',
                            default => 'Unknown',
                        })
                        ->sortable()
                        ->weight(\Filament\Support\Enums\FontWeight::Bold)
                        ->size(\Filament\Tables\Columns\TextColumn\TextColumnSize::Large),

                    Tables\Columns\TextColumn::make('time_slots_summary')
                        ->label('Time Slots')
                        ->getStateUsing(function ($record) {
                            $slots = AvailabilitySchedule::where('tenant_id', $record->tenant_id)
                                ->where('user_id', $record->user_id)
                                ->where('day_of_week', $record->day_of_week)
                                ->orderBy('start_time')
                                ->get();
                            
                            $count = $slots->count();
                            $activeCount = $slots->where('is_active', true)->count();
                            
                            return "{$count} slot(s) • {$activeCount} active";
                        })
                        ->color('gray')
                        ->icon('heroicon-o-clock'),
                ]),

                Tables\Columns\Layout\View::make('filament.resources.availability-schedule-resource.time-slots-content')
                    ->collapsible(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->options([
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete All Slots')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        AvailabilitySchedule::where('tenant_id', $record->tenant_id)
                            ->where('user_id', $record->user_id)
                            ->where('day_of_week', $record->day_of_week)
                            ->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('day_of_week');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAvailabilitySchedules::route('/'),
            'create' => Pages\CreateAvailabilitySchedule::route('/create'),
            'edit' => Pages\EditAvailabilitySchedule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Get one record per day (the first one ordered by start_time) to avoid duplicates in the table
        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();
        
        // Use a subquery to get the minimum ID for each day
        $subquery = AvailabilitySchedule::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->selectRaw('MIN(id) as id')
            ->groupBy('day_of_week');
        
        $ids = $subquery->pluck('id');
        
        return parent::getEloquentQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->orderBy('day_of_week')
            ->orderBy('start_time');
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove time_slots and is_active_global from data as they're not model fields
        // We'll handle them in the CreateRecord page
        unset($data['time_slots'], $data['is_active_global']);
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['user_id'] = auth()->id();

        return $data;
    }
}
