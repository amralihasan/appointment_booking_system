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

    protected static ?string $navigationLabel = 'Service Questions';

    protected static ?string $modelLabel = 'Service Question';

    protected static ?string $pluralModelLabel = 'Service Questions';

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Question Information')
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->label('Service')
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
                            ->label('Question Text')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('field_type')
                            ->label('Field Type')
                            ->required()
                            ->options([
                                'text' => 'Text',
                                'email' => 'Email',
                                'number' => 'Number',
                                'textarea' => 'Long Text',
                                'select_one' => 'Select One Option',
                                'select_multiple' => 'Select Multiple Options',
                                'date' => 'Date',
                            ])
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('options', null)),

                        Forms\Components\Repeater::make('options')
                            ->label('Options')
                            ->schema([
                                Forms\Components\TextInput::make('value')
                                    ->label('Option Value')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Option')
                            ->visible(fn (Forms\Get $get) => in_array($get('field_type'), ['select_one', 'select_multiple']))
                            ->required(fn (Forms\Get $get) => in_array($get('field_type'), ['select_one', 'select_multiple']))
                            ->helperText('Add options for select fields')
                            ->dehydrated(fn ($state) => !empty($state))
                            ->default(fn ($record) => $record && $record->options ? array_map(fn ($opt) => ['value' => $opt], $record->options) : [])
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_required')
                            ->label('Required')
                            ->default(false)
                            ->helperText('Customer must answer this question'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('question_text')
                    ->label('Question')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                    Tables\Columns\TextColumn::make('field_type')
                        ->label('Field Type')
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
                            'text' => 'Text',
                            'email' => 'Email',
                            'number' => 'Number',
                            'textarea' => 'Long Text',
                            'select_one' => 'Select One',
                            'select_multiple' => 'Select Multiple',
                            'date' => 'Date',
                            default => $state,
                        })
                    ->sortable(),

                Tables\Columns\TextColumn::make('options')
                    ->label('Options')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'N/A';
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
                    ->label('Required')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name', modifyQueryUsing: function (Builder $query) {
                        $tenant = Filament::getTenant();
                        if ($tenant) {
                            $query->where('tenant_id', $tenant->id);
                        }
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('field_type')
                    ->label('Field Type')
                    ->options([
                        'text' => 'Text',
                        'email' => 'Email',
                        'number' => 'Number',
                        'textarea' => 'Long Text',
                        'select_one' => 'Select One Option',
                        'select_multiple' => 'Select Multiple Options',
                    ]),

                Tables\Filters\TernaryFilter::make('is_required')
                    ->label('Required Status'),
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
