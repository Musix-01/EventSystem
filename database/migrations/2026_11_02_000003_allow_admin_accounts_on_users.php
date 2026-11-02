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
            // Admins belong to the PUP office: no student number, no organization.
            $table->string('student_number')->nullable()->change();
            $table->string('organization')->nullable()->change();

            // Admins sign in with an email instead.
            $table->string('email')->nullable()->unique()->after('name');
        });

        // Convert the existing admin account (if any) so you don't have to re-seed.
        $adminId = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');

        if ($adminId) {
            DB::table('users')->where('id', $adminId)->update([
                'email'          => 'admin@pup.edu.ph',
                'student_number' => null,
                'organization'   => null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn('email');
        });
    }
};
