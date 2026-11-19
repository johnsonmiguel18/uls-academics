<?php

namespace App\Filament\Resources\News\Schemas;

use App\Filament\Forms\ContentEditor;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Models\News;
use Illuminate\Support\Str;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        $canPublish = fn (): bool => auth()->user()?->can('publish_news') ?? false;

        return $schema->components([
            Section::make('Article Details')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Select::make('category')
                        ->options(fn () => \App\Models\Term::options('news_category'))
                        ->required()
                        ->default('college-news'),

                    // Tagging a college shows the story on that college's Academics page.
                    \App\Filament\Resources\Colleges\CollegeResource::collegeSelect(required: false)
                        ->placeholder('None (university-wide)'),

                    Select::make('sdgs')
                        ->label('Sustainable Development Goals')
                        ->options(fn () => \App\Support\Sdg::options())
                        ->multiple()
                        ->helperText('Tagged stories are counted in the SDG grid of the college page.'),

                    Textarea::make('excerpt')
                        ->label('Short Summary')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),

                    ContentEditor::make('content')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Featured Image')
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

            Section::make('Publishing')
                ->description('Drafts and stories in review are only visible here. Scheduled stories go live by themselves.')
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
                        ->inline()
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
                ])
                ->columns(2),
        ]);
    }
}
