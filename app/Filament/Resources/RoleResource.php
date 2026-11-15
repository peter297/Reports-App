<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?string $modelLabel = 'role';

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasSystemAccess() || (auth()->user()?->can('roles.manage') ?? false);
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        if (! $user || ! self::canViewAny()) {
            return false;
        }

        if ($record->name === 'super_admin') {
            return false;
        }

        return ! $record->users()->exists();
    }

    public static function deleteBlockReason(Role $record): string
    {
        if ($record->name === 'super_admin') {
            return 'The super admin role cannot be deleted.';
        }

        $count = $record->users()->count();

        if ($count > 0) {
            return "This role is assigned to {$count} user(s). Unassign them first.";
        }

        return '';
    }

    /**
     * The single source of truth for the permission catalog, subdivided by module.
     *
     * @return array<string, array{label: string, description: string, permissions: array<string, string>}>
     */
    public static function permissionGroups(): array
    {
        return [
            'syllabus' => [
                'label' => 'Syllabus Module',
                'description' => 'Coverage tracking, mapping visibility and teacher progress.',
                'permissions' => [
                    'syllabus.dashboard.view' => 'View the coverage dashboard',
                    'syllabus.mappings.view' => 'View mappings (scoped)',
                    'syllabus.mappings.progress' => 'Record lesson completion progress',
                    'syllabus.mappings.manage' => 'Manage mappings and syllabus structure',
                    'syllabus.reviews.manage' => 'Add coordinator review comments',
                    'syllabus.reports.view' => 'Download mapping reports (PDF / Excel)',
                ],
            ],
            'reports' => [
                'label' => 'Reports Module',
                'description' => 'Organizational reporting: submission, review, approval and consolidation.',
                'permissions' => [
                    'reports.module.view' => 'See the Reports module in navigation',
                    'reports.view' => 'View reports (scoped)',
                    'reports.view_all' => 'View all reports (any branch)',
                    'reports.create' => 'Create reports from templates',
                    'reports.submit' => 'Submit reports',
                    'reports.review' => 'Mark received / under review',
                    'reports.return' => 'Return reports for revision',
                    'reports.approve' => 'Approve reports',
                    'reports.compile' => 'Compile consolidated reports',
                    'reports.templates.manage' => 'Manage report templates',
                    'reports.routing.manage' => 'Manage reporting routing',
                ],
            ],
            'school_structure' => [
                'label' => 'School Structure',
                'description' => 'Sessions, branches, classes, streams, subjects and teacher assignments.',
                'permissions' => [
                    'sessions.view' => 'View sessions, terms and weeks',
                    'sessions.manage' => 'Manage sessions, terms and weeks',
                    'branches.view' => 'View branches',
                    'branches.manage' => 'Manage branches',
                    'academic_structure.view' => 'View levels, classes, streams and subjects',
                    'academic_structure.manage' => 'Manage levels, classes, streams and subjects',
                    'teacher_assignments.view' => 'View teacher assignments',
                    'teacher_assignments.manage' => 'Manage teacher assignments',
                    'enrollments.view' => 'View learner numbers and enrollments',
                    'enrollments.manage' => 'Manage learner numbers and enrollments',
                ],
            ],
            'attendance' => [
                'label' => 'Attendance & Class Sizes',
                'description' => 'Daily attendance records and learner-count baselines.',
                'permissions' => [
                    'attendance.view' => 'View attendance data',
                    'attendances.manage' => 'Manage attendance records and class sizes',
                ],
            ],
            'discipline' => [
                'label' => 'Discipline',
                'description' => 'Discipline records and follow-up.',
                'permissions' => [
                    'discipline.view' => 'View discipline records',
                    'discipline.manage' => 'Manage discipline records',
                ],
            ],
            'assessments' => [
                'label' => 'Assessments & Documents',
                'description' => 'Formative assessment visibility and professional documents.',
                'permissions' => [
                    'assessments.view' => 'View formative assessments',
                    'documents.view' => 'View professional documents',
                ],
            ],
            'events' => [
                'label' => 'Events & Calendar',
                'description' => 'Events, calendar generation and month quote images.',
                'permissions' => [
                    'events.view' => 'View events and the school calendar',
                    'events.manage' => 'Manage events, calendar and month quotes',
                ],
            ],
            'item_requests' => [
                'label' => 'Item Requests',
                'description' => 'Stationery, equipment and other requisitions.',
                'permissions' => [
                    'item_requests.view' => 'View item requests',
                    'item_requests.manage' => 'Manage item requests (approve / fulfil)',
                ],
            ],
            'operations' => [
                'label' => 'Operations',
                'description' => 'Billings and maintenance.',
                'permissions' => [
                    'billings.view' => 'View billing and fee records',
                    'billings.manage' => 'Manage billing and fee records',
                    'maintenance.view' => 'View maintenance work',
                    'maintenance.manage' => 'Manage maintenance work',
                ],
            ],
            'activities' => [
                'label' => 'Co & Extra Curricular',
                'description' => 'Clubs, sports and extra-curricular activities.',
                'permissions' => [
                    'co_curriculars.view' => 'View co-curricular activities',
                    'co_curriculars.manage' => 'Manage co-curricular activities',
                    'extra_curriculars.view' => 'View extra-curricular activities',
                    'extra_curriculars.manage' => 'Manage extra-curricular activities',
                ],
            ],
            'goalsheets' => [
                'label' => 'Goal Sheets',
                'description' => 'Staff goal sheets and performance tracking.',
                'permissions' => [
                    'goalsheets.view' => 'View goal sheets within scope',
                    'goalsheets.create' => 'Create goal sheets',
                    'goalsheets.edit' => 'Edit goal sheets (draft only)',
                    'goalsheets.delete' => 'Delete goal sheets',
                    'goalsheets.acknowledge' => 'Acknowledge/sign goal sheets (owner or line managers)',
                    'goalsheets.reports' => 'View consolidated goal sheet dashboards and summaries',
                    'goalsheets.admin' => 'Import the workbook and configure categories/measures',
                ],
            ],
            'general' => [
                'label' => 'General',
                'description' => 'Panel-wide pages such as the main dashboard.',
                'permissions' => [
                    'dashboard.view' => 'View the Dashboard',
                ],
            ],
            'administration' => [
                'label' => 'Administration',
                'description' => 'Accounts, access control and system settings.',
                'permissions' => [
                    'users.view' => 'View users and assignments',
                    'users.manage' => 'Manage users and assignments',
                    'roles.manage' => 'Manage roles and permissions',
                    'coordinator_assignments.view' => 'View coordinator assignments',
                    'coordinator_assignments.manage' => 'Manage coordinator assignments',
                    'settings.manage' => 'Manage system settings',
                ],
            ],
        ];
    }

    /**
     * Flat option map (name => label).
     *
     * @return array<string, string>
     */
    public static function permissionOptions(): array
    {
        return collect(static::permissionGroups())->flatMap(fn (array $group) => $group['permissions'])->all();
    }

    /** Name => section key for grouping stored permissions. */
    public static function sectionKeyFor(string $permission): ?string
    {
        foreach (static::permissionGroups() as $key => $group) {
            if (array_key_exists($permission, $group['permissions'])) {
                return $key;
            }
        }

        return null;
    }

    public static function form(Form $form): Form
    {
        $isSuperAdmin = fn (?Role $record): bool => $record?->name === 'super_admin';

        return $form->schema([
            Forms\Components\Section::make('Role')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Role name')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('Use a clear, organisation-appropriate name, e.g. "Campus Administrator" or "Junior School Coordinator".'),
                    Forms\Components\TextInput::make('guard_name')
                        ->hidden()
                        ->default('web')
                        ->dehydrated(),
                ]),

            Forms\Components\Section::make('Permissions')
                ->description('Tick exactly what this role should be able to do, grouped by module. Untick everything to make a read-only role.')
                ->schema(function () use ($isSuperAdmin): array {
                    $sections = [];

                    foreach (static::permissionGroups() as $key => $group) {
                        $sections[] = Forms\Components\Section::make($group['label'])
                            ->description($group['description'])
                            ->compact()
                            ->schema([
                                Forms\Components\CheckboxList::make("permissions.{$key}")
                                    ->label('')
                                    ->options($group['permissions'])
                                    ->columns(2)
                                    ->bulkToggleable()
                                    ->disabled($isSuperAdmin)
                                    ->helperText($isSuperAdmin ? 'The super admin role bypasses all permission checks and cannot be edited.' : null),
                            ]);
                    }

                    return $sections;
                })->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['users', 'permissions']))
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('users_count')->label('Users')->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')->label('Permissions')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated')->dateTime('d M Y, H:i')->toggleable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Role $record): bool => $record->name !== 'super_admin')
                    ->disabled(fn (Role $record): bool => ! self::canDelete($record))
                    ->tooltip(fn (Role $record): ?string => self::deleteBlockReason($record) ?: null),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
