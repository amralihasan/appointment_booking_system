<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\TenantResource\Pages;
use App\Filament\Owner\Resources\TenantResource\RelationManagers;
use App\Models\Category;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.tenant_information'))
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the business category for this tenant'),
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('filament.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('filament.slug_helper')),
                        Forms\Components\TextInput::make('subdomain')
                            ->label(__('filament.subdomain'))
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('filament.subdomain_helper')),
                        Forms\Components\Select::make('status')
                            ->label(__('filament.status'))
                            ->options([
                                'active' => __('filament.active'),
                                'suspended' => __('filament.suspended'),
                                'trial' => __('filament.trial'),
                            ])
                            ->required()
                            ->default('trial'),
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label(__('filament.trial_ends_at'))
                            ->timezone('UTC'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color(fn ($record) => $record->category?->color ?? 'gray')
                    ->icon(fn ($record) => $record->category?->icon ?? 'heroicon-o-tag')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('filament.slug'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subdomain')
                    ->label(__('filament.subdomain'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'danger',
                        'trial' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('filament.users'))
                    ->counts('users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('appointments_count')
                    ->label(__('filament.appointments'))
                    ->counts('appointments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label(__('filament.trial_ends_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament.status'))
                    ->options([
                        'active' => __('filament.active'),
                        'suspended' => __('filament.suspended'),
                        'trial' => __('filament.trial'),
                    ]),
                Tables\Filters\Filter::make('trial')
                    ->label(__('filament.on_trial'))
                    ->query(fn (Builder $query): Builder => $query->where('status', 'trial')),
                Tables\Filters\Filter::make('trial_expired')
                    ->label(__('filament.trial_expired'))
                    ->query(fn (Builder $query): Builder => $query->where('status', 'trial')
                        ->where('trial_ends_at', '<', now())),
            ])
            ->actions([
                Tables\Actions\Action::make('switch')
                    ->label(__('filament.switch_to_tenant'))
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('primary')
                    ->action(function (Tenant $record) {
                        // Store tenant ID in session for impersonation
                        session()->put('owner_impersonating_tenant_id', $record->id);
                        // Redirect to tenant's admin panel
                        return redirect()->route('filament.admin.pages.dashboard', ['tenant' => $record->slug]);
                    }),
                Tables\Actions\Action::make('activate')
                    ->label(__('filament.activate'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Tenant $record) => $record->update(['status' => 'active']))
                    ->visible(fn (Tenant $record) => $record->status !== 'active'),
                Tables\Actions\Action::make('suspend')
                    ->label(__('filament.suspend'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Tenant $record) => $record->update(['status' => 'suspended']))
                    ->visible(fn (Tenant $record) => $record->status !== 'suspended'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('filament.activate'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn (Tenant $record) => $record->update(['status' => 'active']))),
                    Tables\Actions\BulkAction::make('suspend')
                        ->label(__('filament.suspend'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn (Tenant $record) => $record->update(['status' => 'suspended']))),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
