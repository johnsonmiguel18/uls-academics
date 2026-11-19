<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Intro text of each SDG's own page (/sdgs/{number}); other lists leave it empty.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms', fn (Blueprint $table) => $table->text('description')->nullable()->after('color'));
    }

    public function down(): void
    {
        Schema::table('terms', fn (Blueprint $table) => $table->dropColumn('description'));
    }
};
