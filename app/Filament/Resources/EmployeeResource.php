<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('filament.employee');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.employees');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.employee_information'))
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label(__('filament.first_name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->label(__('filament.last_name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('filament.email'))
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('filament.phone'))
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\FileUpload::make('photo')
                            ->label(__('filament.photo'))
                            ->image()
                            ->disk('public')
                            ->directory('employees/photos')
                            ->visibility('public')
                            ->maxSize(1536) // 1.5MB in KB (within 2MB PHP limit)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('bio')
                            ->label(__('filament.bio'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('filament.slug'))
                            ->required()
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                return $rule->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state)))
                            ->helperText(__('filament.booking_url_helper')),

                        Forms\Components\Select::make('timezone')
                            ->label(__('filament.timezone'))
                            ->options([
                                'Africa/Cairo' => 'Africa/Cairo',
                                'UTC' => 'UTC',
                                'America/New_York' => 'America/New_York',
                                'America/Los_Angeles' => 'America/Los_Angeles',
                                'Europe/London' => 'Europe/London',
                                'Europe/Paris' => 'Europe/Paris',
                                'Asia/Dubai' => 'Asia/Dubai',
                                'Asia/Tokyo' => 'Asia/Tokyo',
                            ])
                            ->default('Africa/Cairo')
                            ->searchable()
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('filament.active'))
                            ->default(true)
                            ->helperText(__('filament.employee_active_helper_text')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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

                Tables\Columns\TextColumn::make('services_count')
                    ->label(__('filament.services'))
                    ->counts('services')
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('filament.slug'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('filament.active')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
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

        return $query;
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id ?? Filament::getTenant()?->id;

        return $data;
    }

    public static function getTenantOwnershipRelationshipName(): string
    {
        return 'tenant';
    }
}
