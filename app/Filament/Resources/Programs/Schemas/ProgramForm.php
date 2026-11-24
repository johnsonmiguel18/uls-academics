<?php

namespace App\Filament\Resources\Programs\Schemas;

use App\Filament\Forms\ContentEditor;
use App\Filament\Resources\Programs\ProgramResource;
use App\Models\Term;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Program')->schema([
                    Section::make()->columns(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => blank($get('slug')) ? $set('slug', Str::slug($state)) : null),
                        TextInput::make('slug')->required()->alphaDash()->unique(ignoreRecord: true)
                            ->helperText('Web address: /programs/<slug>'),
                        ProgramResource::collegeSelect(),
                        Select::make('department_id')
                            ->label('Department')
                            ->relationship('department', 'name', fn (Builder $query, Get $get) => $query->where('college_id', $get('college_id') ?? 0))
                            ->placeholder('None'),
                        Select::make('level')->options(fn () => Term::options('level'))->required(),
                        Select::make('degree_type')->options(fn () => Term::options('degree_type'))->required(),
                        TextInput::make('code')->placeholder('e.g. BSIT')->maxLength(20),
                        TextInput::make('duration')->placeholder('e.g. 4 years')->maxLength(50),
                        TextInput::make('majors')->placeholder('Comma-separated, if any')->columnSpanFull(),
                        Textarea::make('summary')
                            ->rows(2)->maxLength(300)->columnSpanFull()
                            ->helperText('One or two sentences for program cards and search results.'),
                    ]),
                    Section::make('Images and files')->columns(2)->schema([
                        FileUpload::make('image')
                            ->label('Program photo')
                            ->image()->imageResizeMode('contain')->imageResizeTargetWidth('1920')->imageResizeTargetHeight('1920')->imageResizeUpscale(false)->imageEditor()->maxSize(5120)
                            ->disk('public')->directory('academics/programs')->visibility('public'),
                        FileUpload::make('curriculum_file')
                            ->label('Curriculum / prospectus (PDF)')
                            ->acceptedFileTypes(['application/pdf'])->maxSize(10240)
                            ->disk('public')->directory('academics/curricula')->visibility('public')
                            ->downloadable(),
                    ]),
                ]),
                Tab::make('Overview & Objectives')->schema([
                    ContentEditor::make('overview'),
                    ContentEditor::make('objectives')->label('Program objectives and outcomes'),
                ]),
                Tab::make('Curriculum & Careers')->schema([
                    ContentEditor::make('curriculum')->helperText('Subjects per year level, or a short summary next to the PDF.'),
                    ContentEditor::make('careers')->label('Career opportunities'),
                ]),
                Tab::make('Admission')->schema([
                    ContentEditor::make('admission_requirements')->label('Admission requirements (not shown)')
                        ->helperText('Kept for reference only. Program pages link to the official admission page instead (Site settings → General → Admission requirements link).'),
                ]),
                Tab::make('FAQs')->schema([
                    Repeater::make('faqs')
                        ->label('Frequently asked questions')
                        ->schema([
                            TextInput::make('question')->required(),
                            Textarea::make('answer')->required()->rows(3),
                        ])
                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                        ->collapsible()
                        ->reorderable()
                        ->defaultItems(0),
                ]),
                Tab::make('Publishing')->schema([
                    Section::make()->columns(2)->schema([
                        Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->required()->default('draft'),
                        TextInput::make('sort_order')->numeric()->default(0),
                        Toggle::make('is_featured')->label('Feature on the Academics page'),
                        Textarea::make('meta_description')->label('Search engine description')->rows(2)->maxLength(300)->columnSpanFull(),
                    ]),
                ]),
            ]),
        ]);
    }
}
