<?php

namespace App\Filament\Resources\FacultyMembers\Tables;

use App\Filament\Resources\FacultyMembers\FacultyMemberResource;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FacultyMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')->disabledClick()->label('')->disk('public')->circular()->imageSize(36)
                    ->defaultImageUrl(fn () => \App\Support\Site::logo()),
                TextColumn::make('name')->searchable()->sortable()->weight('bold')->description(fn ($record) => $record->position),
                TextColumn::make('college.name')->label('College')->wrap()->visibleFrom('md')
                    ->visible(fn () => FacultyMemberResource::managesAllColleges()),
                TextColumn::make('department.name')->label('Department')->placeholder('None')->visibleFrom('lg'),
                IconColumn::make('is_published')->label('On website')->boolean(),
            ])
            ->filters([
                SelectFilter::make('college')->relationship('college', 'name')
                    ->visible(fn () => FacultyMemberResource::managesAllColleges()),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()]);
    }
}
