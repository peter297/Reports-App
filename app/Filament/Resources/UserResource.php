<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

// use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $slug = 'organization-users';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasSystemAccess() ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    /**
     * Deleting a user must never orphan audit data. Reports and recorded
     * syllabus lesson progress are preserved; deactivating the account is
     * the correct remedy for those users.
     */
    public static function canDeleteUser(User $record): bool
    {
        return static::deleteBlockReason($record) === '';
    }

    public static function deleteBlockReason(User $record): string
    {
        if ($record->id === auth()->id()) {
            return 'You cannot delete your own account.';
        }

        if ($record->hasRole('super_admin')) {
            return 'Super admin accounts cannot be deleted.';
        }

        if (\App\Models\Report::query()->where('user_id', $record->id)->exists()) {
            return 'This user has submitted reports and cannot be deleted. Deactivate the account instead.';
        }

        if (\App\Models\SyllabusLessonProgress::query()->where('teacher_id', $record->id)->exists()) {
            return 'This user has recorded syllabus lesson progress and cannot be deleted. Deactivate the account instead.';
        }

        return '';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required(),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Organization')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Leave empty for school-wide roles (CEO, Principal, Deputy Principal, Head Teacher). Branch-scoped roles must select their branch.'),

                        Forms\Components\Select::make('line_manager_id')
                            ->label('Direct Line Manager')
                            ->relationship('lineManager', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Used to derive the reporting hierarchy. Multiple recipients can be configured in Reporting Assignments.'),
                    ])->columns(2),

                Forms\Components\Section::make('Roles')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->visible(fn (): bool => auth()->user()?->hasSystemAccess() ?? false)
                            ->dehydrated(fn (): bool => auth()->user()?->hasSystemAccess() ?? false)
                            ->options(fn (): array => Role::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->helperText('A user may hold multiple roles where authorized. Branch-specific roles require a branch above.'),

                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Direct permissions (in addition to their roles)')
                            ->options(fn (): array => collect(\App\Filament\Resources\RoleResource::permissionGroups())
                                ->flatMap(fn (array $group) => collect($group['permissions'])
                                    ->mapWithKeys(fn (string $label, string $name) => [$name => $group['label'] . ' · ' . $label]))
                                ->all())
                            ->columns(2)
                            ->bulkToggleable()
                            ->searchable()
                            ->visible(fn (): bool => auth()->user()?->hasSystemAccess() ?? false)
                            ->dehydrated(fn (): bool => auth()->user()?->hasSystemAccess() ?? false)
                            ->helperText('Rarely needed. Prefer roles. Anything ticked here is granted in addition to the user\'s roles. Changes take effect immediately.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('branch.name')->label('Branch')->sortable()->placeholder('All branches'),
                Tables\Columns\TextColumn::make('roles.name')->label('Roles')->badge()->separator(','),
                Tables\Columns\TextColumn::make('lineManager.name')->label('Line Manager'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn (): array => Branch::orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Role'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (User $record): bool => ! static::canDeleteUser($record))
                    ->tooltip(fn (User $record): ?string => static::deleteBlockReason($record) ?: null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->disabled(fn (?Collection $records): bool => $records !== null && $records->contains(fn (User $record): bool => ! static::canDeleteUser($record)))
                        ->tooltip('One or more selected users cannot be deleted (yourself, a super admin, or someone with reports or lesson progress). Deactivate their accounts instead.'),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
