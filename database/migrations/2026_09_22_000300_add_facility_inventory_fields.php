<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (! Schema::hasColumn('schools', 'classroom_count_min')) {
                $table->unsignedSmallInteger('classroom_count_min')->nullable();
            }
            if (! Schema::hasColumn('schools', 'classroom_count_max')) {
                $table->unsignedSmallInteger('classroom_count_max')->nullable();
            }
        });

        Schema::table('facilities', function (Blueprint $table): void {
            if (! Schema::hasColumn('facilities', 'major_id')) {
                $table->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('facilities', 'quantity')) {
                $table->unsignedSmallInteger('quantity')->default(1);
            }
            if (! Schema::hasColumn('facilities', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('facilities', 'is_placeholder')) {
                $table->boolean('is_placeholder')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table): void {
            $table->dropForeign(['major_id']);
            $table->dropColumn(['major_id', 'quantity', 'description', 'is_placeholder']);
        });

        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn(['classroom_count_min', 'classroom_count_max']);
        });
    }
};
