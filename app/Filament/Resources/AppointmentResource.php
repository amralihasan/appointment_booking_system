<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Appointments';

    protected static ?string $modelLabel = 'Appointment';

    protected static ?string $pluralModelLabel = 'Appointments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Appointment Details')
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->relationship('service', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('tenant_id', auth()->user()->tenant_id))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $service = Service::find($state);
                                    if ($service) {
                                        $set('duration', $service->duration);
                                    }
                                }
                            }),

                        Forms\Components\DateTimePicker::make('date_time')
                            ->required()
                            ->timezone(auth()->user()->timezone ?? 'Africa/Cairo')
                            ->native(false)
                            ->seconds(false),

                        Forms\Components\TextInput::make('duration')
                            ->required()
                            ->numeric()
                            ->suffix('minutes')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'booked' => 'Booked',
                                'canceled' => 'Canceled',
                                'completed' => 'Completed',
                            ])
                            ->default('booked')
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Client Information')
                    ->schema([
                        Forms\Components\Select::make('contact_id')
                            ->relationship('contact', 'first_name', modifyQueryUsing: fn (Builder $query) => $query->where('tenant_id', auth()->user()->tenant_id))
                            ->searchable()
                            ->preload()
                            ->getSearchResultsUsing(fn (string $search) => \App\Models\Contact::where('tenant_id', auth()->user()->tenant_id)
                                ->where('user_id', auth()->id())
                                ->where(function ($query) use ($search) {
                                    $query->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhere('mobile', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($contact) => [$contact->id => $contact->first_name . ' ' . $contact->last_name . ' (' . $contact->mobile . ')']))
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Contact::find($value)?->first_name . ' ' . \App\Models\Contact::find($value)?->last_name)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $contact = \App\Models\Contact::find($state);
                                    if ($contact) {
                                        $set('client_name', $contact->first_name . ' ' . $contact->last_name);
                                        $set('client_phone', $contact->mobile);
                                        $set('client_email', $contact->email);
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('client_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_phone')
                            ->required()
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_name')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->contact_id 
                        ? ContactResource::getUrl('view', ['record' => $record->contact_id])
                        : null
                    )
                    ->openUrlInNewTab(false)
                    ->color('primary')
                    ->icon(fn ($record) => $record->contact_id ? 'heroicon-o-user' : null),

                Tables\Columns\TextColumn::make('date_time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->timezone(auth()->user()->timezone ?? 'Africa/Cairo')
                    ->label('Date & Time'),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration (min)')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options(function ($record) {
                        $options = [
                            'booked' => 'Booked',
                            'canceled' => 'Canceled',
                            'completed' => 'Completed',
                        ];
                        
                        // If status is canceled, remove 'booked' option
                        if ($record && $record->status === 'canceled') {
                            unset($options['booked']);
                        }
                        
                        return $options;
                    })
                    ->selectablePlaceholder(false)
                    ->sortable()
                    ->afterStateUpdated(function ($record, $state) {
                        // Prevent changing from 'canceled' to 'booked' (double check)
                        if ($record->status === 'canceled' && $state === 'booked') {
                            throw new \Exception('Cannot change status from Canceled to Booked.');
                        }
                        $record->update(['status' => $state]);
                    })
                    ->disabled(fn ($record) => $record->status === 'completed'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'booked' => 'Booked',
                        'canceled' => 'Canceled',
                        'completed' => 'Completed',
                    ]),

                Tables\Filters\SelectFilter::make('service_id')
                    ->relationship('service', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('tenant_id', auth()->user()->tenant_id)),

                Tables\Filters\Filter::make('show_past')
                    ->label('Show Past Appointments'),

                Tables\Filters\Filter::make('date_time')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_time', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_time', '<=', $date),
                            );
                    }),
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
            ->defaultSort('date_time', 'desc');
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
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
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
