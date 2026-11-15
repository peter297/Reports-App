<?php

namespace App\Filament\Resources\ClassesResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StreamsRelationManager extends RelationManager
{
    protected static string $relationship = 'streams';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Stream name')
                    ->placeholder('e.g. Blue, Green, Red')
                    ->required()
                    ->maxLength(100)
                    ->rule(function (?Model $record): \Closure {
                        return function (string $attribute, $value, \Closure $fail) use ($record): void {
                            $classId = $record?->class_id;

                            if (! $classId) {
                                return;
                            }

                            $exists = \App\Models\Stream::query()
                                ->where('class_id', $classId)
                                ->whereRaw('LOWER(name) = ?', [strtolower(trim((string) $value))])
                                ->when($record?->id, fn ($query) => $query->whereKeyNot($record->id))
                                ->exists();

                            if ($exists) {
                                $fail('This class already has a stream with that name.');
                            }
                        };
                    }),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Add stream'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('academic_structure.manage') ?? false),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record): bool => ! static::streamIsReferenced($record)),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function streamIsReferenced(\App\Models\Stream $stream): bool
    {
        return \App\Models\SubjectAssignment::query()->where('stream_id', $stream->id)->exists()
            || \App\Models\SyllabusMapping::query()->where('stream_id', $stream->id)->exists()
            || \App\Models\Attendance::query()->where('stream_id', $stream->id)->exists()
            || \App\Models\ClassSize::query()->where('stream_id', $stream->id)->exists();
    }
}
