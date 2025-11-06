<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Contact;
use Filament\Infolists;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\Pages\EditContact;
use App\Filament\Resources\ContactResource\Pages\ViewContact;
use App\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Filament\Resources\ContactResource\Pages\CreateContact;
use App\Filament\Resources\ContactResource\RelationManagers\AppointmentsRelationManager;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationLabel(): string
    {
        return __('filament.contacts');
    }

    public static function getModelLabel(): string
    {
        return __('filament.contact');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.contacts');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.client_information'))
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label(__('filament.first_name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->label(__('filament.last_name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('mobile')
                            ->label(__('filament.mobile'))
                            ->required()
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('filament.email'))
                            ->email()
                            ->maxLength(255),
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
                Tables\Columns\TextColumn::make('first_name')
                    ->label(__('filament.first_name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->label(__('filament.last_name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('filament.full_name'))
                    ->getStateUsing(fn ($record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable(['first_name', 'last_name']),

                Tables\Columns\TextColumn::make('mobile')
                    ->label(__('filament.mobile'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('filament.email'))
                    ->searchable()
                    ->copyable()
                    ->default('N/A'),

                Tables\Columns\TextColumn::make('appointments_count')
                    ->counts('appointments')
                    ->label(__('filament.appointments'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('filament.client_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('first_name')
                            ->label(__('filament.first_name')),
                        
                        Infolists\Components\TextEntry::make('last_name')
                            ->label(__('filament.last_name')),
                        
                        Infolists\Components\TextEntry::make('mobile')
                            ->label(__('filament.mobile'))
                            ->copyable()
                            ->icon('heroicon-o-phone'),
                        
                        Infolists\Components\TextEntry::make('email')
                            ->label(__('filament.email'))
                            ->copyable()
                            ->icon('heroicon-o-envelope')
                            ->default('N/A'),
                        
                        Infolists\Components\TextEntry::make('appointments_count')
                            ->label(__('filament.total_appointments'))
                            ->getStateUsing(fn ($record) => $record->appointments()->count()),
                        
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('filament.created_at'))
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AppointmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
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
