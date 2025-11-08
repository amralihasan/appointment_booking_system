<?php

namespace App\Filament\Resources\AppointmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClientInformationRelationManager extends RelationManager
{
    protected static string $relationship = 'clientInformation';
    
    protected static string $view = 'filament.resources.appointment-resource.relation-managers.client-information-relation-manager';

    public ?array $data = [];
    
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filament.client_information');
    }

    // This is not a real relationship, we'll override the methods
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
    
    public function areFormActionsSticky(): bool
    {
        return false;
    }
    
    public function getFormActionsAlignment(): string
    {
        return 'end';
    }

    protected function canCreate(): bool
    {
        return false;
    }

    protected function canEdit(Model $record): bool
    {
        return false;
    }

    protected function canDelete(Model $record): bool
    {
        return false;
    }

    public function mount(): void
    {
        parent::mount();
        
        // Fill the form with appointment data after parent mount
        $appointment = $this->getOwnerRecord();
        $this->data = [
            'contact_id' => $appointment->contact_id,
            'client_name' => $appointment->client_name,
            'client_phone' => $appointment->client_phone,
            'client_email' => $appointment->client_email,
            'notes' => $appointment->notes,
        ];
        
        // Explicitly fill the form
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        $appointment = $this->getOwnerRecord();
        
        return $form
            ->model($appointment)
            ->statePath('data')
            ->schema([
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

    public function table(Table $table): Table
    {
        // This won't be used since we're showing a form, not a table
        return $table
            ->columns([])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    // Override to return the owner record as a "fake" relationship
    protected function getTableQuery(): Builder
    {
        return $this->getOwnerRecord()->newQuery()->where('id', $this->getOwnerRecord()->id);
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        $appointment = $this->getOwnerRecord();
        $appointment->update($data);
        $appointment->refresh();
        
        // Update the data property and refill the form
        $this->data = [
            'contact_id' => $appointment->contact_id,
            'client_name' => $appointment->client_name,
            'client_phone' => $appointment->client_phone,
            'client_email' => $appointment->client_email,
            'notes' => $appointment->notes,
        ];
        $this->form->fill($this->data);
        
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Client information updated')
            ->send();
    }
    
    protected function getForms(): array
    {
        return ['form'];
    }
}

