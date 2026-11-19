<?php

namespace App\Filament\Resources\Colleges\RelationManagers;

use App\Filament\Forms\ContentEditor;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class DepartmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'departments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set, $get) => blank($get('slug')) ? $set('slug', Str::slug($state)) : null),
            TextInput::make('slug')->required()->alphaDash()->unique(ignoreRecord: true),
            Textarea::make('overview')->rows(4)->columnSpanFull(),
            ContentEditor::make('achievements')->columnSpanFull(),
            Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->default('published')->required(),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->description('Programs, faculty and documents are linked to a department from their own screens.')
            ->columns([
                TextColumn::make('name')->searchable()->weight('bold'),
                TextColumn::make('programs_count')->counts('programs')->label('Programs'),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => ucfirst($state))->color(fn ($state) => $state === 'published' ? 'primary' : 'gray'),
            ])
            ->defaultSort('sort_order')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
