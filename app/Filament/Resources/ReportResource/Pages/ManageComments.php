<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class ManageComments extends ManageRelatedRecords
{
    protected static string $resource = ReportResource::class;

    protected static string $relationship = 'comments';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    public function getTitle(): string | Htmlable
    {
        $recordTitle = $this->getRecordTitle();

        $recordTitle = $recordTitle instanceof Htmlable ? $recordTitle->toHtml() : $recordTitle;

        return "Manage {$recordTitle} Comments";
    }

    public function getBreadcrumb(): string
    {
        return 'Comments';
    }

    public static function getNavigationLabel(): string
    {
        return 'Manage Comments';
    }

    public function form(Form $form): Form
    {
        $currentUserId = Auth::id();
        return $form
            ->schema([
                // Select the report based on the report_id and current logged-in user_id
                Forms\Components\Select::make('report_id')
                    ->relationship('report', 'subject')
                    ->label('Report Subject')
                    ->options(function () {
                        return \App\Models\Report::where('user_id', auth()->id())
                            ->pluck('subject', 'id');
                    })
                    ->searchable()
                    ->required(),

                // Hidden field for user_id to store the logged-in user's ID
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),

                // Display the logged-in user's name in a disabled text input
                Forms\Components\TextInput::make('user_name')
                    ->label('Commenting User')
                    ->default(auth()->user()->name)
                    ->disabled(),

                Forms\Components\MarkdownEditor::make('comment_text')
                    ->required()
                    ->label('Comment Content'),

                Forms\Components\Toggle::make('is_visible')
                    ->label('Approved for Public')
                    ->default(true),
            ])
            ->columns(1);
    }






    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                // No 'title' field in comments, remove this
                TextEntry::make('user.name')
                    ->label('User Name'),
                    
                TextEntry::make('comment_text')
                    ->markdown()
                    ->label('Comment Content'),

                IconEntry::make('is_visible')
                    ->label('Visibility'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                // Display the subject of the report
                Tables\Columns\TextColumn::make('report.subject')
                    ->label('Report Subject')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Sent By')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('comment_text')
                    ->label('Comment')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visibility')
                    ->sortable(),
            ])
            ->filters([
                // You can add filters here if needed
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->groupedBulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
