<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('npsn')->nullable();
            $t->text('address')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('accreditation')->nullable();
            $t->unsignedSmallInteger('founded_year')->nullable();
            $t->string('area_size')->nullable();
            $t->string('principal_name')->nullable();
            $t->unsignedInteger('staff_count')->default(0)->nullable();
            $t->unsignedInteger('student_count')->default(0)->nullable();
            $t->unsignedInteger('classroom_count')->default(0)->nullable();
            $t->timestamp('stats_updated_at')->nullable();
            $t->text('description')->nullable();
            $t->text('vision')->nullable();
            $t->text('mission')->nullable();
            $t->json('social_links')->nullable();
            $t->timestamps();
        });

        Schema::create('majors', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('summary')->nullable();
            $t->longText('description')->nullable();
            $t->timestamps();
        });

        Schema::create('major_subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('major_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->timestamps();
        });

        Schema::create('careers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('major_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->timestamps();
        });

        Schema::create('alumni', function (Blueprint $t) {
            $t->id();
            $t->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('headline')->nullable();
            $t->text('story')->nullable();
            $t->timestamps();
        });

        Schema::create('news', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('excerpt')->nullable();
            $t->longText('body')->nullable();
            $t->string('thumbnail')->nullable();
            $t->timestamp('published_at')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('ppdb', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            $t->json('requirements')->nullable();
            $t->json('schedule')->nullable();
            $t->boolean('is_active')->default(false);
            $t->timestamps();
        });

        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->string('question');
            $t->text('answer');
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('contents', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->string('type')->index();
            $t->string('module')->nullable()->index();
            $t->longText('body')->nullable();
            $t->boolean('is_published')->default(false);
            $t->timestamps();
        });

        Schema::create('facilities', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('category')->nullable();
            $t->timestamps();
        });

        Schema::create('extracurriculars', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('category')->nullable();
            $t->timestamps();
        });

        Schema::create('innovations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->boolean('has_haki')->default(false);
            $t->timestamps();
        });

        Schema::create('admission_stats', function (Blueprint $t) {
            $t->id();
            $t->foreignId('major_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('year');
            $t->unsignedInteger('applicant_count')->default(0);
            $t->timestamps();
            $t->unique(['major_id', 'year']);
        });

        Schema::create('alumni_tracking_stats', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('year')->unique();
            $t->decimal('employed_percent', 5, 2)->nullable();
            $t->decimal('entrepreneur_percent', 5, 2)->nullable();
            $t->decimal('college_percent', 5, 2)->nullable();
            $t->decimal('other_percent', 5, 2)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_tracking_stats');
        Schema::dropIfExists('admission_stats');
        Schema::dropIfExists('innovations');
        Schema::dropIfExists('extracurriculars');
        Schema::dropIfExists('facilities');
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