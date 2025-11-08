<?php

namespace App\Livewire;

use App\Models\Category;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Component;

class TenantProfileForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            abort(404);
        }

        $this->form->fill([
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'subdomain' => $tenant->subdomain,
            'category_id' => $tenant->category_id,
        ]);
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        
        return $form
            ->schema([
                Section::make(__('filament.tenant_information'))
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label(__('filament.category'))
                            ->options(function () {
                                return Category::orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(__('filament.select_business_category')),
                        
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament.name'))
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label(__('filament.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(\App\Models\Tenant::class, 'slug', ignorable: $tenant)
                            ->helperText(__('filament.slug_helper'))
                            ->disabled(fn () => $tenant && $tenant->exists),
                        
                        Forms\Components\TextInput::make('subdomain')
                            ->label(__('filament.subdomain'))
                            ->maxLength(255)
                            ->unique(\App\Models\Tenant::class, 'subdomain', ignorable: $tenant)
                            ->helperText(__('filament.subdomain_helper')),
                    ])
                    ->columns(2),
            ])
            ->statePath('data')
            ->model($tenant);
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            abort(404);
        }

        $data = $this->form->getState();
        
        // Don't update slug if it already exists (it's disabled in the form)
        if ($tenant->exists && isset($data['slug'])) {
            unset($data['slug']);
        }
        
        $tenant->update($data);

        Notification::make()
            ->success()
            ->title(__('filament.tenant_information_updated'))
            ->body(__('filament.tenant_information_updated_successfully'))
            ->send();
    }

    public function render()
    {
        return view('livewire.tenant-profile-form');
    }
}
