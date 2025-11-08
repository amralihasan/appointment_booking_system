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
                        Forms\Components\Select::make('user_id')
                            ->label(__('filament.user'))
                            ->relationship('user', 'first_name', modifyQueryUsing: function (Builder $query) {
                                $tenant = \Filament\Facades\Filament::getTenant();
                                if ($tenant) {
                                    $query->where('tenant_id', $tenant->id);
                                }
                                $user = auth()->user();
                                if ($user && !$user->isOwner()) {
                                    $query->where('id', $user->id);
                                }
                            })
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return $record->full_name;
                            })
                            ->searchable(['first_name', 'last_name', 'email'])
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('employee_id', null))
                            ->helperText(__('filament.select_user_or_employee')),

                        Forms\Components\Select::make('employee_id')
                            ->label(__('filament.employee'))
                            ->relationship('employee', 'first_name', modifyQueryUsing: function (Builder $query) {
                                $tenant = \Filament\Facades\Filament::getTenant();
                                if ($tenant) {
                                    $query->where('tenant_id', $tenant->id);
                                }
                            })
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return $record->full_name;
                            })
                            ->searchable(['first_name', 'last_name', 'email'])
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('user_id', null))
                            ->helperText(__('filament.select_user_or_employee'))
                            ->visible(fn ($get) => !$get('user_id')),

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
        // Get tenant from Filament context (works for both regular users and impersonating owners)
        $tenant = \Filament\Facades\Filament::getTenant();
        $user = auth()->user();
        
        if (!$tenant) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return empty query if no tenant
        }
        
        $tenantId = $tenant->id;
        $userId = $user && !$user->isOwner() ? $user->id : null;
        
        // Use a subquery to get the minimum ID for each day
        $subquery = AvailabilitySchedule::query()
            ->where('tenant_id', $tenantId);
        
        if ($userId) {
            $subquery->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereNull('user_id');
            });
        }
        
        $subquery->selectRaw('MIN(id) as id')
            ->groupBy('day_of_week', 'user_id', 'employee_id');
        
        $ids = $subquery->pluck('id');
        
        $query = parent::getEloquentQuery()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->orderBy('day_of_week')
            ->orderBy('start_time');
        
        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereNull('user_id');
            });
        }
        
        return $query;
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove time_slots and is_active_global from data as they're not model fields
        // We'll handle them in the CreateRecord page
        unset($data['time_slots'], $data['is_active_global']);
        $data['tenant_id'] = auth()->user()->tenant_id ?? \Filament\Facades\Filament::getTenant()?->id;
        
        // If employee_id is set, don't set user_id (or set to null)
        if (isset($data['employee_id']) && $data['employee_id']) {
            $data['user_id'] = null;
        } else {
            $data['user_id'] = $data['user_id'] ?? auth()->id();
            $data['employee_id'] = null;
        }

        return $data;
    }

    public static function getTenantOwnershipRelationshipName(): string
    {
        return 'tenant';
    }
}
