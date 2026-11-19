<?php

namespace App\Filament\Resources\AcademicEvents\Schemas;

use App\Filament\Resources\AcademicEvents\AcademicEventResource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AcademicEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                // Empty college = university-wide event (only academics managers can pick that).
                AcademicEventResource::collegeSelect(required: false)->placeholder('University-wide'),
                TextInput::make('location'),
                DateTimePicker::make('starts_at')->required()->seconds(false)->native(false),
                DateTimePicker::make('ends_at')->seconds(false)->native(false)->after('starts_at'),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                TextInput::make('link')->url()->placeholder('https:// registration or details page')->columnSpanFull(),
                Toggle::make('is_published')->label('Show on the website')->default(true),
            ]),
        ]);
    }
}
