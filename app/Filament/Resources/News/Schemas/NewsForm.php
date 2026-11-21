<?php

namespace App\Filament\Resources\News\Schemas;

use App\Filament\Forms\ContentEditor;
use App\Filament\Resources\Colleges\CollegeResource;
use App\Models\News;
use App\Models\Term;
use App\Support\Sdg;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        $canPublish = fn (): bool => auth()->user()?->can('publish_news') ?? false;

        // Page-builder layout in three panes: recent stories on the left, the article on a canvas in the middle
        // with an Edit / Preview toolbar, settings in tabs on the right. Fields and rules are unchanged.
        // Below 1280 px the story list is hidden; below 1024 px the panes stack.
        return $schema->columns(['default' => 1, 'lg' => 12])->components([
            // Left: recent stories and "New story"
            View::make('filament.news.story-list')
                ->viewData(fn (?News $record): array => ['story' => $record])
                ->columnSpan(['default' => 1, 'lg' => 12, 'xl' => 3]),

            // Middle: the article itself, on a canvas
            Group::make([
                View::make('filament.news.canvas-toolbar')
                    ->viewData(fn (?News $record): array => ['story' => $record]),

                Group::make([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->label('Headline'),

                    Textarea::make('excerpt')
                        ->label('Short Summary')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),

                    ContentEditor::make('content')
                        ->required()
                        ->columnSpanFull(),
                ])
                    ->extraAttributes(['class' => 'uls-pe-paper', 'x-show' => "mode === 'edit'"]),
            ])
                ->columnSpan(['default' => 1, 'lg' => 8, 'xl' => 6])
                ->extraAttributes(['class' => 'uls-pe-canvas', 'x-data' => "{ mode: 'edit', device: 'desktop' }"]),

            // Right: settings, like an inspector panel
            Tabs::make('Settings')
                ->tabs([
                    Tab::make('Publishing')
                        ->schema([
                            ToggleButtons::make('status')
                                ->label('Status')
                                ->options([
                                    'draft' => 'Draft',
                                    'review' => 'Submit for review',
                                    'published' => 'Publish now',
                                    'scheduled' => 'Schedule',
                                    'archived' => 'Archive',
                                ])
                                ->icons([
                                    'draft' => Heroicon::OutlinedPencilSquare,
                                    'review' => Heroicon::OutlinedEye,
                                    'published' => Heroicon::OutlinedRocketLaunch,
                                    'scheduled' => Heroicon::OutlinedCalendarDays,
                                    'archived' => Heroicon::OutlinedArchiveBox,
                                ])
                                ->colors([
                                    'draft' => 'gray',
                                    'review' => 'warning',
                                    'published' => 'success',
                                    'scheduled' => 'info',
                                    'archived' => 'gray',
                                ])
                                ->default('draft')
                                ->required()
                                ->live()
                                // Without publishing permission: write and submit for review only.
                                ->disableOptionWhen(fn (string $value): bool => ! $canPublish() && ! in_array($value, ['draft', 'review']))
                                ->in(fn (): array => $canPublish() ? array_keys(News::STATUSES) : ['draft', 'review'])
                                ->columnSpanFull(),

                            DateTimePicker::make('published_at')
                                ->label(fn (Get $get): string => $get('status') === 'scheduled' ? 'Publish on' : 'Publication date')
                                ->seconds(false)
                                ->native(false)
                                ->required(fn (Get $get): bool => $get('status') === 'scheduled')
                                ->after(fn (Get $get, ?News $record): ?string => $get('status') === 'scheduled' && $record?->published_at?->isPast() !== true ? 'now' : null)
                                ->visible(fn (Get $get): bool => in_array($get('status'), ['published', 'scheduled']))
                                ->disabled(fn (): bool => ! $canPublish())
                                ->helperText(fn (Get $get): string => $get('status') === 'scheduled'
                                    ? 'The story goes live at this date and time (Philippine time).'
                                    : 'Leave empty to publish right away.'),

                            DateTimePicker::make('unpublish_at')
                                ->label('Take down on (optional)')
                                ->seconds(false)
                                ->native(false)
                                ->after(fn (Get $get): string => filled($get('published_at')) ? 'published_at' : 'now')
                                ->visible(fn (Get $get): bool => in_array($get('status'), ['published', 'scheduled']))
                                ->disabled(fn (): bool => ! $canPublish())
                                ->helperText('For announcements with an end date, e.g. enrollment deadlines.'),

                            Toggle::make('is_featured')
                                ->label('Feature this article')
                                ->default(false)
                                ->disabled(
                                    // Featured = the homepage spotlight; college admins cannot take it.
                                    fn (): bool => ! $canPublish()
                                        || (auth()->user()?->hasRole('college_admin') && ! auth()->user()->can('manage_academics'))
                                )
                                ->helperText('Only users with publishing permission can feature articles.'),
                        ]),

                    Tab::make('Details')
                        ->schema([
                            TextInput::make('slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),

                            Select::make('category')
                                ->options(fn () => Term::options('news_category'))
                                ->required()
                                ->default('college-news'),

                            // Tagging a college shows the story on that college's Academics page.
                            CollegeResource::collegeSelect(required: false)
                                ->placeholder('None (university-wide)'),

                            Select::make('sdgs')
                                ->label('Sustainable Development Goals')
                                ->options(fn () => Sdg::options())
                                ->multiple()
                                ->helperText('Tagged stories are counted in the SDG grid of the college page.'),
                        ]),

                    Tab::make('Image')
                        ->schema([
                            FileUpload::make('featured_image')
                                ->label('Article Image')
                                ->image()
                                // Large photos are shrunk in the browser before upload (max 1920 px).
                                ->imageResizeMode('contain')->imageResizeTargetWidth('1920')->imageResizeTargetHeight('1920')->imageResizeUpscale(false)
                                ->disk('public')
                                ->directory('news-images')
                                ->visibility('public')
                                ->imageEditor()
                                ->imagePreviewHeight('250')
                                ->maxSize(5120)
                                ->helperText(
                                    'Recommended: landscape image. Maximum file size: 5 MB.'
                                ),
                        ]),
                ])
                ->columnSpan(['default' => 1, 'lg' => 4, 'xl' => 3])
                ->extraAttributes(['class' => 'uls-pe-inspector']),
        ]);
    }
}
