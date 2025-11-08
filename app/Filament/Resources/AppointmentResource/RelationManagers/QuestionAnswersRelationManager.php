<?php

namespace App\Filament\Resources\AppointmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionAnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'questionAnswers';
    
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filament.question_answers');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_question_id')
                    ->label(__('filament.question'))
                    ->relationship('question', 'question_text')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(),

                Forms\Components\Textarea::make('answer_value')
                    ->label(__('filament.answer'))
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('answer_value')
            ->columns([
                Tables\Columns\TextColumn::make('question.question_text')
                    ->label(__('filament.question'))
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('question.field_type')
                    ->label(__('filament.field_type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'email' => 'info',
                        'number' => 'warning',
                        'textarea' => 'success',
                        'select_one' => 'primary',
                        'select_multiple' => 'danger',
                        'date' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'text' => __('filament.field_type_text'),
                        'email' => __('filament.field_type_email'),
                        'number' => __('filament.field_type_number'),
                        'textarea' => __('filament.field_type_textarea'),
                        'select_one' => __('filament.field_type_select_one_short'),
                        'select_multiple' => __('filament.field_type_select_multiple_short'),
                        'date' => __('filament.field_type_date'),
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('answer_value')
                    ->label(__('filament.answer'))
                    ->formatStateUsing(function ($state, $record) {
                        // Handle select_multiple (JSON)
                        if ($record->question && $record->question->field_type === 'select_multiple') {
                            $values = json_decode($state, true);
                            if (is_array($values)) {
                                return implode(', ', $values);
                            }
                        }
                        // Handle date formatting
                        if ($record->question && $record->question->field_type === 'date' && $state) {
                            try {
                                return \Carbon\Carbon::parse($state)->format('Y-m-d');
                            } catch (\Exception $e) {
                                return $state;
                            }
                        }
                        return $state;
                    })
                    ->wrap()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No create action - answers are created during booking
            ])
            ->actions([
                // Read-only - answers shouldn't be edited after booking
            ])
            ->bulkActions([
                // No bulk actions - answers shouldn't be deleted
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('question'))
            ->defaultSort('id', 'asc');
    }
}
