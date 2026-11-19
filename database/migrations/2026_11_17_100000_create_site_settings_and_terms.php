<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed site content:
 * - site_settings: one record with every site-wide text, link, menu and page-section setting.
 * - terms: the lists editors pick from (academic levels, degree types, categories, SDGs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->json('data');
            $table->timestamps();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('key', 80);
            $table->string('label');
            $table->string('color', 9)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'key']);
            $table->index(['type', 'is_active', 'sort_order']);
        });

        // Indexes for the public pages' most frequent queries.
        Schema::table('colleges', fn (Blueprint $table) => $table->index(['status', 'sort_order']));
        Schema::table('programs', function (Blueprint $table) {
            $table->index(['college_id', 'status', 'sort_order']);
            $table->index(['is_featured', 'status']);
            $table->index('degree_type');
        });
        Schema::table('faculty_members', fn (Blueprint $table) => $table->index(['college_id', 'is_published', 'sort_order']));
        Schema::table('academic_events', fn (Blueprint $table) => $table->index(['is_published', 'starts_at']));
        Schema::table('documents', fn (Blueprint $table) => $table->index(['college_id', 'is_public']));
        Schema::table('news', fn (Blueprint $table) => $table->index(['college_id', 'status', 'published_at']));
    }

    public function down(): void
    {
        Schema::table('news', fn (Blueprint $table) => $table->dropIndex(['college_id', 'status', 'published_at']));
        Schema::table('documents', fn (Blueprint $table) => $table->dropIndex(['college_id', 'is_public']));
        Schema::table('academic_events', fn (Blueprint $table) => $table->dropIndex(['is_published', 'starts_at']));
        Schema::table('faculty_members', fn (Blueprint $table) => $table->dropIndex(['college_id', 'is_published', 'sort_order']));
        Schema::table('programs', function (Blueprint $table) {
            $table->dropIndex(['college_id', 'status', 'sort_order']);
            $table->dropIndex(['is_featured', 'status']);
            $table->dropIndex(['degree_type']);
        });
        Schema::table('colleges', fn (Blueprint $table) => $table->dropIndex(['status', 'sort_order']));
        Schema::dropIfExists('terms');
        Schema::dropIfExists('site_settings');
    }
};
