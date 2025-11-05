<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
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

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $modelLabel = 'Service';

    protected static ?string $pluralModelLabel = 'Services';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                return $rule->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->maxLength(255)
                            ->helperText('Used in booking URL: domain-name.coach-name/service-name'),

                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('EGP')
                            ->default(0),

                        Forms\Components\TextInput::make('duration')
                            ->required()
                            ->numeric()
                            ->suffix('minutes')
                            ->default(60),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'one' => 'One-to-One',
                                'group' => 'Group',
                            ])
                            ->live()
                            ->default('one'),

                        Forms\Components\TextInput::make('max_spots')
                            ->numeric()
                            ->minValue(2)
                            ->required(fn (Forms\Get $get) => $get('type') === 'group')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'group')
                            ->helperText('Required for group services'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'one' => 'success',
                        'group' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'one' => 'One-to-One',
                        'group' => 'Group',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('max_spots')
                    ->label('Max Spots')
                    ->numeric()
                    ->default('N/A')
                    ->visible(fn ($record) => $record?->type === 'group'),

                Tables\Columns\TextColumn::make('price')
                    ->money('EGP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration (min)')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'one' => 'One-to-One',
                        'group' => 'Group',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
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
            //
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
        return parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('user_id', auth()->id());
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['user_id'] = auth()->id();

        return $data;
    }
}
