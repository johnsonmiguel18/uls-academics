<?php

namespace App\Filament\Resources\Programs\Tables;

use App\Filament\Resources\Programs\ProgramResource;
use App\Models\College;
use App\Models\Program;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(['name', 'code', 'majors'])->sortable()->weight('bold')->wrap()
                    ->description(fn (Program $record) => $record->majors ? 'Majors: ' . $record->majors : null),
                TextColumn::make('college.name')->label('College')->sortable()->wrap()->visibleFrom('md')
                    ->visible(fn () => ProgramResource::managesAllColleges()),
                TextColumn::make('degree_type')->label('Degree')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => \App\Models\Term::label('degree_type', $state))->visibleFrom('xl'),
                IconColumn::make('is_featured')->label('Featured')->boolean()->visibleFrom('xl'),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => ucfirst($state))->color(fn ($state) => $state === 'published' ? 'primary' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('college')->relationship('college', 'name')
                    ->visible(fn () => ProgramResource::managesAllColleges()),
                SelectFilter::make('level')->options(fn () => \App\Models\Term::options('level')),
                SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
            ])
            ->defaultSort('name')
            ->defaultPaginationPageOption(25)
            ->recordActions([
                Action::make('view')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (Program $record) => route('academics.program', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Program $record) => $record->status === 'published'),
                EditAction::make(),
            ]);
    }
}
