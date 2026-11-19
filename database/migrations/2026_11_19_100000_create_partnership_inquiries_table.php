<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Messages sent from the Global Engagement site's "Let's connect" form (Admin → Inquiries).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partnership_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('organization')->nullable();
            $table->string('interest')->nullable();
            $table->text('message');
            $table->string('status')->default('new')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partnership_inquiries');
    }
};
