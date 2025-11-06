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
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationLabel(): string
    {
        return __('filament.appointments');
    }

    public static function getModelLabel(): string
    {
        return __('filament.appointment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.appointments');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.appointment_details'))
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->label(__('filament.service_name'))
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
                            ->label(__('filament.date_time'))
                            ->required()
                            ->timezone(auth()->user()->timezone ?? 'Africa/Cairo')
                            ->native(false)
                            ->seconds(false),

                        Forms\Components\TextInput::make('duration')
                            ->label(__('filament.duration'))
                            ->required()
                            ->numeric()
                            ->suffix(__('common.minutes'))
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label(__('filament.status'))
                            ->required()
                            ->options([
                                'booked' => __('filament.booked'),
                                'canceled' => __('filament.canceled'),
                                'completed' => __('filament.completed'),
                            ])
                            ->default('booked')
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('filament.client_information'))
                    ->schema([
                        Forms\Components\Select::make('contact_id')
                            ->label(__('filament.contact'))
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
                            ->label(__('filament.client_name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_phone')
                            ->label(__('filament.client_phone'))
                            ->required()
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_email')
                            ->label(__('filament.client_email'))
                            ->email()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('filament.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('service.name')
                    ->label(__('filament.service_name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_name')
                    ->label(__('filament.client_name'))
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
                    ->time('g:i A')
                    ->sortable()
                    ->timezone(auth()->user()->timezone ?? 'Africa/Cairo')
                    ->label(__('filament.time')),

                Tables\Columns\TextColumn::make('duration')
                    ->label(__('filament.duration_min'))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\SelectColumn::make('status')
                    ->label(__('filament.status'))
                    ->options(function ($record) {
                        $options = [
                            'booked' => __('filament.booked'),
                            'canceled' => __('filament.canceled'),
                            'completed' => __('filament.completed'),
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
                            throw new \Exception(__('filament.cannot_change_status'));
                        }
                        $record->update(['status' => $state]);
                    })
                    ->disabled(fn ($record) => $record->status === 'completed'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Group::make('date_time')
                    ->date()
                    ->label(__('filament.date'))
                    ->getTitleFromRecordUsing(function ($record) {
                        // Return Carbon instance - Filament will format it with locale
                        return Carbon::parse($record->date_time)
                            ->timezone(auth()->user()->timezone ?? 'Africa/Cairo')
                            ->locale(app()->getLocale());
                    })
                    ->getKeyFromRecordUsing(function ($record) {
                        // Return parseable date key (Y-m-d format)
                        $dateTime = $record->date_time instanceof Carbon 
                            ? $record->date_time->copy()->utc() 
                            : Carbon::parse($record->date_time, 'UTC')->utc();
                        return $dateTime->format('Y-m-d');
                    })
                    ->scopeQueryByKeyUsing(function (Builder $query, string $key) {
                        // Parse the date key (format: Y-m-d)
                        $date = Carbon::parse($key, 'UTC');
                        return $query->whereDate('date_time', $date);
                    })
                    ->getDescriptionFromRecordUsing(function ($record) {
                        // $record->date_time is already a Carbon instance (from datetime cast)
                        // Get the date string in Y-m-d format from UTC
                        $dateTime = $record->date_time instanceof Carbon 
                            ? $record->date_time->copy()->utc() 
                            : Carbon::parse($record->date_time, 'UTC')->utc();
                        
                        $dateString = $dateTime->format('Y-m-d');
                        
                        // Build query with same base filters as the table
                        // Use whereRaw with DATE() function to compare dates correctly
                        $query = Appointment::where('tenant_id', $record->tenant_id)
                            ->where('user_id', $record->user_id)
                            ->whereRaw('DATE(date_time) = ?', [$dateString]);
                        
                        // Get all records for this date
                        $dateAppointments = $query->get();
                        
                        $total = $dateAppointments->count();
                        $booked = $dateAppointments->where('status', 'booked')->count();
                        $canceled = $dateAppointments->where('status', 'canceled')->count();
                        $completed = $dateAppointments->where('status', 'completed')->count();
                        
                        $parts = [];
                        if ($booked > 0) {
                            $parts[] = "{$booked} " . __('filament.booked');
                        }
                        if ($canceled > 0) {
                            $parts[] = "{$canceled} " . __('filament.canceled');
                        }
                        if ($completed > 0) {
                            $parts[] = "{$completed} " . __('filament.completed');
                        }
                        
                        $statusText = !empty($parts) ? ' • ' . implode(' • ', $parts) : '';
                        $totalText = $total === 1 ? __('filament.appointment') : __('filament.appointments');
                        
                        return "{$total} {$totalText}{$statusText}";
                    })
                    ->collapsible()
                    ->orderQueryUsing(function (Builder $query, string $direction) {
                        // Sort groups by date, nearest first (ascending)
                        return $query->orderByRaw("DATE(date_time) ASC");
                    }),
            ])
            ->defaultGroup('date_time')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'booked' => __('filament.booked'),
                        'canceled' => __('filament.canceled'),
                        'completed' => __('filament.completed'),
                    ]),

                Tables\Filters\SelectFilter::make('service_id')
                    ->relationship('service', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('tenant_id', auth()->user()->tenant_id)),

                Tables\Filters\Filter::make('show_past')
                    ->label(__('filament.show_past_appointments')),

                Tables\Filters\Filter::make('date_time')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('filament.from_date')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('filament.until_date')),
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
            ->defaultSort('date_time', 'asc');
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
