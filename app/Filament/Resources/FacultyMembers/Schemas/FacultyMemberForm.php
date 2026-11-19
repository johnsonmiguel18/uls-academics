<?php

namespace App\Filament\Resources\FacultyMembers\Schemas;

use App\Filament\Resources\FacultyMembers\FacultyMemberResource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class FacultyMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->required()->maxLength(255)->helperText('With degrees, as it should appear, e.g. "Juan Dela Cruz, PhD".'),
                TextInput::make('position')->placeholder('Dean, Program Chair, Instructor...'),
                FacultyMemberResource::collegeSelect(),
                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name', fn (Builder $query, Get $get) => $query->where('college_id', $get('college_id') ?? 0))
                    ->placeholder('None'),
                TextInput::make('specialization'),
                TextInput::make('email')->email(),
                FileUpload::make('photo')
                    ->image()->imageResizeMode('contain')->imageResizeTargetWidth('1920')->imageResizeTargetHeight('1920')->imageResizeUpscale(false)->avatar()->imageEditor()->maxSize(2048)
                    ->disk('public')->directory('academics/people')->visibility('public'),
                Textarea::make('bio')->rows(4),
                Toggle::make('is_published')->label('Show on the website')->default(true),
                TextInput::make('sort_order')->numeric()->default(0)->helperText('Lower numbers first; deans usually 0.'),
            ]),
        ]);
    }
}
