<?php

namespace App\Filament\Resources\AppointmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionAnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'questionAnswers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_question_id')
                    ->label('Question')
                    ->relationship('question', 'question_text')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(),

                Forms\Components\Textarea::make('answer_value')
                    ->label('Answer')
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
                    ->label('Question')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('question.field_type')
                    ->label('Field Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'email' => 'info',
                        'number' => 'warning',
                        'textarea' => 'success',
                        'select_one' => 'primary',
                        'select_multiple' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'text' => 'Text',
                        'email' => 'Email',
                        'number' => 'Number',
                        'textarea' => 'Long Text',
                        'select_one' => 'Select One',
                        'select_multiple' => 'Select Multiple',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('answer_value')
                    ->label('Answer')
                    ->formatStateUsing(function ($state, $record) {
                        // Handle select_multiple (JSON)
                        if ($record->question && $record->question->field_type === 'select_multiple') {
                            $values = json_decode($state, true);
                            if (is_array($values)) {
                                return implode(', ', $values);
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
