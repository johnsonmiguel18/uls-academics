<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_name')->nullable();
            $table->string('level')->default('undergraduate'); // undergraduate | graduate | basic
            $table->string('headline')->nullable();             // hero title, e.g. "Building engineers for resilient communities"
            $table->string('tagline')->nullable();
            $table->text('overview')->nullable();
            $table->string('email')->nullable();                // "Ask about this program" links
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            $table->longText('goals')->nullable();               // markdown
            $table->longText('objectives')->nullable();          // markdown
            $table->string('video_url')->nullable();
            $table->string('video_caption')->nullable();
            $table->string('dean_name')->nullable();
            $table->string('dean_title')->nullable();
            $table->string('dean_photo')->nullable();
            $table->text('dean_message')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('seal_image')->nullable();
            $table->longText('facilities')->nullable();          // markdown

            // Research cluster (BCU-style): name, emblem, coordinator, links, highlights, focus areas.
            $table->string('research_cluster')->nullable();              // acronym, e.g. "TIBAY"
            $table->string('research_cluster_name')->nullable();         // full name
            $table->text('research_description')->nullable();
            $table->string('research_emblem')->nullable();
            $table->string('research_coordinator')->nullable();
            $table->string('research_agenda_url')->nullable();
            $table->string('research_repository_url')->nullable();
            $table->json('research_highlights')->nullable();  // [{title, description, url}]
            $table->json('focus_areas')->nullable();          // [{area, sdgs: [int]}]

            // Partnerships, student benefits, extension and the SDGs.
            $table->text('partners_intro')->nullable();
            $table->json('partners')->nullable();             // [{name, type, period, description, sdgs, links: [{label, url}]}]
            $table->json('linkages')->nullable();             // [{name, description, url}]
            $table->json('student_benefits')->nullable();     // [{title, description, url}]
            $table->text('sdg_summary')->nullable();
            $table->json('extension_projects')->nullable();   // [{title, year, description, sdgs, url}]
            $table->string('meta_description', 300)->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('overview')->nullable();
            $table->longText('achievements')->nullable(); // markdown
            $table->string('status')->default('published');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->nullable();
            $table->string('level')->default('undergraduate'); // undergraduate | graduate | basic
            $table->string('degree_type');                      // a key of the admin list "Degree types" (terms)
            $table->string('duration')->nullable();
            $table->string('majors')->nullable();
            $table->string('summary', 300)->nullable();
            $table->longText('overview')->nullable();               // markdown, same for the fields below
            $table->longText('objectives')->nullable();
            $table->longText('curriculum')->nullable();
            $table->string('curriculum_file')->nullable();
            $table->longText('careers')->nullable();
            $table->longText('admission_requirements')->nullable();
            $table->json('faqs')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('meta_description', 300)->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'level']);
        });

        Schema::create('accreditations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('agency');           // PAASCU, PACUCOA, CHED COE...
            $table->string('level');            // e.g. Level III Re-accredited
            $table->date('valid_until')->nullable();
            $table->string('certificate')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('faculty_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('specialization')->nullable();
            $table->string('email')->nullable();
            $table->string('photo')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('academic_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('category')->default('other'); // a key of the admin list "Document categories" (terms)
            $table->string('file');
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('college_id')->nullable()->after('email')->constrained()->nullOnDelete();
        });

        Schema::table('news', function (Blueprint $table) {
            $table->foreignId('college_id')->nullable()->after('category')->constrained()->nullOnDelete();
            $table->json('sdgs')->nullable()->after('college_id');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('sdgs');
            $table->dropConstrainedForeignId('college_id');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('college_id'));

        foreach (['documents', 'academic_events', 'faculty_members', 'accreditations', 'programs', 'departments', 'colleges'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
