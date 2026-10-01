<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (! Schema::hasColumn('schools', 'npsn')) {
                $table->string('npsn')->nullable();
            }
            if (! Schema::hasColumn('schools', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('schools', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (! Schema::hasColumn('schools', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('schools', 'accreditation')) {
                $table->string('accreditation')->nullable();
            }
            if (! Schema::hasColumn('schools', 'founded_year')) {
                $table->unsignedSmallInteger('founded_year')->nullable();
            }
            if (! Schema::hasColumn('schools', 'area_size')) {
                $table->string('area_size')->nullable();
            }
            if (! Schema::hasColumn('schools', 'principal_name')) {
                $table->string('principal_name')->nullable();
            }
            if (! Schema::hasColumn('schools', 'staff_count')) {
                $table->unsignedInteger('staff_count')->nullable();
            }
            if (! Schema::hasColumn('schools', 'student_count')) {
                $table->unsignedInteger('student_count')->nullable();
            }
            if (! Schema::hasColumn('schools', 'classroom_count')) {
                $table->unsignedInteger('classroom_count')->nullable();
            }
            if (! Schema::hasColumn('schools', 'stats_updated_at')) {
                $table->timestamp('stats_updated_at')->nullable();
            }
        });

        if (! Schema::hasTable('facilities')) {
            Schema::create('facilities', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('category')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('extracurriculars')) {
            Schema::create('extracurriculars', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('category')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('innovations')) {
            Schema::create('innovations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('has_haki')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admission_stats')) {
            Schema::create('admission_stats', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('major_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('year');
                $table->unsignedInteger('applicant_count');
                $table->timestamps();
                $table->unique(['major_id', 'year']);
            });
        }

        if (! Schema::hasTable('alumni_tracking_stats')) {
            Schema::create('alumni_tracking_stats', function (Blueprint $table): void {
                $table->id();
                $table->unsignedSmallInteger('year')->unique();
                $table->decimal('employed_percent', 5, 2)->nullable();
                $table->decimal('entrepreneur_percent', 5, 2)->nullable();
                $table->decimal('college_percent', 5, 2)->nullable();
                $table->decimal('other_percent', 5, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_tracking_stats');
        Schema::dropIfExists('admission_stats');
        Schema::dropIfExists('innovations');
        Schema::dropIfExists('extracurriculars');
        Schema::dropIfExists('facilities');

        $columns = [
            'npsn', 'address', 'phone', 'email', 'accreditation',
            'founded_year', 'area_size', 'principal_name', 'staff_count',
            'student_count', 'classroom_count', 'stats_updated_at',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('schools', $column)) {
                Schema::table('schools', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
