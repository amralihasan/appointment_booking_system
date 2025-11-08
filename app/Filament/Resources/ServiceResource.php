<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationLabel(): string
    {
        return __('filament.services');
    }

    public static function getModelLabel(): string
    {
        return __('filament.service');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.services');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.service_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),

                        Forms\Components\Textarea::make('description')
                            ->label(__('filament.description'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('filament.slug'))
                            ->required()
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                return $rule->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->maxLength(255)
                            ->helperText(__('filament.booking_url_helper')),

                        Forms\Components\TextInput::make('price')
                            ->label(__('filament.price'))
                            ->required()
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0),

                        Forms\Components\TextInput::make('duration')
                            ->label(__('filament.duration'))
                            ->required()
                            ->numeric()
                            ->suffix(__('common.minutes'))
                            ->default(60),

                        Forms\Components\TextInput::make('time_before')
                            ->label(__('filament.time_before'))
                            ->numeric()
                            ->minValue(0)
                            ->suffix(__('common.minutes'))
                            ->default(0)
                            ->helperText(__('filament.time_before_helper')),

                        Forms\Components\TextInput::make('time_after')
                            ->label(__('filament.time_after'))
                            ->numeric()
                            ->minValue(0)
                            ->suffix(__('common.minutes'))
                            ->default(0)
                            ->helperText(__('filament.time_after_helper')),

                        Forms\Components\TextInput::make('booking_scope_days')
                            ->label(__('filament.booking_scope_days'))
                            ->numeric()
                            ->minValue(1)
                            ->suffix(__('common.days'))
                            ->default(30)
                            ->helperText(__('filament.booking_scope_days_helper')),

                        Forms\Components\Select::make('type')
                            ->label(__('filament.type'))
                            ->required()
                            ->options([
                                'one' => __('filament.one_to_one'),
                                'group' => __('filament.group'),
                            ])
                            ->live()
                            ->default('one'),

                        Forms\Components\TextInput::make('max_spots')
                            ->label(__('filament.max_spots'))
                            ->numeric()
                            ->minValue(2)
                            ->required(fn (Forms\Get $get) => $get('type') === 'group')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'group')
                            ->helperText(__('filament.group_service_helper')),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label(__('filament.active')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('filament-tables::table.empty.heading', ['model' => static::getPluralModelLabel()]))
            ->emptyStateDescription(__('filament-tables::table.empty.description', ['model' => static::getPluralModelLabel()]))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('filament.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'one' => 'success',
                        'group' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'one' => __('filament.one_to_one'),
                        'group' => __('filament.group'),
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('max_spots')
                    ->label(__('filament.max_spots'))
                    ->numeric()
                    ->default('N/A')
                    ->visible(fn ($record) => $record?->type === 'group'),

                Tables\Columns\TextColumn::make('price')
                    ->label(__('filament.price'))
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label(__('filament.duration_min'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('filament.slug'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('booking_preview')
                    ->label(__('filament.preview'))
                    ->state(__('filament.preview'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->url(function ($record) {
                        // Ensure tenant is loaded
                        if (!$record->relationLoaded('tenant')) {
                            $record->load('tenant');
                        }
                        
                        if (!$record->tenant || !$record->tenant->slug || !$record->slug) {
                            return null;
                        }
                        
                        try {
                            return url('/' . $record->tenant->slug . '/' . $record->slug);
                        } catch (\Exception $e) {
                            return null;
                        }
                    })
                    ->openUrlInNewTab()
                    ->tooltip(__('filament.open_booking_preview'))
                    ->sortable(false),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('filament.active')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'one' => __('filament.one_to_one'),
                        'group' => __('filament.group'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament.active_status')),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Get tenant from Filament context (works for both regular users and impersonating owners)
        $tenant = Filament::getTenant();
        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }
        
        // Only filter by user_id if user is not an owner (owners see all users' data when impersonating)
        $user = auth()->user();
        if ($user && !$user->isOwner()) {
            $query->where('user_id', $user->id);
        }
        
        return $query->with('tenant');
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id ?? \Filament\Facades\Filament::getTenant()?->id;
        $data['user_id'] = auth()->id();

        return $data;
    }

    public static function getTenantOwnershipRelationshipName(): string
    {
        return 'tenant';
    }
}
