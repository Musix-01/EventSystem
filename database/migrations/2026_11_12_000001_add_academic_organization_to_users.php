<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // When the admin makes someone a Student Council officer, their academic organization
        // (ELITE, JPIA, ...) is kept here so they can still join its events and be moved back later.
        Schema::table('users', function (Blueprint $table) {
            $table->string('academic_organization')->nullable()->after('organization');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('academic_organization');
        });
    }
};
