<?php

namespace App\Livewire;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ProfileForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(404);
        }

        $this->form->fill([
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'whatsapp' => $user->whatsapp,
            'timezone' => $user->timezone ?? 'Africa/Cairo',
            'language' => $user->language ?? 'en',
        ]);
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();
        
        return $form
            ->schema([
                Forms\Components\Section::make(__('filament.profile'))
                    ->schema([
                        TextInput::make('first_name')
                            ->label(__('filament.first_name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                        
                        TextInput::make('last_name')
                            ->label(__('filament.last_name'))
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('email')
                            ->label(__('filament.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(\App\Models\User::class, 'email', ignorable: $user),
                        
                        TextInput::make('mobile')
                            ->label(__('filament.mobile'))
                            ->tel()
                            ->maxLength(20),
                        
                        TextInput::make('whatsapp')
                            ->label(__('filament.whatsapp'))
                            ->tel()
                            ->maxLength(20),
                        
                        Select::make('timezone')
                            ->label(__('filament.timezone'))
                            ->options([
                                'Africa/Cairo' => 'Cairo (GMT+2)',
                                'Africa/Casablanca' => 'Casablanca (GMT+1)',
                                'Africa/Johannesburg' => 'Johannesburg (GMT+2)',
                                'Africa/Lagos' => 'Lagos (GMT+1)',
                                'Africa/Nairobi' => 'Nairobi (GMT+3)',
                                'America/New_York' => 'New York (GMT-5)',
                                'America/Chicago' => 'Chicago (GMT-6)',
                                'America/Denver' => 'Denver (GMT-7)',
                                'America/Los_Angeles' => 'Los Angeles (GMT-8)',
                                'America/Toronto' => 'Toronto (GMT-5)',
                                'America/Sao_Paulo' => 'São Paulo (GMT-3)',
                                'Asia/Dubai' => 'Dubai (GMT+4)',
                                'Asia/Kolkata' => 'Kolkata (GMT+5:30)',
                                'Asia/Singapore' => 'Singapore (GMT+8)',
                                'Asia/Tokyo' => 'Tokyo (GMT+9)',
                                'Asia/Shanghai' => 'Shanghai (GMT+8)',
                                'Asia/Hong_Kong' => 'Hong Kong (GMT+8)',
                                'Europe/London' => 'London (GMT+0)',
                                'Europe/Paris' => 'Paris (GMT+1)',
                                'Europe/Berlin' => 'Berlin (GMT+1)',
                                'Europe/Rome' => 'Rome (GMT+1)',
                                'Europe/Madrid' => 'Madrid (GMT+1)',
                                'Europe/Moscow' => 'Moscow (GMT+3)',
                                'Europe/Istanbul' => 'Istanbul (GMT+3)',
                                'Australia/Sydney' => 'Sydney (GMT+10)',
                                'Australia/Melbourne' => 'Melbourne (GMT+10)',
                                'Pacific/Auckland' => 'Auckland (GMT+12)',
                                'UTC' => 'UTC (GMT+0)',
                            ])
                            ->default('Africa/Cairo')
                            ->searchable(),
                        
                        Select::make('language')
                            ->label(__('filament.language'))
                            ->options([
                                'en' => __('filament.english'),
                                'ar' => __('filament.arabic'),
                            ])
                            ->default('en'),
                        
                        TextInput::make('password')
                            ->label(__('filament.password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                            ->live(debounce: 500)
                            ->same('passwordConfirmation'),
                        
                        TextInput::make('passwordConfirmation')
                            ->label(__('filament.password_confirmation'))
                            ->password()
                            ->revealable()
                            ->required(fn (Forms\Get $get): bool => filled($get('password')))
                            ->visible(fn (Forms\Get $get): bool => filled($get('password')))
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('data')
            ->model($user);
    }

    public function save(): void
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(404);
        }

        $data = $this->form->getState();
        
        // Remove password confirmation from data
        unset($data['passwordConfirmation']);
        
        // Only update password if it's provided
        if (empty($data['password'])) {
            unset($data['password']);
        }
        
        $user->update($data);

        Notification::make()
            ->success()
            ->title(__('filament.profile_updated'))
            ->body(__('filament.profile_updated_successfully'))
            ->send();
    }

    public function render()
    {
        return view('livewire.profile-form');
    }
}
