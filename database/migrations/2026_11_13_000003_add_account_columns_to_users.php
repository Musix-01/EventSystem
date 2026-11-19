<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Set when the admin gives someone a temporary password. They must pick their own at next login.
            $table->boolean('must_change_password')->default(false)->after('password');

            // The admin checks the uploaded COR and marks the student verified.
            $table->timestamp('cor_verified_at')->nullable()->after('cor_path');
            $table->foreignId('cor_verified_by')->nullable()->after('cor_verified_at')->constrained('users')->nullOnDelete();

            // The student's default program, year and section ("BSIT 3-1"), used to fill the join form.
            $table->string('year_section')->nullable()->after('organization');
        });

        // Accounts that already exist were accepted before verification existed, so keep them working.
        DB::table('users')->whereIn('role', ['student', 'officer'])->update(['cor_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cor_verified_by');
            $table->dropColumn(['must_change_password', 'cor_verified_at', 'year_section']);
        });
    }
};
