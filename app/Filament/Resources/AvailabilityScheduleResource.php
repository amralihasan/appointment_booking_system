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

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationLabel(): string
    {
        return __('filament.availability');
    }

    public static function getModelLabel(): string
    {
        return __('filament.availability_schedule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.availability_schedules');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.schedule_information'))
                    ->schema([
                        Forms\Components\Select::make('day_of_week')
                            ->required()
                            ->options([
                                0 => __('filament.sunday'),
                                1 => __('filament.monday'),
                                2 => __('filament.tuesday'),
                                3 => __('filament.wednesday'),
                                4 => __('filament.thursday'),
                                5 => __('filament.friday'),
                                6 => __('filament.saturday'),
                            ])
                            ->native(false)
                            ->label(__('filament.day_of_week'))
                            ->helperText(__('filament.select_day_helper')),

                        Forms\Components\Repeater::make('time_slots')
                            ->schema([
                                Forms\Components\TimePicker::make('start_time')
                                    ->required()
                                    ->seconds(false)
                                    ->label(__('filament.start_time')),

                                Forms\Components\TimePicker::make('end_time')
                                    ->required()
                                    ->seconds(false)
                                    ->after('start_time')
                                    ->label(__('filament.end_time')),

                                Forms\Components\Toggle::make('is_active')
                                    ->default(true)
                                    ->label(__('filament.active')),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel(__('filament.add_another_time_slot'))
                            ->itemLabel(fn (array $state): ?string => 
                                $state['start_time'] && $state['end_time'] 
                                    ? $state['start_time'] . ' - ' . $state['end_time']
                                    : __('filament.new_time_slot')
                            )
                            ->required()
                            ->minItems(1)
                            ->reorderableWithButtons(),

                        Forms\Components\Toggle::make('is_active_global')
                            ->default(true)
                            ->label(__('filament.all_slots_active_by_default'))
                            ->helperText(__('filament.all_slots_active_helper')),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('filament-tables::table.empty.heading', ['model' => static::getPluralModelLabel()]))
            ->emptyStateDescription(__('filament-tables::table.empty.description', ['model' => static::getPluralModelLabel()]))
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\TextColumn::make('day_of_week')
                        ->formatStateUsing(fn (int $state): string => match ($state) {
                            0 => __('filament.sunday'),
                            1 => __('filament.monday'),
                            2 => __('filament.tuesday'),
                            3 => __('filament.wednesday'),
                            4 => __('filament.thursday'),
                            5 => __('filament.friday'),
                            6 => __('filament.saturday'),
                            default => __('filament.unknown'),
                        })
                        ->sortable()
                        ->weight(\Filament\Support\Enums\FontWeight::Bold)
                        ->size(\Filament\Tables\Columns\TextColumn\TextColumnSize::Large),

                    Tables\Columns\TextColumn::make('time_slots_summary')
                        ->label(__('filament.time_slots'))
                        ->getStateUsing(function ($record) {
                            $slots = AvailabilitySchedule::where('tenant_id', $record->tenant_id)
                                ->where('user_id', $record->user_id)
                                ->where('day_of_week', $record->day_of_week)
                                ->orderBy('start_time')
                                ->get();
                            
                            $count = $slots->count();
                            $activeCount = $slots->where('is_active', true)->count();
                            
                            $slotText = trans_choice('filament.n_slots', $count, ['count' => $count]);
                            $activeText = trans_choice('filament.n_active_slots', $activeCount, ['count' => $activeCount]);
                            
                            return "{$slotText} • {$activeText}";
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
                        0 => __('filament.sunday'),
                        1 => __('filament.monday'),
                        2 => __('filament.tuesday'),
                        3 => __('filament.wednesday'),
                        4 => __('filament.thursday'),
                        5 => __('filament.friday'),
                        6 => __('filament.saturday'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament.active_status')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label(__('filament.delete_all_slots'))
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
