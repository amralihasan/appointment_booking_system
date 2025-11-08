<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceQuestionResource\Pages;
use App\Filament\Resources\ServiceQuestionResource\RelationManagers;
use App\Models\ServiceQuestion;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceQuestionResource extends Resource
{
    protected static ?string $model = ServiceQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;
    
    public static function getNavigationLabel(): string
    {
        return __('filament.service_questions');
    }
    
    public static function getModelLabel(): string
    {
        return __('filament.service_question');
    }
    
    public static function getPluralModelLabel(): string
    {
        return __('filament.service_questions');
    }

    protected static ?int $navigationSort = 7;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.question_information'))
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->label(__('filament.service'))
                            ->relationship('service', 'name', modifyQueryUsing: function (Builder $query) {
                                $tenant = Filament::getTenant();
                                if ($tenant) {
                                    $query->where('tenant_id', $tenant->id);
                                }
                                $user = auth()->user();
                                if ($user && !$user->isOwner()) {
                                    $query->where('user_id', $user->id);
                                }
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($context) => $context === 'edit'),

                        Forms\Components\TextInput::make('question_text')
                            ->label(__('filament.question_text'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('field_type')
                            ->label(__('filament.field_type'))
                            ->required()
                            ->options([
                                'text' => __('filament.field_type_text'),
                                'email' => __('filament.field_type_email'),
                                'number' => __('filament.field_type_number'),
                                'textarea' => __('filament.field_type_textarea'),
                                'select_one' => __('filament.field_type_select_one'),
                                'select_multiple' => __('filament.field_type_select_multiple'),
                                'date' => __('filament.field_type_date'),
                            ])
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('options', null)),

                        Forms\Components\Repeater::make('options')
                            ->label(__('filament.options'))
                            ->schema([
                                Forms\Components\TextInput::make('value')
                                    ->label(__('filament.option_value'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel(__('filament.add_option'))
                            ->visible(fn (Forms\Get $get) => in_array($get('field_type'), ['select_one', 'select_multiple']))
                            ->required(fn (Forms\Get $get) => in_array($get('field_type'), ['select_one', 'select_multiple']))
                            ->helperText(__('filament.add_options_helper'))
                            ->dehydrated(fn ($state) => !empty($state))
                            ->default(fn ($record) => $record && $record->options ? array_map(fn ($opt) => ['value' => $opt], $record->options) : [])
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_required')
                            ->label(__('filament.required'))
                            ->default(false)
                            ->helperText(__('filament.required_helper')),

                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('filament.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('filament.sort_order_helper')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->label(__('filament.service'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('question_text')
                    ->label(__('filament.question'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                    Tables\Columns\TextColumn::make('field_type')
                        ->label(__('filament.field_type'))
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'text' => 'gray',
                            'email' => 'info',
                            'number' => 'warning',
                            'textarea' => 'success',
                            'select_one' => 'primary',
                            'select_multiple' => 'danger',
                            'date' => 'warning',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'text' => __('filament.field_type_text'),
                            'email' => __('filament.field_type_email'),
                            'number' => __('filament.field_type_number'),
                            'textarea' => __('filament.field_type_textarea'),
                            'select_one' => __('filament.field_type_select_one_short'),
                            'select_multiple' => __('filament.field_type_select_multiple_short'),
                            'date' => __('filament.field_type_date'),
                            default => $state,
                        })
                    ->sortable(),

                Tables\Columns\TextColumn::make('options')
                    ->label(__('filament.options'))
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return __('filament.not_available');
                        }
                        
                        // If it's already an array, use it directly
                        if (is_array($state)) {
                            return implode(', ', $state);
                        }
                        
                        // If it's a JSON string, decode it first
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            if (is_array($decoded)) {
                                return implode(', ', $decoded);
                            }
                        }
                        
                        return $state;
                    })
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_required')
                    ->label(__('filament.required'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('filament.sort_order'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_id')
                    ->label(__('filament.service'))
                    ->relationship('service', 'name', modifyQueryUsing: function (Builder $query) {
                        $tenant = Filament::getTenant();
                        if ($tenant) {
                            $query->where('tenant_id', $tenant->id);
                        }
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('field_type')
                    ->label(__('filament.field_type'))
                    ->options([
                        'text' => __('filament.field_type_text'),
                        'email' => __('filament.field_type_email'),
                        'number' => __('filament.field_type_number'),
                        'textarea' => __('filament.field_type_textarea'),
                        'select_one' => __('filament.field_type_select_one'),
                        'select_multiple' => __('filament.field_type_select_multiple'),
                        'date' => __('filament.field_type_date'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_required')
                    ->label(__('filament.required_status')),
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
            ->defaultSort('sort_order', 'asc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $tenant = Filament::getTenant();
        if ($tenant) {
            $query->whereHas('service', function ($q) use ($tenant) {
                $q->where('tenant_id', $tenant->id);
            });
        }
        
        $user = auth()->user();
        if ($user && !$user->isOwner()) {
            $query->whereHas('service', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }
        
        return $query->with('service');
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
            'index' => Pages\ListServiceQuestions::route('/'),
            'create' => Pages\CreateServiceQuestion::route('/create'),
            'edit' => Pages\EditServiceQuestion::route('/{record}/edit'),
        ];
    }
}
