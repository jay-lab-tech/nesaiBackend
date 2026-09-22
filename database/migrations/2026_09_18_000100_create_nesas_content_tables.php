<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            $table->json('social_links')->nullable();
            $table->timestamps();
        });

        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->timestamps();
        });

        Schema::create('major_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('alumni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('headline')->nullable();
            $table->text('story')->nullable();
            $table->timestamps();
        });

        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('ppdb', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('requirements')->nullable();
            $table->json('schedule')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->index();
            $table->string('module')->nullable()->index();
            $table->longText('body')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('ppdb');
        Schema::dropIfExists('news');
        Schema::dropIfExists('alumni');
        Schema::dropIfExists('careers');
        Schema::dropIfExists('major_subjects');
        Schema::dropIfExists('majors');
        Schema::dropIfExists('schools');
    }
};
