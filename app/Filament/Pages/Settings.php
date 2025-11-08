<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = null;

    protected static ?string $navigationGroup = null;

    public ?string $activeTab = 'profile';

    public function mount(): void
    {
        $this->activeTab = request()->query('tab', 'profile');
    }

    public function getTitle(): string | Htmlable
    {
        return __('filament.settings');
    }

    public function getHeading(): string | Htmlable
    {
        return __('filament.settings');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function getViewData(): array
    {
        return [
            'activeTab' => $this->activeTab,
        ];
    }
}
