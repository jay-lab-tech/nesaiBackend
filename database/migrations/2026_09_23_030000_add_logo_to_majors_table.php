<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('majors', function (Blueprint $table): void {
            if (! Schema::hasColumn('majors', 'logo')) {
                $table->string('logo')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('majors', function (Blueprint $table): void {
            if (Schema::hasColumn('majors', 'logo')) {
                $table->dropColumn('logo');
            }
        });
    }
};
