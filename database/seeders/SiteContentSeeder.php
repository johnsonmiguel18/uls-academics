<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Models\Term;
use Illuminate\Database\Seeder;

/**
 * First content of Admin → Site settings and Admin → Lists and categories.
 * After installation everything here is edited in the admin; the seeder only fills
 * what is still empty, so re-running it never overwrites an editor's changes.
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTerms();

        // Add only settings that do not exist yet (new groups or new keys after an update);
        // lists such as menus and sections are never merged item by item.
        $setting = SiteSetting::query()->firstOrNew();
        $setting->data = self::addMissing(self::settings(), $setting->data ?? []);
        $setting->save();
    }

    /** Keys missing from $current are taken from $defaults; existing values and lists stay as edited. */
    private static function addMissing(array $defaults, array $current): array
    {
        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $current)) {
                $current[$key] = $value;
            } elseif (is_array($value) && is_array($current[$key]) && ! array_is_list($value)) {
                $current[$key] = self::addMissing($value, $current[$key]);
            }
        }

        return $current;
    }

    public static function settings(): array
    {
        return [
            'contact' => [
                'site_name' => 'ULS Academics',
                'university_name' => 'University of La Salette, Inc.',
                'university_short_name' => 'University of La Salette',
                'address' => 'Dubinan East, Santiago City, Isabela 3311',
                'phone' => '(078) 682-4771',
                'email' => '',
                'main_site_url' => 'https://uls.edu.ph',
                'enroll_url' => 'https://app.uls.edu.ph/sias/',
                'admission_url' => 'https://uls.edu.ph/pages/admission-requirements',
            ],
            'header' => [
                'topbar_link_label' => 'University of La Salette main website',
                'show_contact' => true,
                'brand_label' => 'Academics',
                'colleges_menu_label' => 'Colleges',
                'menu' => [
                    ['label' => 'Programs', 'url' => '/programs'],
                    ['label' => 'News', 'url' => '/news'],
                    ['label' => 'SDGs', 'url' => '/sdgs'],
                    ['label' => 'Global engagement', 'url' => '/global'],
                ],
                'search_placeholder' => 'Search programs',
                'enroll_label' => 'Enroll',
                'back_to_top' => 'Back to top',
            ],
            'footer' => [
                'about' => 'ULS Academics: colleges, programs and faculty of the University of La Salette, Inc., Dubinan East, Santiago City, Isabela 3311.',
                'colleges_heading' => 'Colleges',
                'columns' => [
                    ['heading' => 'Explore', 'links' => [
                        ['label' => 'Program finder', 'url' => '/programs'],
                        ['label' => 'Undergraduate programs', 'url' => '/programs?level=undergraduate'],
                        ['label' => 'Graduate programs', 'url' => '/programs?level=graduate'],
                        ['label' => 'News and announcements', 'url' => '/news'],
                        ['label' => 'Online enrollment', 'url' => 'https://app.uls.edu.ph/sias/'],
                    ]],
                    ['heading' => 'University of La Salette', 'links' => [
                        ['label' => 'Main website', 'url' => 'https://uls.edu.ph'],
                        ['label' => 'Admission requirements', 'url' => 'https://uls.edu.ph/pages/admission-requirements'],
                        ['label' => '(078) 682-4771', 'url' => 'tel:+63786824771'],
                    ]],
                ],
                'copyright' => '© :year University of La Salette, Inc. All rights reserved.',
            ],
            'cta' => [
                'kicker' => 'Admissions',
                'heading' => 'Begin your Salettinian journey',
                'text' => 'Enrollment runs online through the Student Information and Accounting System. Prepare your requirements, then reserve your slot.',
                'primary_label' => 'Enroll now',
                'primary_url' => 'https://app.uls.edu.ph/sias/',
                'secondary_label' => 'Admission requirements',
                'secondary_url' => 'https://uls.edu.ph/pages/admission-requirements',
            ],
            'home' => [
                'meta_title' => 'ULS Academics | University of La Salette',
                'meta_description' => 'Explore the colleges, graduate school and basic education programs of the University of La Salette, Santiago City, Isabela.',
                'breadcrumb_parent' => 'University of La Salette',
                'kicker' => 'Academics',
                'headline' => 'Academic Excellence at ULS',
                'lead' => 'Ten colleges, a graduate school and basic education, formed in the Salettinian tradition of faith, reconciliation and service. Find the program that fits your calling.',
                'search_label' => 'Search academic programs',
                'search_placeholder' => 'Search programs, e.g. Nursing, BSIT, Education',
                'search_button' => 'Search',
                'quick_links' => [
                    ['label' => 'Undergraduate programs', 'url' => '/programs?level=undergraduate'],
                    ['label' => 'Graduate programs', 'url' => '/programs?level=graduate'],
                    ['label' => 'Basic Education', 'url' => '/programs?level=basic'],
                    ['label' => 'Admission requirements', 'url' => 'https://uls.edu.ph/pages/admission-requirements'],
                ],
                'stats' => [
                    ['metric' => 'colleges', 'label' => 'Colleges and academic units', 'enabled' => true],
                    ['metric' => 'level:undergraduate', 'label' => 'Undergraduate programs', 'enabled' => true],
                    ['metric' => 'level:graduate', 'label' => 'Graduate programs', 'enabled' => true],
                    ['metric' => 'accredited', 'label' => 'Accredited programs', 'enabled' => true],
                    ['metric' => 'faculty', 'label' => 'Faculty profiles', 'enabled' => true],
                ],
                'sections' => [
                    ['type' => 'stats', 'enabled' => true, 'kicker' => '', 'heading' => 'Academics at a glance', 'link_label' => ''],
                    ['type' => 'colleges', 'enabled' => true, 'kicker' => 'Academic units', 'heading' => 'Our colleges and schools', 'link_label' => 'All programs'],
                    ['type' => 'featured', 'enabled' => true, 'kicker' => 'Program highlights', 'heading' => 'Featured programs', 'link_label' => 'Find a program', 'limit' => 6],
                    ['type' => 'news', 'enabled' => true, 'kicker' => 'From the colleges', 'heading' => 'News and announcements', 'link_label' => 'All news', 'limit' => 3, 'events_heading' => 'Upcoming events'],
                    ['type' => 'cta', 'enabled' => true],
                ],
                'college_card_link' => 'Explore',
            ],
            'college' => [
                'stats' => [
                    ['metric' => 'programs', 'label' => 'Programs', 'enabled' => true],
                    ['metric' => 'partners', 'label' => 'Partner institutions and agencies', 'enabled' => true],
                    ['metric' => 'sdgs', 'label' => 'Sustainable Development Goals supported', 'enabled' => true],
                    ['metric' => 'stories', 'label' => 'Stories published', 'enabled' => true],
                ],
                'sections' => [
                    ['type' => 'about', 'enabled' => true, 'nav_label' => 'About', 'kicker' => 'About the college', 'heading' => 'Vision, mission and goals'],
                    ['type' => 'programs', 'enabled' => true, 'nav_label' => 'Programs', 'kicker' => 'Academic programs', 'heading' => 'What you can study at :short'],
                    ['type' => 'research', 'enabled' => true, 'nav_label' => 'Research', 'kicker' => 'Research · Research and Development Center', 'heading' => 'Our research cluster'],
                    ['type' => 'partners', 'enabled' => true, 'nav_label' => 'Partners', 'kicker' => 'Partnerships and linkages', 'heading' => 'Who we work with'],
                    ['type' => 'students', 'enabled' => true, 'nav_label' => 'For students', 'kicker' => 'For students', 'heading' => 'What these linkages mean for you'],
                    ['type' => 'sdgs', 'enabled' => true, 'nav_label' => 'Extension & SDGs', 'kicker' => 'Extension and the SDGs', 'heading' => 'Work that serves communities'],
                    ['type' => 'faculty', 'enabled' => true, 'nav_label' => 'Faculty', 'kicker' => 'Faculty directory', 'heading' => 'Our faculty'],
                    ['type' => 'resources', 'enabled' => true, 'nav_label' => 'Resources', 'kicker' => 'Facilities', 'heading' => 'Where we learn'],
                    ['type' => 'news', 'enabled' => true, 'nav_label' => 'News', 'kicker' => 'Latest from :short', 'heading' => 'News and articles'],
                ],
                'labels' => [
                    'hero_programs' => 'Our programs',
                    'hero_partners' => 'Partners and linkages',
                    'hero_faculty' => 'Meet the faculty',
                    'dean_card_programs' => ':count programs',
                    'research_cluster' => 'Research cluster',
                    'see_agenda' => 'See the research agenda',
                    'see_research' => 'See our research',
                    'vision' => 'Vision',
                    'mission' => 'Mission',
                    'goals' => 'Goals',
                    'objectives' => 'Objectives',
                    'show_all' => 'Show all',
                    'show_less' => 'Show less',
                    'video_caption' => 'Get to know the :college.',
                    'video_link' => 'Watch the :college video on YouTube (opens in a new tab)',
                    'all_programs' => 'All ULS programs',
                    'program_details' => 'Program details',
                    'ask_program' => 'Ask about this program',
                    'ask_subject' => 'Inquiry: :program',
                    'majors' => 'Majors',
                    'departments' => 'Departments',
                    'research_coordinator' => 'Research coordinator',
                    'research_agenda' => 'Full research agenda',
                    'research_repository' => 'Research repository',
                    'research_highlights' => ':short research highlights',
                    'focus_areas' => 'Research focus areas',
                    'linkages' => 'Academic, government and professional linkages',
                    'read_more' => 'Read more',
                    'sdg_stories' => ':goal: :count stories',
                    'sdg_none' => ':goal, no stories yet',
                    'sdg_help' => 'Bright goals have :short stories or projects; the number shows how many. Tap a bright goal to read them. Pale goals have none yet.',
                    'documents_kicker' => 'Resources',
                    'documents_heading' => 'Documents and downloads',
                    'all_news' => 'All :short news',
                    'events_heading' => 'Upcoming events',
                    'cta_heading' => 'Join the :college',
                ],
            ],
            'department' => [
                'labels' => [
                    'programs_kicker' => 'Programs', 'programs_heading' => 'Programs offered',
                    'faculty_kicker' => 'Faculty', 'faculty_heading' => 'Faculty',
                    'achievements_kicker' => 'Achievements', 'achievements_heading' => 'Recognition',
                    'accreditation_kicker' => 'Accreditation', 'accreditation_heading' => 'Quality assurance',
                    'resources_kicker' => 'Resources', 'resources_heading' => 'Documents and downloads',
                ],
            ],
            'program' => [
                'sections' => [
                    ['type' => 'overview', 'enabled' => true, 'nav_label' => 'Overview', 'heading' => 'Program overview'],
                    ['type' => 'objectives', 'enabled' => true, 'nav_label' => 'Objectives', 'heading' => 'Objectives and outcomes'],
                    ['type' => 'curriculum', 'enabled' => true, 'nav_label' => 'Curriculum', 'heading' => 'Curriculum'],
                    ['type' => 'careers', 'enabled' => true, 'nav_label' => 'Careers', 'heading' => 'Career opportunities'],
                    ['type' => 'admission', 'enabled' => true, 'nav_label' => 'Admission', 'heading' => 'Admission requirements'],
                    ['type' => 'accreditation', 'enabled' => true, 'nav_label' => 'Accreditation', 'heading' => 'Accreditation'],
                    ['type' => 'faqs', 'enabled' => true, 'nav_label' => 'FAQs', 'heading' => 'Frequently asked questions'],
                ],
                'labels' => [
                    'apply' => 'Apply now',
                    'download_curriculum' => 'Download curriculum (PDF)',
                    'download_curriculum_full' => 'Download the full curriculum (PDF)',
                    'on_this_page' => 'On this page',
                    'fact_degree' => 'Degree', 'fact_level' => 'Level', 'fact_duration' => 'Duration', 'fact_code' => 'Program code', 'fact_accreditation' => 'Accreditation',
                    'valid_until' => 'Valid until',
                    'certificate' => 'Certificate',
                    'empty_heading' => 'Program details are on the way',
                    'empty_text' => 'The :college is preparing the full description of this program. For questions, contact the college or the Admissions Office.',
                    'related_kicker' => 'Also in the :college',
                    'related_heading' => 'Related programs',
                    'related_link' => 'All programs',
                    'cta_heading' => 'Ready to apply?',
                    'card_link' => 'View program',
                ],
            ],
            'finder' => [
                'meta_title' => 'Program Finder | ULS Academics',
                'meta_description' => 'Search and filter every undergraduate, graduate and basic education program offered by the University of La Salette.',
                'kicker' => 'Program finder',
                'heading' => 'Find your program',
                'per_page' => 24,
                'labels' => [
                    'search' => 'Search', 'search_placeholder' => 'Program name or code',
                    'level' => 'Academic level', 'level_all' => 'All levels',
                    'college' => 'College', 'college_all' => 'All colleges',
                    'degree' => 'Degree type', 'degree_all' => 'All degrees',
                    'accredited' => 'Accredited only',
                    'apply' => 'Apply', 'clear' => 'Clear',
                    'results' => ':count programs found',
                    'empty_heading' => 'No programs match these filters',
                    'empty_text' => 'Try a shorter search or see all programs.',
                ],
            ],
            'news' => [
                'meta_title' => 'News & Announcements | ULS Academics',
                'heading' => 'News & Announcements',
                'intro' => 'News, achievements, research and extension work from the colleges of the University of La Salette.',
                'per_page' => 9,
                'labels' => [
                    'breadcrumb' => 'News',
                    'search_label' => 'Search news',
                    'search_placeholder' => 'Search news and announcements...',
                    'search_button' => 'Search',
                    'all_categories' => 'All',
                    'filtered_college' => 'Stories from the :college',
                    'filtered_sdg' => 'Stories on SDG :number: :goal',
                    'see_all' => 'See all news',
                    'empty_heading' => 'No news found',
                    'empty_text' => 'Try another search term or category, or check back soon for updates.',
                    'view_all' => 'View all news',
                    'published' => 'Published :date',
                    'back' => 'Back to News & Announcements',
                    'related_heading' => 'More stories',
                    'list_heading' => 'More updates',
                    'results_heading' => 'Search results',
                    'featured_heading' => 'Featured',
                    'latest_heading' => 'More headlines',
                    'sdg_filter' => 'Filter by Sustainable Development Goal',
                    'all_sdgs' => 'All goals',
                    'sdg_menu' => 'Goals',
                    'rail_previous' => 'Previous stories',
                    'rail_next' => 'Next stories',
                    'more_sdgs' => '+:count',
                    'story_nav' => 'Previous and next stories',
                    'previous_story' => 'Previous story',
                    'next_story' => 'Next story',
                ],
            ],
            'sdgs' => [
                'meta_title' => 'Sustainable Development Goals | ULS Academics',
                'kicker' => 'Extension and the SDGs',
                'heading' => 'Our work for the Sustainable Development Goals',
                'intro' => 'Every story and extension project of the colleges is tagged with the UN Sustainable Development Goals it serves. Choose a goal to see the work behind it.',
                'report_label' => '',
                'report_url' => '',
                'labels' => [
                    'breadcrumb' => 'SDGs',
                    'goals_tracked' => 'Goals with work behind them',
                    'stories_mapped' => 'Stories and projects mapped',
                    'colleges_involved' => 'Colleges involved',
                    'grid_heading' => 'The 17 goals',
                    'grid_help' => 'Bright goals have stories or projects; the number shows how many. Pale goals have none yet.',
                    'by_college_heading' => 'By college',
                    'college_column' => 'College',
                    'goals_column' => 'Goals served',
                    'items_column' => 'Stories and projects',
                    'goal_kicker' => 'SDG :number',
                    'goal_colleges_heading' => 'Colleges working on this goal',
                    'goal_college_count' => 'Stories and projects: :count',
                    'goal_projects_heading' => 'Extension projects',
                    'goal_stories_heading' => 'Stories',
                    'goal_stories_all' => 'All :count stories on this goal',
                    'goal_empty' => 'No stories or projects are tagged with this goal yet.',
                    'previous' => 'Previous goal',
                    'next' => 'Next goal',
                    'all_goals' => 'All goals',
                    'read_more' => 'Read more',
                ],
            ],
            'global' => [
                'meta_title' => 'Global Engagement & Linkages | University of La Salette',
                'meta_description' => 'Partnerships of the University of La Salette with schools, government agencies, industry and communities, in the Philippines and abroad.',
                'brand_label' => 'Global Engagement & Linkages',
                'menu' => [
                    ['label' => 'About', 'url' => '#about'],
                    ['label' => 'Our partners', 'url' => '#partners'],
                    ['label' => 'Opportunities', 'url' => '#opportunities'],
                    ['label' => 'Stories', 'url' => '#stories'],
                ],
                'menu_button' => "Let's connect",
                'hero' => [
                    'kicker' => 'Santiago City, Isabela',
                    'heading' => 'Our partners.',
                    'heading_accent' => 'Our shared future.',
                    'intro' => 'The University of La Salette works with schools, government agencies, industry and communities to widen learning, research and service for our students, faculty and region.',
                    'primary_label' => 'Explore our partners',
                    'secondary_label' => 'Partner with ULS',
                ],
                'sections' => [
                    ['type' => 'pathways', 'enabled' => true, 'kicker' => '', 'heading' => 'Ways to work with us'],
                    ['type' => 'about', 'enabled' => true, 'kicker' => 'About', 'heading' => 'Rooted in faith. Connected through a shared purpose.'],
                    ['type' => 'partners', 'enabled' => true, 'kicker' => 'Linkages', 'heading' => 'Our school partners'],
                    ['type' => 'opportunities', 'enabled' => true, 'kicker' => 'Opportunities', 'heading' => "Explore what's possible"],
                    ['type' => 'sdg', 'enabled' => true, 'kicker' => 'SDG 17', 'heading' => 'Partnerships for the Goals'],
                    ['type' => 'stories', 'enabled' => true, 'kicker' => 'Stories', 'heading' => 'Partnerships in action', 'link_label' => 'More stories', 'limit' => 3],
                    ['type' => 'faq', 'enabled' => true, 'kicker' => 'Questions', 'heading' => 'A helpful starting point'],
                    ['type' => 'connect', 'enabled' => true, 'kicker' => "Let's connect", 'heading' => 'Start a conversation with ULS'],
                ],
                'pathways' => [
                    ['title' => 'Academic exchange', 'text' => 'Student and faculty mobility, visiting lectures and joint classes with partner schools.'],
                    ['title' => 'Research and innovation', 'text' => 'Joint studies, shared facilities and publications with universities and agencies.'],
                    ['title' => 'Community and industry', 'text' => 'Internships, extension work and projects with employers, government and communities.'],
                ],
                'about' => "Guided by the Salettinian mission of reconciliation, the University of La Salette builds partnerships that open opportunities for students, strengthen teaching and research, and serve the communities of Isabela and beyond.\n\nThe Office of Global Engagement and Linkages coordinates agreements with schools, government agencies, industry and community organizations, together with the colleges.",
                'partners_intro' => 'Partners of the colleges and of the university as a whole. Filter by college or by reach.',
                'partners' => [],
                'opportunities' => [
                    ['title' => 'Academic exchange', 'text' => 'Short-term mobility, credit-bearing study and visiting faculty with partner schools.'],
                    ['title' => 'Research and innovation', 'text' => 'Joint research, co-authored papers and shared conferences.'],
                    ['title' => 'Industry engagement', 'text' => 'On-the-job training, guest lecturers and curriculum input from employers.'],
                    ['title' => 'Community collaboration', 'text' => 'Extension programs with local governments, parishes and civic groups.'],
                ],
                'exchange_heading' => 'Academic exchange at ULS',
                'exchange_items' => [
                    ['text' => 'Student mobility and short courses with partner schools'],
                    ['text' => 'Visiting lecturers and joint online classes'],
                    ['text' => 'Faculty development and benchmarking visits'],
                ],
                'exchange_note' => 'Availability, eligibility, funding and agreements must be confirmed with the university for each opportunity.',
                'sdg_text' => 'Our linkages follow the UN Sustainable Development Goals, especially Goal 17: working together across schools, government, industry and communities so that progress reaches more people.',
                'story_categories' => ['linkages', 'extension'],
                'faq' => [
                    ['question' => 'How can my institution propose a collaboration?', 'answer' => "Send us a message through the form below with your institution, your area of interest and a short description. The office will reply with the next steps."],
                    ['question' => 'Where can I find open exchange or scholarship calls?', 'answer' => 'This site does not announce open calls. Calls are posted by the university and the colleges in the news section.'],
                    ['question' => 'I am a ULS student or faculty member. Whom do I ask?', 'answer' => "Start with your college's dean's office, which works with this office on partner activities."],
                    ['question' => 'Can community organizations partner with ULS?', 'answer' => "Yes. Many of our linkages are extension programs with barangays, parishes and civic groups. Tell us about your community's needs through the form below."],
                ],
                'connect_text' => 'Tell us who you are and what you have in mind. The Office of Global Engagement and Linkages will get back to you by email.',
                'interests' => [
                    'exchange' => 'Academic exchange',
                    'research' => 'Research and innovation',
                    'industry' => 'Industry engagement',
                    'community' => 'Community collaboration',
                    'other' => 'Something else',
                ],
                'labels' => [
                    'stat_partners' => 'Partners',
                    'stat_local' => 'Local partners',
                    'stat_international' => 'International partners',
                    'stat_colleges' => 'Colleges represented',
                    'filter_college' => 'College',
                    'filter_scope' => 'Reach',
                    'all_colleges' => 'All colleges',
                    'university_wide' => 'University-wide',
                    'all_scopes' => 'Local and international',
                    'showing' => 'Showing :shown of :count partners',
                    'partners_empty' => 'Partners are being added. Please check back soon.',
                    'no_match' => 'No partners match these filters.',
                    'sdg_link' => 'See our work for the SDGs',
                    'form_name' => 'Your name',
                    'form_email' => 'Email address',
                    'form_organization' => 'Institution or department',
                    'form_interest' => 'Area of interest',
                    'form_choose' => 'Choose one',
                    'form_message' => 'What do you have in mind?',
                    'form_submit' => 'Send inquiry',
                    'form_privacy' => 'We use your details only to answer this inquiry.',
                    'form_sent' => 'Thank you. Your inquiry was sent; we will reply by email.',
                    'form_errors' => 'Please check the highlighted fields.',
                    'address_heading' => 'Visit us',
                ],
            ],
            'not_found' => [
                'kicker' => 'Error 404',
                'heading' => "We couldn't find that page",
                'text' => 'The program or college may have moved, or the link may be mistyped.',
                'primary_label' => 'Find a program',
                'secondary_label' => 'Academics home',
            ],
            'a11y' => [
                'skip_link' => 'Skip to content',
                'breadcrumb' => 'Breadcrumb',
                'open_menu' => 'Open menu',
                'main_navigation' => 'Main navigation',
                'sdgs' => 'Sustainable Development Goals',
                'pagination' => 'Pages',
                'wordmark' => 'University of La Salette, Incorporated',
            ],
            'seo' => [
                'organization_address' => 'Santiago City, Isabela, Philippines',
                'default_description' => 'Colleges, programs, faculty and news of the University of La Salette, Santiago City, Isabela.',
            ],
            'admin' => [
                'brand_name' => 'ULS Academics',
                'badge' => 'Website Admin',
                'login_subheading' => 'Sign in to manage colleges, programs, faculty and college news.',
            ],
            'performance' => [
                'page_cache_minutes' => 10,
                'vitals_sample_rate' => 25,
            ],
        ];
    }

    private function seedTerms(): void
    {
        $lists = [
            'level' => [
                'basic' => 'Basic Education',
                'undergraduate' => 'Undergraduate',
                'graduate' => 'Graduate',
            ],
            'degree_type' => [
                'basic' => 'Basic Education',
                'bachelor' => "Bachelor's Degree",
                'juris-doctor' => 'Juris Doctor',
                'master' => "Master's Degree",
                'doctorate' => 'Doctorate',
                'eteeap' => 'ETEEAP',
            ],
            'news_category' => [
                'college-news' => 'College News',
                'announcement' => 'Announcements',
                'achievement' => 'Achievements',
                'research' => 'Research',
                'extension' => 'Extension',
                'linkages' => 'Linkages and Partnerships',
            ],
            'partner_scope' => [
                'local' => 'Local',
                'international' => 'International',
            ],
            'document_category' => [
                'curriculum' => 'Curriculum',
                'brochure' => 'Brochure',
                'accreditation' => 'Accreditation',
                'form' => 'Form',
                'memo' => 'Memo / Policy',
                'other' => 'Other',
            ],
        ];

        foreach ($lists as $type => $entries) {
            $order = 0;
            foreach ($entries as $key => $label) {
                Term::firstOrCreate(['type' => $type, 'key' => $key], ['label' => $label, 'sort_order' => $order++]);
            }
        }

        // The 17 UN Sustainable Development Goals: official colors and the UN one-line aim of each.
        $sdgs = [
            ['No Poverty', '#E5243B', 'End poverty in all its forms everywhere.'],
            ['Zero Hunger', '#DDA63A', 'End hunger, achieve food security and improved nutrition and promote sustainable agriculture.'],
            ['Good Health and Well-being', '#4C9F38', 'Ensure healthy lives and promote well-being for all at all ages.'],
            ['Quality Education', '#C5192D', 'Ensure inclusive and equitable quality education and promote lifelong learning opportunities for all.'],
            ['Gender Equality', '#FF3A21', 'Achieve gender equality and empower all women and girls.'],
            ['Clean Water and Sanitation', '#26BDE2', 'Ensure availability and sustainable management of water and sanitation for all.'],
            ['Affordable and Clean Energy', '#FCC30B', 'Ensure access to affordable, reliable, sustainable and modern energy for all.'],
            ['Decent Work and Economic Growth', '#A21942', 'Promote sustained, inclusive and sustainable economic growth, full and productive employment and decent work for all.'],
            ['Industry, Innovation and Infrastructure', '#FD6925', 'Build resilient infrastructure, promote inclusive and sustainable industrialization and foster innovation.'],
            ['Reduced Inequalities', '#DD1367', 'Reduce inequality within and among countries.'],
            ['Sustainable Cities and Communities', '#FD9D24', 'Make cities and human settlements inclusive, safe, resilient and sustainable.'],
            ['Responsible Consumption and Production', '#BF8B2E', 'Ensure sustainable consumption and production patterns.'],
            ['Climate Action', '#3F7E44', 'Take urgent action to combat climate change and its impacts.'],
            ['Life Below Water', '#0A97D9', 'Conserve and sustainably use the oceans, seas and marine resources for sustainable development.'],
            ['Life on Land', '#56C02B', 'Protect, restore and promote sustainable use of terrestrial ecosystems, sustainably manage forests, combat desertification, and halt and reverse land degradation and halt biodiversity loss.'],
            ['Peace, Justice and Strong Institutions', '#00689D', 'Promote peaceful and inclusive societies for sustainable development, provide access to justice for all and build effective, accountable and inclusive institutions at all levels.'],
            ['Partnerships for the Goals', '#19486A', 'Strengthen the means of implementation and revitalize the Global Partnership for Sustainable Development.'],
        ];
        foreach ($sdgs as $i => [$name, $color, $aim]) {
            $goal = Term::firstOrCreate(['type' => 'sdg', 'key' => (string) ($i + 1)], ['label' => $name, 'color' => $color, 'sort_order' => $i + 1]);
            // Fill the aim once; never overwrite the admin's own text.
            if ($goal->description === null) {
                $goal->update(['description' => $aim]);
            }
        }
    }
}
