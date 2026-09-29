<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicAnalysisResource\Pages;
use App\Models\AcademicAnalysis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AcademicAnalysisResource extends Resource
{
    protected static ?string $model = AcademicAnalysis::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Academic Analysis';

    protected static ?string $navigationLabel = 'Analysis';

    protected static ?string $modelLabel = 'Academic Analysis';

    protected static ?string $pluralModelLabel = 'Academic Analysis';

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return ($user?->hasUnrestrictedAccess()
            || $user?->isHead()
            || $user?->isDeputy()
            || $user?->can('view_any_academic::analysis')
            || ($user?->hasRole('Coordinators') && $user?->sectionCoordinatorAssignments()->exists()))
            ?? false;
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return ($user?->hasUnrestrictedAccess()
            || $user?->can('create_academic::analysis')
            || ($user?->hasRole('Coordinators') && $user?->sectionCoordinatorAssignments()->exists()))
            ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('update_academic::analysis')) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('delete_academic::analysis')) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Context')
                    ->schema([
                        Forms\Components\Select::make('year_session_id')
                            ->label('Academic Year')
                            ->relationship('yearSession', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn (): ?int => \App\Models\YearSession::active()?->id)
                            ->required(),
                        Forms\Components\Select::make('term_id')
                            ->label('Term')
                            ->relationship('term', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn (): ?int => \App\Models\Term::active()?->id)
                            ->required(),
                        Forms\Components\Select::make('exam_type')
                            ->label('Exam')
                            ->options(collect(\App\Models\AcademicAnalysis::EXAM_TYPES)
                                ->mapWithKeys(fn (string $exam): array => [$exam => $exam])
                                ->all())
                            ->default('Mid Term')
                            ->required(),
                        Forms\Components\Select::make('branch')
                            ->options(function (): array {
                                $user = Auth::user();

                                if ($user?->hasUnrestrictedAccess() || $user?->isHead()) {
                                    return [
                                        'Juja Road' => 'Juja Road',
                                        'Kitisuru' => 'Kitisuru',
                                        'South C' => 'South C',
                                    ];
                                }

                                if ($user?->isDeputy()) {
                                    return $user->branch ? [$user->branch => $user->branch] : [];
                                }

                                $branches = $user?->coordinatedBranches() ?? [];

                                return array_combine($branches, $branches);
                            })
                            ->default(function (): ?string {
                                $user = Auth::user();

                                if ($user?->hasUnrestrictedAccess() || $user?->isHead() || $user?->isDeputy()) {
                                    return $user?->branch;
                                }

                                return $user?->sectionCoordinatorAssignments()->first()?->branch;
                            })
                            ->disabled(function (): bool {
                                $user = Auth::user();

                                if ($user?->hasUnrestrictedAccess() || $user?->isHead()) {
                                    return false;
                                }

                                if ($user?->isDeputy()) {
                                    return true;
                                }

                                return count($user?->coordinatedBranches() ?? []) <= 1;
                            })
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('section', null);
                                $set('class_id', null);
                                $set('stream_id', null);
                                $set('subjects', null);
                            })
                            ->required()
                            ->dehydrated(),
                        Forms\Components\Select::make('section')
                            ->options(function (Forms\Get $get): array {
                                $user = Auth::user();
                                $sections = ['EYE', 'Upper Primary', 'Junior School'];

                                if ($user?->hasUnrestrictedAccess() || $user?->isHead()) {
                                    return array_combine($sections, $sections);
                                }

                                if ($user?->isDeputy()) {
                                    return array_combine($sections, $sections);
                                }

                                $assignments = $user?->sectionCoordinatorAssignments();

                                if ($branch = $get('branch')) {
                                    $assignments = $assignments?->where('branch', $branch);
                                }

                                return $assignments?->pluck('section', 'section')->all() ?? [];
                            })
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get): void {
                                $set('class_id', null);
                                $set('stream_id', null);
                                $set('subjects', static::defaultSubjectRows($get('section'), $get('branch')));
                            })
                            ->required(),
                        Forms\Components\Select::make('class_id')
                            ->label('Class')
                            ->relationship(name: 'class', titleAttribute: 'name')
                            ->options(fn (Forms\Get $get): array => AttendanceResource::attendanceClassOptions($get('branch'), $get('section')))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set): mixed => $set('stream_id', null))
                            ->required(),
                        Forms\Components\Select::make('stream_id')
                            ->label('Stream')
                            ->relationship(name: 'stream', titleAttribute: 'name')
                            ->options(fn (Forms\Get $get): array => AttendanceResource::attendanceStreamOptions(
                                $get('branch'),
                                $get('section'),
                                $get('class_id') ? (int) $get('class_id') : null,
                            ))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(3),
                Forms\Components\Section::make('Learning Areas')
                    ->description('Enter the average and learner counts per band for each subject.')
                    ->schema([
                        Forms\Components\Repeater::make('subjects')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('subject')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('average')
                                    ->label('L.A. Average')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->default(0),
                                Forms\Components\TextInput::make('exceeding')
                                    ->label('Exceeding')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('meeting')
                                    ->label('Meeting')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('approaching')
                                    ->label('Approaching')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('below')
                                    ->label('Below')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                            ])
                            ->columns(6)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->default(fn (Forms\Get $get): ?array => static::defaultSubjectRows($get('section'), $get('branch')))
                            ->addActionLabel('Add learning area')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['subject'] ?? null)
                            ->required(),
                    ]),
            ]);
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public static function defaultSubjectRows(?string $section, ?string $branch): ?array
    {
        if (! $section) {
            return null;
        }

        $names = \App\Models\Subject::namesForSection($section, $branch);

        if ($names === []) {
            return null;
        }

        return collect($names)
            ->map(fn (string $name): array => [
                'subject' => $name,
                'average' => 0,
                'exceeding' => 0,
                'meeting' => 0,
                'approaching' => 0,
                'below' => 0,
            ])
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('sectionPdf')
                    ->label('Section Report PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->form(static::scopeFormFields())
                    ->action(function (array $data, \Livewire\Component $livewire): void {
                        $livewire->redirect(route('academic-analysis.section.pdf', [
                            'yearSession' => $data['year_session_id'],
                            'term' => $data['term_id'],
                            'exam' => $data['exam_type'],
                            'branch' => $data['branch'],
                            'section' => $data['section'],
                        ]));
                    }),
                Tables\Actions\Action::make('sectionExcel')
                    ->label('Section Report Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->form(static::scopeFormFields())
                    ->action(function (array $data, \Livewire\Component $livewire): void {
                        $livewire->redirect(route('academic-analysis.section.excel', [
                            'yearSession' => $data['year_session_id'],
                            'term' => $data['term_id'],
                            'exam' => $data['exam_type'],
                            'branch' => $data['branch'],
                            'section' => $data['section'],
                        ]));
                    }),
                Tables\Actions\Action::make('yearPdf')
                    ->label('Year Report PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->visible(fn (): bool => auth()->user()?->hasUnrestrictedAccess() || auth()->user()?->isHead() || auth()->user()?->isDeputy() ?? false)
                    ->form(static::yearFormFields())
                    ->action(function (array $data, \Livewire\Component $livewire): void {
                        $livewire->redirect(route('academic-analysis.year.pdf', [
                            'yearSession' => $data['year_session_id'],
                            'branch' => $data['branch'] ?? 'all',
                            'section' => $data['section'] ?? 'all',
                        ]));
                    }),
                Tables\Actions\Action::make('schoolsPdf')
                    ->label('All Schools PDF')
                    ->icon('heroicon-o-globe-alt')
                    ->color('info')
                    ->visible(fn (): bool => auth()->user()?->hasUnrestrictedAccess() || auth()->user()?->isHead() ?? false)
                    ->form(static::schoolsFormFields())
                    ->action(function (array $data, \Livewire\Component $livewire): void {
                        $livewire->redirect(route('academic-analysis.schools.pdf', [
                            'yearSession' => $data['year_session_id'],
                        ]));
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('yearSession.name')
                    ->label('Year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_type')
                    ->label('Exam')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('section')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->label('Class')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stream.name')
                    ->label('Stream')
                    ->sortable(),
                Tables\Columns\TextColumn::make('overall_average')
                    ->label('Average')
                    ->getStateUsing(fn (AcademicAnalysis $record): float => $record->overallAverage())
                    ->numeric(decimalPlaces: 2),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year_session_id')
                    ->label('Academic Year')
                    ->relationship('yearSession', 'name')
                    ->preload(),
                Tables\Filters\SelectFilter::make('term_id')
                    ->label('Term')
                    ->relationship('term', 'name')
                    ->preload(),
                Tables\Filters\SelectFilter::make('exam_type')
                    ->label('Exam')
                    ->options(['Mid Term' => 'Mid Term', 'End Term' => 'End Term']),
                Tables\Filters\SelectFilter::make('branch')
                    ->options([
                        'Juja Road' => 'Juja Road',
                        'Kitisuru' => 'Kitisuru',
                        'South C' => 'South C',
                    ]),
                Tables\Filters\SelectFilter::make('section')
                    ->options([
                        'EYE' => 'EYE',
                        'Upper Primary' => 'Upper Primary',
                        'Junior School' => 'Junior School',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (AcademicAnalysis $record): string => route('academic-analysis.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('excel')
                    ->label('Excel')
                    ->icon('heroicon-o-table-cells')
                    ->url(fn (AcademicAnalysis $record): string => route('academic-analysis.excel', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected static function scopeFormFields(): array
    {
        $user = Auth::user();

        return [
            Forms\Components\Select::make('year_session_id')
                ->label('Academic Year')
                ->relationship('yearSession', 'name')
                ->searchable()
                ->preload()
                ->default(fn (): ?int => \App\Models\YearSession::active()?->id)
                ->required(),
            Forms\Components\Select::make('term_id')
                ->label('Term')
                ->relationship('term', 'name')
                ->searchable()
                ->preload()
                ->default(fn (): ?int => \App\Models\Term::active()?->id)
                ->required(),
            Forms\Components\Select::make('exam_type')
                ->label('Exam')
                ->options(['Mid Term' => 'Mid Term', 'End Term' => 'End Term'])
                ->required(),
            Forms\Components\Select::make('branch')
                ->options(function () use ($user): array {
                    if ($user?->hasUnrestrictedAccess() || $user?->isHead()) {
                        return ['Juja Road' => 'Juja Road', 'Kitisuru' => 'Kitisuru', 'South C' => 'South C'];
                    }

                    if ($user?->isDeputy()) {
                        return $user->branch ? [$user->branch => $user->branch] : [];
                    }

                    $branches = $user?->coordinatedBranches() ?? [];

                    return array_combine($branches, $branches);
                })
                ->default(fn () => $user?->branch)
                ->required(),
            Forms\Components\Select::make('section')
                ->options(function () use ($user): array {
                    $sections = ['EYE' => 'EYE', 'Upper Primary' => 'Upper Primary', 'Junior School' => 'Junior School'];

                    if ($user?->hasUnrestrictedAccess() || $user?->isHead() || $user?->isDeputy()) {
                        return $sections;
                    }

                    return $user?->sectionCoordinatorAssignments()->pluck('section', 'section')->all() ?? [];
                })
                ->required(),
        ];
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected static function yearFormFields(): array
    {
        $user = Auth::user();

        return [
            Forms\Components\Select::make('year_session_id')
                ->label('Academic Year')
                ->relationship('yearSession', 'name')
                ->searchable()
                ->preload()
                ->default(fn (): ?int => \App\Models\YearSession::active()?->id)
                ->required(),
            Forms\Components\Select::make('branch')
                ->options(function () use ($user): array {
                    $options = ['all' => 'All Branches'];

                    if ($user?->hasUnrestrictedAccess() || $user?->isHead()) {
                        return $options + ['Juja Road' => 'Juja Road', 'Kitisuru' => 'Kitisuru', 'South C' => 'South C'];
                    }

                    return $user?->branch ? $options + [$user->branch => $user->branch] : $options;
                })
                ->default('all')
                ->required(),
            Forms\Components\Select::make('section')
                ->options(['all' => 'All Sections', 'EYE' => 'EYE', 'Upper Primary' => 'Upper Primary', 'Junior School' => 'Junior School'])
                ->default('all')
                ->required(),
        ];
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected static function schoolsFormFields(): array
    {
        return [
            Forms\Components\Select::make('year_session_id')
                ->label('Academic Year')
                ->relationship('yearSession', 'name')
                ->searchable()
                ->preload()
                ->default(fn (): ?int => \App\Models\YearSession::active()?->id)
                ->required(),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery()->whereKey(0);
        }

        return parent::getEloquentQuery()
            ->with(['yearSession', 'term', 'class', 'stream'])
            ->accessibleTo($user);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicAnalyses::route('/'),
            'create' => Pages\CreateAcademicAnalysis::route('/create'),
            'edit' => Pages\EditAcademicAnalysis::route('/{record}/edit'),
        ];
    }
}
