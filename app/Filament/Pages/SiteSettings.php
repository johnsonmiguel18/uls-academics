<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Models\Term;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Admin → Site settings: every site-wide text, link, menu, page section and setting of the
 * public site. Saved as one record (site_settings); the site reads it through App\Support\Site.
 * Texts may use placeholders where noted: :college (college name), :short (short name), :count, :year.
 */
class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_academics') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(SiteSetting::query()->first()?->data ?? []);
    }

    public function save(): void
    {
        $setting = SiteSetting::query()->firstOrNew();
        // Keep keys this form does not show (e.g. settings added by a later update).
        $setting->data = array_replace($setting->data ?? [], $this->form->getState());
        $setting->save();

        Notification::make()->title('Site settings saved. The website is updated.')->success()->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label('Save settings')->submit('save')->keyBindings(['mod+s'])])->sticky(),
                ]),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $links = fn (string $name, string $label) => Repeater::make($name)->label($label)
            ->schema([TextInput::make('label')->required(), TextInput::make('url')->required()->label('Link')->placeholder('/programs or https://...')])
            ->columns(2)->reorderable()->collapsible()->defaultItems(0)->itemLabel(fn (array $state) => $state['label'] ?? null);

        $placeholders = 'Placeholders: :college = college name, :short = short name (or full name).';

        return $schema->statePath('data')->components([
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make('General')->icon(Heroicon::OutlinedBuildingOffice2)->schema([
                    Section::make('University and contact')->columns(2)->schema([
                        TextInput::make('contact.site_name')->label('Site name')->required()->helperText('Browser tabs and search results.'),
                        \Filament\Forms\Components\FileUpload::make('contact.logo')->label('Seal / logo')
                            ->image()->disk('public')->directory('site')->visibility('public')->maxSize(1024)
                            ->helperText('Square image, shown in the header, footer and admin. Empty: the bundled ULS seal.'),
                        TextInput::make('contact.university_name')->label('University (legal name)')->required(),
                        TextInput::make('contact.university_short_name')->label('University (short name)'),
                        TextInput::make('contact.address')->label('Address'),
                        TextInput::make('contact.phone')->label('Phone'),
                        TextInput::make('contact.email')->label('Email')->email(),
                        TextInput::make('contact.main_site_url')->label('Main university website')->url(),
                        TextInput::make('contact.enroll_url')->label('Online enrollment link')->url(),
                        TextInput::make('contact.admission_url')->label('Admission requirements link')->url(),
                    ]),
                    Section::make('Screen reader labels')->collapsed()->schema([
                        KeyValue::make('a11y')->hiddenLabel()->keyLabel('Where')->valueLabel('Text')->addable(false)->deletable(false)->editableKeys(false),
                    ]),
                    Section::make('Search engines')->columns(2)->schema([
                        TextInput::make('seo.organization_address')->label('Address shown to search engines'),
                        Textarea::make('seo.default_description')->label('Default page description')->rows(2)->maxLength(300),
                    ]),
                ]),

                Tab::make('Header and footer')->icon(Heroicon::OutlinedBars3)->schema([
                    Section::make('Header')->columns(2)->schema([
                        TextInput::make('header.topbar_link_label')->label('Top bar link to the main website'),
                        Toggle::make('header.show_contact')->label('Show address and phone in the top bar')->inline(false),
                        TextInput::make('header.brand_label')->label('Label next to the logo'),
                        TextInput::make('header.colleges_menu_label')->label('Colleges menu label')->helperText('Lists every published college automatically. Empty: hide the menu.'),
                        TextInput::make('header.search_placeholder')->label('Search box hint')->helperText('Empty: hide the search box.'),
                        TextInput::make('header.enroll_label')->label('Enroll button')->helperText('Links to the online enrollment link. Empty: hide.'),
                        TextInput::make('header.back_to_top')->label('Back-to-top button (screen readers)'),
                        $links('header.menu', 'Menu links')->columnSpanFull(),
                    ]),
                    Section::make('Footer')->schema([
                        Textarea::make('footer.about')->label('About text')->rows(2),
                        TextInput::make('footer.colleges_heading')->label('Heading of the colleges column')->helperText('Empty: hide the column.'),
                        Repeater::make('footer.columns')->label('Link columns')
                            ->schema([TextInput::make('heading')->required(), $links('links', 'Links')])
                            ->reorderable()->collapsible()->defaultItems(0)->itemLabel(fn (array $state) => $state['heading'] ?? null),
                        TextInput::make('footer.copyright')->label('Copyright line')->helperText(':year = current year.'),
                    ]),
                ]),

                Tab::make('Home page')->icon(Heroicon::OutlinedHome)->schema([
                    Section::make('Top of the page')->columns(2)->schema([
                        TextInput::make('home.meta_title')->label('Browser tab title'),
                        Textarea::make('home.meta_description')->label('Search engine description')->rows(2)->maxLength(300),
                        TextInput::make('home.breadcrumb_parent')->label('Breadcrumb link to the main website'),
                        TextInput::make('home.kicker')->label('Small label above the title'),
                        TextInput::make('home.headline')->label('Title')->required()->columnSpanFull(),
                        Textarea::make('home.lead')->label('Introduction')->rows(3)->columnSpanFull(),
                        TextInput::make('home.search_label')->label('Search box label (screen readers)'),
                        TextInput::make('home.search_placeholder')->label('Search box hint'),
                        TextInput::make('home.search_button')->label('Search button'),
                        TextInput::make('home.college_card_link')->label('College card link text'),
                        $links('home.quick_links', 'Quick links under the search box')->columnSpanFull(),
                    ]),
                    Section::make('Numbers')->description('Counted live from the database.')->schema([
                        Repeater::make('home.stats')->hiddenLabel()
                            ->schema([
                                Select::make('metric')->options(fn () => self::homeMetrics())->required(),
                                TextInput::make('label')->required(),
                                Toggle::make('enabled')->default(true)->inline(false),
                            ])
                            ->columns(3)->reorderable()->defaultItems(0)->addActionLabel('Add number')
                            ->itemLabel(fn (array $state) => $state['label'] ?? null),
                    ]),
                    Section::make('Sections')->description('Drag to reorder; switch off to hide.')->schema([
                        self::sections('home.sections', [
                            'stats' => 'Numbers', 'colleges' => 'Colleges', 'featured' => 'Featured programs',
                            'news' => 'News and events', 'cta' => 'Enrollment call to action',
                        ], withLink: true),
                    ]),
                ]),

                Tab::make('College pages')->icon(Heroicon::OutlinedBuildingLibrary)->schema([
                    Section::make('Numbers')->description('Counted live for each college.')->schema([
                        Repeater::make('college.stats')->hiddenLabel()
                            ->schema([
                                Select::make('metric')->options([
                                    'programs' => 'Published programs', 'partners' => 'Partner institutions',
                                    'sdgs' => 'SDGs supported', 'stories' => 'Published stories', 'faculty' => 'Faculty profiles',
                                ])->required(),
                                TextInput::make('label')->required(),
                                Toggle::make('enabled')->default(true)->inline(false),
                            ])
                            ->columns(3)->reorderable()->defaultItems(0)->addActionLabel('Add number')
                            ->itemLabel(fn (array $state) => $state['label'] ?? null),
                    ]),
                    Section::make('Sections')->description('Same order and headings for every college. ' . $placeholders . ' Sections without content are skipped for that college.')->schema([
                        self::sections('college.sections', [
                            'about' => 'About: vision, mission, goals, video', 'programs' => 'Programs', 'research' => 'Research cluster',
                            'partners' => 'Partners and linkages', 'students' => 'For students', 'sdgs' => 'Extension and SDGs',
                            'faculty' => 'Faculty', 'resources' => 'Facilities and documents', 'news' => 'News and events',
                        ], withNav: true),
                    ]),
                    Section::make('Labels and buttons')->description($placeholders . ' :count = number.')->collapsed()->schema([
                        KeyValue::make('college.labels')->hiddenLabel()->keyLabel('Where')->valueLabel('Text')->addable(false)->deletable(false)->editableKeys(false),
                    ]),
                ]),

                Tab::make('Program pages')->icon(Heroicon::OutlinedAcademicCap)->schema([
                    Section::make('Sections')->description('Drag to reorder; switch off to hide on every program page.')->schema([
                        self::sections('program.sections', [
                            'overview' => 'Overview', 'objectives' => 'Objectives', 'curriculum' => 'Curriculum', 'careers' => 'Careers',
                            'admission' => 'Admission', 'accreditation' => 'Accreditation', 'faqs' => 'FAQs',
                        ], withNav: true, withKicker: false),
                    ]),
                    Section::make('Labels and buttons')->description($placeholders)->collapsed()->schema([
                        KeyValue::make('program.labels')->hiddenLabel()->keyLabel('Where')->valueLabel('Text')->addable(false)->deletable(false)->editableKeys(false),
                    ]),
                    Section::make('Department pages')->collapsed()->schema([
                        KeyValue::make('department.labels')->hiddenLabel()->keyLabel('Where')->valueLabel('Text')->addable(false)->deletable(false)->editableKeys(false),
                    ]),
                ]),

                Tab::make('Finder, news, 404')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([
                    Section::make('Program finder')->columns(2)->schema([
                        TextInput::make('finder.meta_title')->label('Browser tab title'),
                        TextInput::make('finder.heading')->label('Title'),
                        TextInput::make('finder.kicker')->label('Small label above the title'),
                        TextInput::make('finder.per_page')->label('Programs per page')->numeric()->minValue(6)->maxValue(96),
                        Textarea::make('finder.meta_description')->label('Search engine description')->rows(2)->columnSpanFull(),
                        KeyValue::make('finder.labels')->label('Labels')->addable(false)->deletable(false)->editableKeys(false)->columnSpanFull(),
                    ]),
                    Section::make('News page')->columns(2)->schema([
                        TextInput::make('news.meta_title')->label('Browser tab title'),
                        TextInput::make('news.heading')->label('Title'),
                        Textarea::make('news.intro')->label('Introduction')->rows(2),
                        TextInput::make('news.per_page')->label('Stories per page')->numeric()->minValue(3)->maxValue(48),
                        KeyValue::make('news.labels')->label('Labels')->addable(false)->deletable(false)->editableKeys(false)->columnSpanFull(),
                    ]),
                    Section::make('Enrollment call to action')->description('Closes the home, college and program pages.')->columns(2)->schema([
                        TextInput::make('cta.kicker')->label('Small label'),
                        TextInput::make('cta.heading')->label('Heading'),
                        Textarea::make('cta.text')->label('Text')->rows(2)->columnSpanFull(),
                        TextInput::make('cta.primary_label')->label('Main button'),
                        TextInput::make('cta.primary_url')->label('Main button link'),
                        TextInput::make('cta.secondary_label')->label('Second button'),
                        TextInput::make('cta.secondary_url')->label('Second button link'),
                    ]),
                    Section::make('Page not found')->columns(2)->schema([
                        TextInput::make('not_found.kicker')->label('Small label'),
                        TextInput::make('not_found.heading')->label('Heading'),
                        Textarea::make('not_found.text')->label('Text')->rows(2)->columnSpanFull(),
                        TextInput::make('not_found.primary_label')->label('Button to the program finder'),
                        TextInput::make('not_found.secondary_label')->label('Button to the home page'),
                    ]),
                ]),

                Tab::make('Global engagement')->icon(Heroicon::OutlinedLink)->schema([
                    Section::make('Site and menu')
                        ->description(fn () => 'Address: ' . route('global.home') . (config('app.global_domain') ? '' : '. Set GLOBAL_DOMAIN in .env to serve it on its own subdomain.'))
                        ->columns(2)->schema([
                            TextInput::make('global.brand_label')->label('Name next to the logo'),
                            TextInput::make('global.meta_title')->label('Browser tab title'),
                            Textarea::make('global.meta_description')->label('Search engine description')->rows(2)->maxLength(300)->columnSpanFull(),
                            $links('global.menu', 'Menu links (#about, #partners, #opportunities, #stories jump to sections)')->columnSpanFull(),
                            TextInput::make('global.menu_button')->label('Menu button (jumps to the inquiry form)'),
                        ]),
                    Section::make('Top of the page')->columns(2)->schema([
                        TextInput::make('global.hero.kicker')->label('Small label above the title'),
                        TextInput::make('global.hero.heading')->label('Title'),
                        TextInput::make('global.hero.heading_accent')->label('Second line of the title (italic)'),
                        Textarea::make('global.hero.intro')->label('Introduction')->rows(2)->columnSpanFull(),
                        TextInput::make('global.hero.primary_label')->label('Button to the partners'),
                        TextInput::make('global.hero.secondary_label')->label('Button to the inquiry form'),
                    ]),
                    Section::make('Sections')->description('Drag to reorder; switch off to hide.')->schema([
                        self::sections('global.sections', [
                            'pathways' => 'Ways to work with us', 'about' => 'About', 'partners' => 'Partners',
                            'opportunities' => 'Opportunities', 'sdg' => 'Partnerships for the Goals (SDG 17)',
                            'stories' => 'Stories', 'faq' => 'Questions', 'connect' => 'Inquiry form',
                        ], withLink: true),
                    ]),
                    Section::make('Section content')->columns(2)->collapsible()->schema([
                        Repeater::make('global.pathways')->label('Ways to work with us: cards')
                            ->schema([TextInput::make('title')->required(), Textarea::make('text')->rows(2)])
                            ->columns(2)->reorderable()->defaultItems(0)->collapsible()
                            ->itemLabel(fn (array $state) => $state['title'] ?? null)->columnSpanFull(),
                        Textarea::make('global.about')->label('About (blank line between paragraphs)')->rows(6)->columnSpanFull(),
                        Textarea::make('global.partners_intro')->label('Partners introduction')->rows(2)->columnSpanFull(),
                        Repeater::make('global.opportunities')->label('Opportunities: cards')
                            ->schema([TextInput::make('title')->required(), Textarea::make('text')->rows(2)])
                            ->columns(2)->reorderable()->defaultItems(0)->collapsible()
                            ->itemLabel(fn (array $state) => $state['title'] ?? null)->columnSpanFull(),
                        TextInput::make('global.exchange_heading')->label('Exchange panel heading'),
                        Textarea::make('global.exchange_note')->label('Exchange panel note')->rows(2),
                        Repeater::make('global.exchange_items')->label('Exchange panel items')->simple(TextInput::make('text')->required())
                            ->reorderable()->defaultItems(0)->columnSpanFull(),
                        Textarea::make('global.sdg_text')->label('Partnerships for the Goals text')->rows(3)->columnSpanFull(),
                        Select::make('global.story_categories')->label('Stories: news categories to show')->multiple()
                            ->options(fn () => Term::options('news_category'))->helperText('Empty: all news.')->columnSpanFull(),
                        Repeater::make('global.faq')->label('Questions')
                            ->schema([TextInput::make('question')->required(), Textarea::make('answer')->rows(3)->required()])
                            ->reorderable()->defaultItems(0)->collapsible()->itemLabel(fn (array $state) => $state['question'] ?? null)->columnSpanFull(),
                        Textarea::make('global.connect_text')->label('Inquiry form introduction')->rows(2)->columnSpanFull(),
                        KeyValue::make('global.interests')->label('Inquiry form: areas of interest')->keyLabel('Code (saved with inquiries)')->valueLabel('Shown as')->columnSpanFull(),
                    ]),
                    Section::make('University-wide partners')
                        ->description("Partners not tied to one college. College partners are edited on each college's page and appear here automatically.")
                        ->schema([\App\Filament\Forms\PartnerFields::repeater('global.partners', 'Partners')]),
                    Section::make('Labels')->collapsed()->schema([
                        KeyValue::make('global.labels')->hiddenLabel()->helperText('Placeholders: :shown and :count.')
                            ->addable(false)->deletable(false)->editableKeys(false),
                    ]),
                ]),

                Tab::make('SDG pages')->icon(Heroicon::OutlinedGlobeAlt)->schema([
                    Section::make('SDG report (/sdgs) and goal pages (/sdgs/1 to /sdgs/17)')
                        ->description('Goal names, colors and aims are edited in Lists and categories → Sustainable Development Goals. Counts come from stories tagged with SDGs and the extension projects on college pages.')
                        ->columns(2)->schema([
                            TextInput::make('sdgs.meta_title')->label('Browser tab title'),
                            TextInput::make('sdgs.kicker')->label('Small label above the title'),
                            TextInput::make('sdgs.heading')->label('Title')->columnSpanFull(),
                            Textarea::make('sdgs.intro')->label('Introduction')->rows(2)->columnSpanFull(),
                            TextInput::make('sdgs.report_label')->label('Report button')->helperText('E.g. "Read the SDG Report". Empty: no button.'),
                            TextInput::make('sdgs.report_url')->label('Report link')->placeholder('https://... or /storage/...'),
                            KeyValue::make('sdgs.labels')->label('Labels')->helperText('Placeholders: :number and :goal (goal number and name), :count.')
                                ->addable(false)->deletable(false)->editableKeys(false)->columnSpanFull(),
                        ]),
                ]),

                Tab::make('Admin and performance')->icon(Heroicon::OutlinedBolt)->schema([
                    Section::make('Admin panel')->columns(2)->schema([
                        TextInput::make('admin.brand_name')->label('Name in the admin header'),
                        TextInput::make('admin.badge')->label('Badge next to the logo'),
                        TextInput::make('admin.login_subheading')->label('Text on the sign-in page'),
                    ]),
                    Section::make('Performance')->columns(2)->schema([
                        TextInput::make('performance.page_cache_minutes')->label('Keep public pages ready for (minutes)')->numeric()->minValue(0)->maxValue(1440)
                            ->helperText('Pages are rebuilt immediately after any admin change anyway. 0 = never keep.'),
                        TextInput::make('performance.vitals_sample_rate')->label('Speed measurements: % of visits')->numeric()->minValue(0)->maxValue(100)
                            ->helperText('Share of visits that report loading speed (Core Web Vitals) to the dashboard. 0 = off.'),
                    ]),
                ]),
            ]),
        ]);
    }

    /** Fixed list of page sections: reorder, switch on/off, edit headings. Sections cannot be added or removed. */
    private static function sections(string $name, array $types, bool $withNav = false, bool $withKicker = true, bool $withLink = false): Repeater
    {
        return Repeater::make($name)->hiddenLabel()
            ->schema(array_values(array_filter([
                Select::make('type')->options($types)->disabled()->dehydrated()->label('Section'),
                Toggle::make('enabled')->label('Shown')->default(true)->inline(false),
                $withNav ? TextInput::make('nav_label')->label('In-page menu label') : null,
                $withKicker ? TextInput::make('kicker')->label('Small label') : null,
                TextInput::make('heading')->label('Heading'),
                $withLink ? TextInput::make('link_label')->label('Link text') : null,
                $withLink ? TextInput::make('limit')->label('How many')->numeric()->minValue(1)->maxValue(24) : null,
            ])))
            ->columns(3)
            ->reorderable()
            ->addable(false)
            ->deletable(false)
            ->collapsible()
            ->itemLabel(fn (array $state) => ($types[$state['type'] ?? ''] ?? 'Section') . (($state['enabled'] ?? true) ? '' : ' (hidden)'));
    }

    /** Home page numbers: fixed counts, plus "programs at level X" for every academic level. */
    public static function homeMetrics(): array
    {
        $metrics = ['colleges' => 'Published colleges and units', 'programs' => 'All published programs', 'accredited' => 'Accredited programs', 'faculty' => 'Faculty profiles'];
        foreach (Term::options('level') as $key => $label) {
            $metrics["level:$key"] = "$label programs";
        }

        return $metrics;
    }
}
