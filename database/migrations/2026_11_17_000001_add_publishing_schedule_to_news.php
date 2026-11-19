<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Publishing workflow for news: draft -> review -> scheduled -> published -> archived,
 * plus an optional take-down date (unpublish_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->timestamp('unpublish_at')->nullable()->after('published_at');
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropIndex(['status', 'published_at']);
            $table->dropColumn('unpublish_at');
        });
    }
};
