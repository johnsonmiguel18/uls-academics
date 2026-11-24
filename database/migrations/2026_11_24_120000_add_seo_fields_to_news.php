<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Search and sharing fields for news (filled in automatically when left empty; see App\Support\NewsSeo). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('meta_title', 120)->nullable()->after('excerpt');
            $table->string('meta_description', 320)->nullable()->after('meta_title');
            $table->string('og_title', 200)->nullable()->after('meta_description');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('keywords', 255)->nullable()->after('og_description');
            $table->json('tags')->nullable()->after('keywords');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'og_title', 'og_description', 'keywords', 'tags']);
        });
    }
};
