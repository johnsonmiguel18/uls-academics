<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Loading-speed measurements from real visits (Core Web Vitals), kept 90 days. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_vitals', function (Blueprint $table) {
            $table->id();
            $table->string('metric', 8);      // LCP, INP, CLS, FCP, TTFB
            $table->float('value');
            $table->string('device', 8);      // mobile, desktop
            $table->string('path', 255);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['metric', 'created_at', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_vitals');
    }
};
