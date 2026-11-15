<?php

namespace App\Filament\Resources;

use App\Enums\SyllabusMappingStatus;
use App\Filament\Resources\SyllabusMappingResource\Pages;
use App\Models\SyllabusLesson;
use App\Models\SyllabusMapping;
use App\Models\YearSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SyllabusMappingResource extends Resource
{
    protected static ?string $model = SyllabusMapping::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Syllabus';

    protected static ?string $navigationLabel = 'Syllabus Mappings';

    protected static ?string $modelLabel = 'syllabus mapping';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->can('syllabus.mappings.view')
            && ($user->hasSyllabusViewAllAccess() || $user->subjectAssignments()->exists());
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user?->hasSyllabusManagementAccess() ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        return $user?->hasSyllabusManagementAccess() ?? false;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        if (! ($user?->hasSyllabusManagementAccess() ?? false)) {
            return false;
        }

        return ! static::hasProgress($record);
    }

    public static function hasProgress(SyllabusMapping $mapping): bool
    {
        return SyllabusLesson::query()
            ->where('syllabus_mapping_id', $mapping->id)
            ->whereHas('progress')
            ->exists();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery()->with(['yearSession', 'term', 'class', 'subject']);

        if ($user && $user->hasUnrestrictedAccess()) {
            return $query;
        }

        return $user ? $query->accessibleTo($user) : $query->whereKey(0);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Step 1: Academic Details')
                    ->description('Select the branch, level, class, stream, subject and teacher for this mapping.')
                    ->schema([
                        Forms\Components\Select::make('year_session_id')
                            ->label('School Session')
                            ->relationship('yearSession', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn (): ?int => YearSession::active()?->id)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('term_id', null))
                            ->required(),
                        Forms\Components\Select::make('term_id')
                            ->label('Term')
                            ->options(function (Forms\Get $get): array {
                                $query = \App\Models\Term::query()->orderBy('start_date')->orderBy('name');

                                if ($sessionId = $get('year_session_id')) {
                                    $query->where('year_session_id', $sessionId);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->default(fn (): ?int => \App\Models\Term::query()->where('status', 'active')->value('id'))
                            ->required(),
                        Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->options(\App\Models\Branch::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('academic_level_id', null);
                                $set('class_id', null);
                                $set('stream_id', null);
                                $set('subject_id', null);
                                $set('assigned_teacher_id', null);
                            })
                            ->required(),
                        Forms\Components\Select::make('academic_level_id')
                            ->label('Academic Level')
                            ->options(function (Forms\Get $get): array {
                                $branchId = $get('branch_id');

                                if (! $branchId) {
                                    return \App\Models\AcademicLevel::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id')->all();
                                }

                                $ids = \App\Models\Classes::query()->where('branch_id', $branchId)->pluck('academic_level_id')->unique()->all();

                                return \App\Models\AcademicLevel::query()->whereIn('id', $ids)->orderBy('sort_order')->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('class_id', null);
                                $set('stream_id', null);
                                $set('subject_id', null);
                                $set('assigned_teacher_id', null);
                            })
                            ->required(),
                        Forms\Components\Select::make('class_id')
                            ->label('Grade / Class')
                            ->options(function (Forms\Get $get): array {
                                $query = \App\Models\Classes::query()->orderBy('name');

                                if ($branchId = $get('branch_id')) {
                                    $query->where('branch_id', $branchId);
                                }

                                if ($levelId = $get('academic_level_id')) {
                                    $query->where('academic_level_id', $levelId);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('stream_id', null);
                                $set('subject_id', null);
                                $set('assigned_teacher_id', null);
                            })
                            ->required(),
                        Forms\Components\Select::make('stream_id')
                            ->label('Stream')
                            ->options(fn (Forms\Get $get): array => $get('class_id')
                                ? \App\Models\Stream::query()->where('class_id', $get('class_id'))->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()
                                : [])
                            ->searchable()
                            ->preload()
                            ->live()
                            ->nullable()
                            ->helperText('Streams are independently configurable per class.'),
                        Forms\Components\Select::make('subject_id')
                            ->label('Subject')
                            ->options(function (Forms\Get $get): array {
                                $levelId = $get('academic_level_id');

                                if (! $levelId && ($classId = $get('class_id'))) {
                                    $levelId = \App\Models\Classes::whereKey($classId)->value('academic_level_id');
                                }

                                $query = \App\Models\Subject::query()->where('is_active', true)->orderBy('name');

                                if ($levelId) {
                                    $query->where('academic_level_id', $levelId);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('assigned_teacher_id')
                            ->label('Assign Teacher')
                            ->options(function (): array {
                                return \App\Models\User::query()
                                    ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Teacher']))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Assigning a teacher creates a teacher assignment for this session, class, stream and subject.')
                            ->dehydrated(false),
                        Forms\Components\Select::make('status')
                            ->options(collect(SyllabusMappingStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
                            ->default(SyllabusMappingStatus::Draft->value)
                            ->required()
                            ->helperText('Activate a mapping once its structure is final. Teachers update active mappings.'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Step 2: Syllabus Structure')
                    ->description('Add strands, substrands and lesson topics. Lessons are numbered automatically in the order shown — drag the handles to reorder.')
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('importCsv')
                                ->label('Import file')
                                ->icon('heroicon-o-arrow-up-tray')
                                ->modalHeading('Import syllabus structure')
                                ->modalDescription('Upload an .xlsx or .csv. Supports the simple layout (strand, substrand, topic — one lesson per row) and the school export layout (LESSON NO / Out of / STRAND / SUB-STRAND / LESSON TOPICS, including sparse strand/sub-strand cells). A header row is optional. Importing appends to the structure below.')
                                ->form([
                                    Forms\Components\FileUpload::make('file')
                                        ->label('Syllabus file')
                                        ->acceptedFileTypes(['text/csv', 'text/plain', 'text/x-csv', 'application/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                        ->disk('local')
                                        ->required(),
                                ])
                                ->action(function (array $data, Forms\Set $set, Forms\Get $get): void {
                                    $path = Storage::disk('local')->path($data['file']);

                                    try {
                                        if (! is_file($path)) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('The uploaded file could not be read.')
                                                ->body('Please try uploading the file again.')
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        $structure = app(\App\Support\SyllabusStructure::class);
                                        $result = $structure->stateFromFile($path, $get('strands') ?? []);

                                        if ($result['imported_lessons'] === 0) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('No lesson topics found in the file.')
                                                ->body('Expected columns like strand, substrand, topic (one lesson per row), or the school export layout.')
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        $set('strands', $result['state']);
                                        $message = sprintf('Imported %d lesson topic%s across %d strand%s and %d substrand%s.', $result['imported_lessons'], $result['imported_lessons'] === 1 ? '' : 's', $result['strands'], $result['strands'] === 1 ? '' : 's', $result['substrands'], $result['substrands'] === 1 ? '' : 's');

                                        if ($result['declared_out_of']) {
                                            $message .= ' Declared total out of: '.$result['declared_out_of'].'.';
                                        }

                                        \Filament\Notifications\Notification::make()
                                            ->title('Import complete')
                                            ->body($message)
                                            ->success()
                                            ->send();
                                    } finally {
                                        Storage::disk('local')->delete($data['file']);
                                    }
                                }),
                            Forms\Components\Actions\Action::make('bulkAdd')
                                ->label('Bulk add topics')
                                ->icon('heroicon-o-clipboard-document-list')
                                ->modalHeading('Bulk add lesson topics')
                                ->modalDescription('One topic per line. Creates the strand/substrand if they do not exist yet.')
                                ->form([
                                    Forms\Components\TextInput::make('strand')->required(),
                                    Forms\Components\TextInput::make('substrand')->required(),
                                    Forms\Components\Textarea::make('topics')
                                        ->label('Topics (one per line)')
                                        ->rows(8)
                                        ->required(),
                                ])
                                ->action(function (array $data, Forms\Set $set, Forms\Get $get): void {
                                    $topics = preg_split('/\r\n|\r|\n/', $data['topics']) ?: [];
                                    $topics = array_values(array_filter(array_map('trim', $topics), fn ($topic) => $topic !== ''));

                                    if ($topics === []) {
                                        return;
                                    }

                                    $structure = app(\App\Support\SyllabusStructure::class);
                                    $set('strands', $structure->appendLessons($get('strands') ?? [], $data['strand'], $data['substrand'], $topics));
                                }),
                        ])->fullWidth(),

                        Forms\Components\Placeholder::make('total_lessons_preview')
                            ->label('Total lessons')
                            ->content(function (Forms\Get $get): string {
                                $count = 0;

                                foreach ($get('strands') ?? [] as $strand) {
                                    foreach ($strand['substrands'] ?? [] as $substrand) {
                                        $count += count($substrand['lessons'] ?? []);
                                    }
                                }

                                return (string) $count;
                            }),

                        Forms\Components\Repeater::make('strands')
                            ->label('Strands')
                            ->default([])
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null) ? $state['name'] : 'New strand')
                            ->addActionLabel('Add strand')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('name')
                                    ->label('Theme / Strand')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('description')
                                    ->maxLength(255)
                                    ->nullable(),
                                Forms\Components\DatePicker::make('expected_completion_date')
                                    ->label('Expected date of completion')
                                    ->helperText('Target date by which the whole strand should be completed. Individual lessons stay on-demand — leave blank if not yet planned.')
                                    ->nullable(),
                                Forms\Components\Repeater::make('substrands')
                                    ->label('Substrands')
                                    ->default([])
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null) ? $state['name'] : 'New substrand')
                                    ->addActionLabel('Add substrand')
                                    ->schema([
                                        Forms\Components\Hidden::make('id'),
                                        Forms\Components\TextInput::make('name')
                                            ->label('Substrand')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('description')
                                            ->maxLength(255)
                                            ->nullable(),
                                        Forms\Components\Repeater::make('lessons')
                                            ->label('Lesson topics')
                                            ->default([])
                                            ->reorderable()
                                            ->addActionLabel('Add lesson topic')
                                            ->schema([
                                                Forms\Components\Hidden::make('id'),
                                                Forms\Components\TextInput::make('topic')
                                                    ->label('Lesson topic')
                                                    ->required()
                                                    ->maxLength(500),
                                            ])
                                            ->columns(1),
                                    ])
                                    ->columns(1),
                            ])
                            ->columns(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['class.branch', 'class.academicLevel', 'stream']))
            ->columns([
                Tables\Columns\TextColumn::make('yearSession.name')
                    ->label('Session')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable(),
                Tables\Columns\TextColumn::make('class.branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('class.academicLevel.name')
                    ->label('Level')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->label('Grade / Stream')
                    ->state(fn (SyllabusMapping $record): string => $record->grade_stream)
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->leftJoin('classes', 'classes.id', '=', 'syllabus_mappings.class_id')
                        ->leftJoin('streams', 'streams.id', '=', 'syllabus_mappings.stream_id')
                        ->orderBy('classes.name', $direction)
                        ->orderBy('streams.name', $direction)
                        ->select('syllabus_mappings.*'))
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where(fn (Builder $q) => $q
                            ->whereHas('class', fn (Builder $q2) => $q2->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('stream', fn (Builder $q2) => $q2->where('name', 'like', "%{$search}%")))),
                Tables\Columns\TextColumn::make('subject.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_lessons')
                    ->label('Lessons')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assigned_teachers')
                    ->label('Teacher(s)')
                    ->state(fn (SyllabusMapping $record): string => $record->assignedTeachers()->pluck('name')->implode(', ') ?: '—')
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year_session_id')
                    ->label('School Session')
                    ->relationship('yearSession', 'name'),
                Tables\Filters\SelectFilter::make('term_id')
                    ->label('Term')
                    ->relationship('term', 'name'),
                Tables\Filters\SelectFilter::make('class.branch_id')
                    ->label('Branch')
                    ->relationship('class.branch', 'name'),
                Tables\Filters\SelectFilter::make('class.academic_level_id')
                    ->label('Academic Level')
                    ->relationship('class.academicLevel', 'name'),
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Grade')
                    ->relationship('class', 'name'),
                Tables\Filters\SelectFilter::make('stream_id')
                    ->label('Stream')
                    ->relationship('stream', 'name'),
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(SyllabusMappingStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()),
            ])
            ->actions([
                Action::make('sheet')
                    ->label('Open sheet')
                    ->icon('heroicon-o-table-cells')
                    ->color('info')
                    ->url(fn (SyllabusMapping $record): string => static::getUrl('sheet', ['record' => $record])),
                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->modalHeading('Duplicate syllabus mapping')
                    ->modalDescription('Copies the structure (strands, substrands, lessons) into a new mapping. Completion progress is not copied.')
                    ->form([
                        Forms\Components\Select::make('year_session_id')
                            ->label('School Session')
                            ->options(\App\Models\YearSession::query()->pluck('name', 'id'))
                            ->default(fn (SyllabusMapping $record) => $record->year_session_id)
                            ->required(),
                        Forms\Components\Select::make('term_id')
                            ->label('Term')
                            ->options(\App\Models\Term::query()->pluck('name', 'id'))
                            ->default(fn (SyllabusMapping $record) => $record->term_id)
                            ->required(),
                        Forms\Components\Select::make('class_id')
                            ->label('Grade / Class')
                            ->options(fn (): array => \App\Models\Classes::query()->with('branch')->get()->mapWithKeys(fn (\App\Models\Classes $record) => [$record->id => $record->branch?->name.' · '.$record->name])->all())
                            ->default(fn (SyllabusMapping $record) => $record->class_id)
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('stream_id')
                            ->label('Stream')
                            ->options(fn (SyllabusMapping $record): array => $record->class?->streams()->pluck('name', 'id')->all() ?? [])
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('subject_id')
                            ->relationship(name: 'subject', titleAttribute: 'name', modifyQueryUsing: fn ($query) => $query->orderBy('name'))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->visible(fn (): bool => static::canCreate())
                    ->action(function (SyllabusMapping $record, array $data): void {
                        $candidate = new SyllabusMapping($data);

                        if ($candidate->duplicateExists()) {
                            \Filament\Notifications\Notification::make()
                                ->title('A draft or active mapping already exists for that year, term, campus, grade and subject.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $copy = app(\App\Support\SyllabusStructure::class)->duplicate($record, $data, Auth::id());

                        \App\Support\SyllabusActivityLogger::record('syllabus_mapping', $copy->id, 'duplicated', null, ['source_id' => $record->id]);

                        \Filament\Notifications\Notification::make()
                            ->title('Mapping duplicated')
                            ->body('The copied structure is ready to review and activate.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn (SyllabusMapping $record): bool => static::canEdit($record)),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SyllabusMapping $record): bool => $record->status === SyllabusMappingStatus::Draft && static::totalLessons($record) > 0)
                    ->action(fn (SyllabusMapping $record) => $record->update(['status' => SyllabusMappingStatus::Active->value])),
                Action::make('archive')
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (SyllabusMapping $record): bool => $record->status === SyllabusMappingStatus::Active)
                    ->action(fn (SyllabusMapping $record) => $record->update(['status' => SyllabusMappingStatus::Archived->value])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (SyllabusMapping $record): bool => static::canDelete($record)),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function totalLessons(SyllabusMapping $mapping): int
    {
        return (int) SyllabusLesson::query()->where('syllabus_mapping_id', $mapping->id)->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSyllabusMappings::route('/'),
            'create' => Pages\CreateSyllabusMapping::route('/create'),
            'edit' => Pages\EditSyllabusMapping::route('/{record}/edit'),
            'sheet' => Pages\SyllabusSheet::route('/{record}/sheet'),
            'review' => Pages\SyllabusReview::route('/{record}/review'),
        ];
    }
}
