<?php

namespace App\Filament\Resources\Colleges\Schemas;

use App\Filament\Forms\ContentEditor;
use App\Filament\Resources\Colleges\CollegeResource;
use App\Models\College;
use App\Support\Sdg;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * One tab per section of the public college page (same order as the page),
 * so a dean's office can fill the page top to bottom. Empty sections are hidden on the site.
 */
class CollegeForm
{
    public static function configure(Schema $schema): Schema
    {
        $isManager = fn (): bool => CollegeResource::managesAllColleges();
        $sdgs = fn (string $name = 'sdgs') => Select::make($name)->label('SDGs')->options(fn () => Sdg::options())->multiple();
        $image = fn (string $name, string $dir = 'academics') => FileUpload::make($name)
            ->image()->imageResizeMode('contain')->imageResizeTargetWidth('1920')->imageResizeTargetHeight('1920')->imageResizeUpscale(false)->disk('public')->directory($dir)->visibility('public')->maxSize(5120);

        return $schema->components([
            Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Header')->schema([
                    Section::make()->columns(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => blank($get('slug')) ? $set('slug', Str::slug($state)) : null),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->disabled(fn () => ! $isManager())
                            ->helperText('Web address: /colleges/<slug>. Changing it breaks old links.'),
                        TextInput::make('short_name')->placeholder('e.g. CIT')->maxLength(20)
                            ->helperText('Used in headings such as "What you can study at CIT".'),
                        Select::make('level')->options(fn () => \App\Models\Term::options('level'))->required(),
                        TextInput::make('headline')
                            ->maxLength(120)
                            ->columnSpanFull()
                            ->placeholder('e.g. Forming teachers who light the way')
                            ->helperText('Big title at the top of the page. Empty: the college name.'),
                        Textarea::make('tagline')
                            ->label('Short description')
                            ->rows(2)->maxLength(300)->columnSpanFull()
                            ->helperText('One or two sentences under the title.'),
                        TextInput::make('email')->email()
                            ->helperText('Receives "Ask about this program" emails.'),
                    ]),
                    Section::make('Images')->columns(2)->schema([
                        $image('banner_image')->label('Banner photo')->imageEditor()
                            ->helperText('Landscape photo, at least 1600 px wide. Max 5 MB.'),
                        $image('seal_image')->label('College seal / logo'),
                    ]),
                ]),

                Tab::make('Dean')->schema([
                    Section::make()->columns(2)->schema([
                        TextInput::make('dean_name')->label('Name'),
                        TextInput::make('dean_title')->label('Title')->placeholder('Dean, College of ...'),
                        $image('dean_photo', 'academics/people')->label('Photo')->avatar()->imageEditor(),
                        Textarea::make('dean_message')->label("Dean's message")->rows(8)->columnSpanFull(),
                    ]),
                ]),

                Tab::make('About')->schema([
                    Textarea::make('overview')->rows(3),
                    Textarea::make('vision')->rows(3),
                    Textarea::make('mission')->rows(4),
                    ContentEditor::make('goals')->helperText('A bulleted list works best.'),
                    ContentEditor::make('objectives')->helperText('A numbered list works best.'),
                    Section::make('Video')->columns(2)->schema([
                        TextInput::make('video_url')->label('YouTube link')->url()->placeholder('https://www.youtube.com/watch?v=...'),
                        TextInput::make('video_caption')->label('Caption')->placeholder('Get to know the college'),
                    ]),
                ]),

                Tab::make('Research')->schema([
                    Section::make('Research cluster')->columns(2)->schema([
                        TextInput::make('research_cluster')->label('Cluster acronym')->placeholder('e.g. TANGLAW'),
                        TextInput::make('research_cluster_name')->label('Full name'),
                        Textarea::make('research_description')->label('What the cluster studies')->rows(3)->columnSpanFull(),
                        TextInput::make('research_coordinator'),
                        $image('research_emblem')->label('Cluster emblem'),
                        TextInput::make('research_agenda_url')->label('Research agenda link')->url(),
                        TextInput::make('research_repository_url')->label('Research repository link')->url(),
                    ]),
                    Repeater::make('research_highlights')
                        ->label('Research highlights')
                        ->schema([
                            TextInput::make('title')->required(),
                            Textarea::make('description')->rows(2),
                            TextInput::make('url')->label('Link')->url(),
                        ])
                        ->itemLabel(fn (array $state) => $state['title'] ?? null)
                        ->collapsible()->defaultItems(0)->addActionLabel('Add highlight'),
                    Repeater::make('focus_areas')
                        ->label('Research focus areas')
                        ->helperText('E.g. one per program or research theme, with the topics and SDGs it covers.')
                        ->schema([
                            TextInput::make('area')->required(),
                            Textarea::make('topics')->rows(3)->helperText('One topic per line.'),
                            $sdgs(),
                        ])
                        ->itemLabel(fn (array $state) => $state['area'] ?? null)
                        ->collapsible()->defaultItems(0)->addActionLabel('Add focus area'),
                ]),

                Tab::make('Partners')->schema([
                    Textarea::make('partners_intro')->label('Introduction')->rows(2),
                    \App\Filament\Forms\PartnerFields::repeater('partners', 'Partner institutions')
                        ->helperText('Also listed on the Global Engagement site.'),
                    Repeater::make('linkages')
                        ->label('Academic, government and professional linkages')
                        ->helperText('Shorter list under the partner cards.')
                        ->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('description'),
                            TextInput::make('url')->label('Link')->url(),
                        ])
                        ->columns(3)
                        ->itemLabel(fn (array $state) => $state['name'] ?? null)
                        ->collapsible()->defaultItems(0)->addActionLabel('Add linkage'),
                ]),

                Tab::make('For Students')->schema([
                    Repeater::make('student_benefits')
                        ->label('What these linkages mean for students')
                        ->helperText('Up to four cards, e.g. "Ready for the boards", "Student research".')
                        ->schema([
                            TextInput::make('title')->required(),
                            Textarea::make('description')->rows(2),
                            TextInput::make('url')->label('"Read more" link')->url(),
                        ])
                        ->itemLabel(fn (array $state) => $state['title'] ?? null)
                        ->maxItems(4)->collapsible()->defaultItems(0)->addActionLabel('Add card'),
                ]),

                Tab::make('Extension & SDGs')->schema([
                    Textarea::make('sdg_summary')->label('How the college serves the SDGs')->rows(3),
                    Repeater::make('extension_projects')
                        ->label('Extension projects')
                        ->schema([
                            TextInput::make('title')->required(),
                            TextInput::make('year'),
                            $sdgs(),
                            TextInput::make('url')->label('Story link')->url(),
                            Textarea::make('description')->rows(2)->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->itemLabel(fn (array $state) => $state['title'] ?? null)
                        ->collapsible()->defaultItems(0)->addActionLabel('Add project'),
                ]),

                Tab::make('Facilities')->schema([
                    ContentEditor::make('facilities'),
                ]),

                Tab::make('Publishing')->schema([
                    Section::make()->columns(2)->schema([
                        Select::make('status')
                            ->options(['draft' => 'Draft', 'published' => 'Published'])
                            ->required()
                            ->default('draft'),
                        TextInput::make('sort_order')
                            ->numeric()->default(0)
                            ->disabled(fn () => ! $isManager())
                            ->helperText('Lower numbers come first in menus and on the home page.'),
                        Textarea::make('meta_description')
                            ->label('Search engine description')
                            ->rows(2)->maxLength(300)->columnSpanFull()
                            ->helperText('Shown by Google under the page title. Empty: the short description is used.'),
                    ]),
                ]),
            ]),
        ]);
    }
}
