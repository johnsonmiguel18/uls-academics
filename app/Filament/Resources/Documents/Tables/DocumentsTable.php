<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->weight('bold')->wrap(),
                TextColumn::make('category')->badge()->color('gray')->formatStateUsing(fn ($state) => \App\Models\Term::label('document_category', $state)),
                TextColumn::make('college.name')->label('College')->placeholder('University-wide')->wrap()->visibleFrom('md'),
                IconColumn::make('is_public')->label('Public')->boolean(),
                TextColumn::make('updated_at')->label('Updated')->date('M d, Y')->sortable()->visibleFrom('lg'),
            ])
            ->filters([SelectFilter::make('category')->options(fn () => \App\Models\Term::options('document_category'))])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Action::make('open')->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                    ->url(fn (Document $record) => Storage::disk('public')->url($record->file))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
