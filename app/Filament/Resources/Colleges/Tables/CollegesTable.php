<?php

namespace App\Filament\Resources\Colleges\Tables;

use App\Models\College;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollegesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('seal_image')->disabledClick()->label('')->disk('public')->circular()->imageSize(36),
                TextColumn::make('name')->searchable()->sortable()->weight('bold')
                    ->description(fn (College $record) => $record->dean_name),
                TextColumn::make('level')->badge()->formatStateUsing(fn ($state) => \App\Models\Term::label('level', $state))->visibleFrom('md'),
                TextColumn::make('programs_count')->counts('programs')->label('Programs')->visibleFrom('md'),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => ucfirst($state))->color(fn ($state) => $state === 'published' ? 'primary' : 'gray'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order', fn () => \App\Filament\Resources\Colleges\CollegeResource::managesAllColleges())
            ->recordActions([
                Action::make('view')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (College $record) => route('academics.college', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (College $record) => $record->status === 'published'),
                EditAction::make(),
            ]);
    }
}
