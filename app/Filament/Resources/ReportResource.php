<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use App\Models\Report;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use App\Filament\Resources\ReportResource\Pages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Infolists\Components;
use Filament\Forms\Components\SpatieTagsEntry;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    //protected static ?string $navigationGroup= 'Admin Reports';

    protected static ?string $slug= 'organization-reports';

    protected static ?string $recordTitleAttribute = 'title';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::count();
    // }

    public static function form(Form $form): Form
    {
        $currentUserId = Auth::id();
        return $form
            ->schema([
                Forms\Components\Section::make('Report Details')
                    ->schema([
                        Select::make('category')
                            ->required()
                            ->label('Category')
                            ->options([
                                'General Reports' => 'General Reports',
                                'Academic Reports' => 'Academic Reports',
                                'Admin Reports' => 'Admin Reports',
                            ])
                            ->placeholder('Select a Category'),


                        TextInput::make('subject')
                            ->required()
                            ->label('Subject'),
                    ])->columns(2),

                Forms\Components\Section::make('Report Summary')
                    ->schema([
                        Forms\Components\MarkdownEditor::make('summary')
                            ->required()
                            ->columnSpan('full'),
                    ])->columns(1),

                Forms\Components\Section::make('Report Recipients & Attachments')
                    ->schema([

                        Select::make('recipients')
                            ->relationship('recipients', 'name')
                            ->multiple()
                            ->preload()
                            ->label('Recipients')
                            ->options(function () use ($currentUserId) {
                                return \App\Models\User::whereKey(
                                    \App\Models\User::find($currentUserId)?->line_manager_id
                                )
                                    ->pluck('name', 'id');
                            }),

                        Forms\Components\FileUpload::make('file_paths')
                            ->preserveFilenames()
                            ->directory('attachments')
                            ->getUploadedFileNameForStorageUsing(function(TemporaryUploadedFile $file): string{
                                return (string) str($file->getClientOriginalName())->prepend(now()->timestamp);
                            })
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->openable()
                            ->downloadable()
                            ->label('Attachments')
                            ->disk('public')
                            ->uploadingMessage('Uploading attachment...')
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            TextColumn::make('category')
                ->sortable()
                ->searchable()
                ->label('Category'),

            TextColumn::make('subject')
                ->sortable()
                ->searchable()
                ->label('Subject'),

            TextColumn::make('user.name')
                ->label('Sender')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('recipients.name')
                ->label('Recipients')
                ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('created_at')
                ->label('Created Date')
                ->sortable()
                ->color('success')
                ->badge()
                ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('M d, Y'))
                ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('updated_at')
                ->label('Updated Date')
                ->sortable()
                ->color('success')
                ->badge()
                ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('M d, Y'))
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('file_paths')
                ->formatStateUsing(function (Report $record) {
                    return collect($record->file_paths)->map(function ($path) {
                        $filename = basename($path);
                        // Remove the random numbers before the filename using regex
                        $cleanFilename = preg_replace('/^\d+/', '', $filename);
                        $cleanFilename = ltrim($cleanFilename, '_ '); // Remove any leading underscores or spaces
                        $url = asset('storage/attachments/' . $filename);
                        return "<a href=\"{$url}\" target=\"_blank\" class=\" bg-blue-500 rounded px-2 py-1 mb-1 mr-1 text-sm hover:underline\">{$cleanFilename}</a>";
                    })->implode(''); // Join badges without additional separators
                })
                ->label('Attachments')
                ->html(),



        ])





            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Report Deleted.')
                            ->body('The Report is Deleted Successfully!')
                    )
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }






    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make()
                    ->schema([
                        Components\Grid::make(2) // Adjust the layout here
                            ->schema([
                                Components\Group::make([
                                    Components\TextEntry::make('category'),
                                    Components\TextEntry::make('subject'),
                                    Components\TextEntry::make('created_at')
                                        ->badge()
                                        ->date()
                                        ->color('success'),
                                ]),
                                Components\Group::make([
                                    Components\TextEntry::make('user.name')
                                        ->label('Report Sender'),
                                    Components\TextEntry::make('recipients.name')
                                        ->label('Report Recipients'),
                                ]),
                            ]),
                    ]),
                Components\Section::make('Report Summary Content')
                    ->schema([
                        Components\TextEntry::make('summary')
                            ->prose()
                            ->markdown()
                            ->hiddenLabel(),
                    ])
                    ->collapsible(),


                Components\Section::make('Attachments')
                    ->schema([
                        Components\TextEntry::make('file_paths')
                            ->label('Attachments')
                            ->formatStateUsing(function (Report $record) {
                                return collect($record->file_paths)->map(function ($path) {
                                    $filename = basename($path);
                                    $cleanFilename = preg_replace('/^\d+/', '', $filename);
                                    $cleanFilename = ltrim($cleanFilename, '_ ');
                                    $url = asset('storage/attachments/' . $filename);
                                    return "<a href=\"{$url}\" target=\"_blank\" class=\"bg-blue-500 rounded px-2 py-1 inline-block mb-1\">{$cleanFilename}</a>";
                                })->implode('<br>');
                            })
                            ->html(),
                    ])
                    ->collapsible(),
            ]);
    }



    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewReport::class,
            Pages\EditReport::class,
            Pages\ManageComments::class,
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
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'comments' => Pages\ManageComments::route('/{record}/comments'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
            'view' => Pages\ViewReport::route('/{record}'),
        ];
    }


}
