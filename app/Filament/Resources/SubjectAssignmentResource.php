<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectAssignmentResource\Pages;
use App\Models\SubjectAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubjectAssignmentResource extends Resource
{
    protected static ?string $model = SubjectAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'School Management';

    protected static ?string $navigationLabel = 'Teacher Assignments';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user?->can('teacher_assignments.view') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment')
                    ->description('Assign a subject to a teacher for a class-stream-subject combination in a school session.')
                    ->schema([
                        Forms\Components\Select::make('year_session_id')
                            ->label('School Session')
                            ->relationship('yearSession', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('class_id', null))
                            ->required(),
                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->relationship(name: 'subject', titleAttribute: 'name', modifyQueryUsing: fn ($query) => $query->with('academicLevel')->orderBy('academic_level_id')->orderBy('name'))
                            ->getOptionLabelFromRecordUsing(fn (\App\Models\Subject $record): string => $record->name.($record->academicLevel?->name ? ' ('.$record->academicLevel->name.')' : ''))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('class_id')
                            ->label('Grade / Class')
                            ->options(function (Forms\Get $get): array {
                                $levelId = $get('subject_id') ? \App\Models\Subject::find($get('subject_id'))?->academic_level_id : null;

                                $query = \App\Models\Classes::query()->with('branch')->orderBy('name');

                                if ($levelId) {
                                    $query->where('academic_level_id', $levelId);
                                }

                                return $query->get()->mapWithKeys(fn (\App\Models\Classes $record) => [$record->id => $record->branch?->name.' · '.$record->name])->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('stream_id', null)),
                        Forms\Components\Select::make('stream_id')
                            ->label('Stream')
                            ->options(function (Forms\Get $get): array {
                                $classId = $get('class_id');

                                if (! $classId) {
                                    return [];
                                }

                                return \App\Models\Stream::query()->where('class_id', $classId)->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Leave blank to assign at grade level only.'),
                        Forms\Components\Select::make('assignment_role')
                            ->label('Role')
                            ->options(SubjectAssignment::roles())
                            ->required()
                            ->default(SubjectAssignment::ROLE_TEACHER),
                        Forms\Components\Select::make('user_id')
                            ->label('Teacher / Coordinator')
                            ->relationship('user', 'name', modifyQueryUsing: fn ($query) => $query->orderBy('name'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('branch')
                            ->label('Campus')
                            ->options([
                                'Juja Road' => 'Juja Road',
                                'Kitisuru' => 'Kitisuru',
                                'South C' => 'South C',
                            ])
                            ->helperText('Leave blank to apply across all campuses (used by coordinators).')
                            ->preload(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('yearSession.name')
                    ->label('Session')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.academicLevel.name')
                    ->label('Level')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->label('Grade')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('class.branch.name')
                    ->label('Branch')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stream.name')
                    ->label('Stream')
                    ->placeholder('All streams'),
                Tables\Columns\TextColumn::make('branch')
                    ->label('Campus')
                    ->placeholder('All campuses')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assignment_role')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assignment_role')
                    ->options(SubjectAssignment::roles()),
                Tables\Filters\SelectFilter::make('year_session_id')
                    ->label('School Session')
                    ->relationship('yearSession', 'name'),
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name'),
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Grade / Class')
                    ->relationship('class', 'name'),
                Tables\Filters\SelectFilter::make('stream_id')
                    ->relationship('stream', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('teacher_assignments.manage') ?? false),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('teacher_assignments.manage') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('teacher_assignments.manage') ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubjectAssignments::route('/'),
            'create' => Pages\CreateSubjectAssignment::route('/create'),
            'edit' => Pages\EditSubjectAssignment::route('/{record}/edit'),
        ];
    }
}
