<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Actor: the user who performed the action.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Snapshots remain available if the user is later deleted.
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->json('actor_roles')->nullable();

            // Examples: page.created, news.updated, auth.login, auth.failed.
            $table->string('event', 100)->index();
            $table->text('description');

            // The record affected by the action, if any.
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Safe, relevant details about the action.
            $table->json('properties')->nullable();

            // Request information, where available.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('route_name')->nullable();
            $table->string('url_path')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
