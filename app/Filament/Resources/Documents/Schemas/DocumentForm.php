<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                DocumentResource::collegeSelect(required: false)->placeholder('University-wide'),
                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name', fn (Builder $query, Get $get) => $query->where('college_id', $get('college_id') ?? 0))
                    ->placeholder('None'),
                Select::make('category')->options(fn () => \App\Models\Term::options('document_category'))->required(),
                Toggle::make('is_public')->label('Downloadable on the website')->default(true)->inline(false),
                FileUpload::make('file')
                    ->required()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/*',
                    ])
                    ->maxSize(20480)
                    ->disk('public')->directory('academics/documents')->visibility('public')
                    ->preserveFilenames()
                    ->downloadable()
                    ->columnSpanFull()
                    ->helperText('PDF, Word, Excel or image, up to 20 MB. Files marked downloadable are public to anyone with the link.'),
            ]),
        ]);
    }
}
