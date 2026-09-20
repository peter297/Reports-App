<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

// use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $slug = 'organization-users';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true),

                Forms\Components\Select::make('branch')
                    ->options([
                        'Juja Road' => 'Juja Road',
                        'Kitisuru' => 'Kitisuru',
                        'South C' => 'South C',
                    ])
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('line_manager_id')
                    ->label('Direct Line Manager')
                    ->relationship('lineManager', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),

                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn (): bool => auth()->user()?->hasUnrestrictedAccess() ?? false)
                    ->dehydrated(fn (): bool => auth()->user()?->hasUnrestrictedAccess() ?? false)
                    ->live()
                    ->options(function (Forms\Get $get): array {
                        $rolesByBranch = [
                            'Juja Road' => [
                                'Deputy HeadTeacher',
                                'Dean of Islamic Studies',
                                'Coordinators',
                                'School Administrator',
                                'Head of Admissions',
                                'Teachers',
                            ],
                            'Kitisuru' => [
                                'Deputy Principal',
                                'Dean of Islamic Studies',
                                'Coordinators',
                                'School Administrator',
                                'Fleet Manager',
                                'Teachers',
                            ],
                            'South C' => [
                                'Deputy HeadTeacher',
                                'Dean of Islamic Studies',
                                'Coordinators',
                                'School Administrator',
                                'Teachers',
                            ],
                        ];

                        $roles = array_merge(
                            $rolesByBranch[$get('branch')] ?? [],
                            ['CEO', 'Principal', 'HeadTeacher', 'ICT Department', 'Admin', 'super_admin'],
                        );

                        return Role::query()
                            ->whereIn('name', array_unique($roles))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    }),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('branch')->sortable(),
                Tables\Columns\TextColumn::make('lineManager.name')->label('Line Manager'),
                Tables\Columns\TextColumn::make('created_at')
                    ->date(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
