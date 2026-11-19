<?php

namespace App\Filament\Resources\Programs\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccreditationsRelationManager extends RelationManager
{
    protected static string $relationship = 'accreditations';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('agency')->required()->placeholder('PAASCU, PACUCOA, CHED...')->datalist(['PAASCU', 'PACUCOA', 'CHED', 'AACCUP']),
            TextInput::make('level')->required()->placeholder('e.g. Level III Re-accredited'),
            DatePicker::make('valid_until'),
            FileUpload::make('certificate')
                ->acceptedFileTypes(['application/pdf', 'image/*'])->maxSize(10240)
                ->disk('public')->directory('academics/accreditations')->visibility('public')->downloadable(),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('agency')
            ->columns([
                TextColumn::make('agency')->weight('bold'),
                TextColumn::make('level'),
                TextColumn::make('valid_until')->date('M Y')
                    ->color(fn ($record) => $record->valid_until?->isPast() ? 'danger' : null),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
