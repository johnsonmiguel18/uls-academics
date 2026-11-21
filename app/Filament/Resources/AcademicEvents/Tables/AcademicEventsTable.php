<?php

namespace App\Filament\Resources\AcademicEvents\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AcademicEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->weight('bold')->wrap()->description(fn ($record) => $record->location),
                TextColumn::make('college.name')->label('College')->placeholder('University-wide')->wrap()->visibleFrom('xl'),
                TextColumn::make('starts_at')->label('Starts')->dateTime('M d, Y g:i A')->sortable(),
                IconColumn::make('is_published')->label('On website')->boolean(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
