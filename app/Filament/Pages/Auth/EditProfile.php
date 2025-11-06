<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
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
                
                $this->getEmailFormComponent(),
                
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
                
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

}

