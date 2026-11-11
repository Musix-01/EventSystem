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
            $table->string('student_number')->nullable()->change();
            $table->string('organization')->nullable()->change();

            $table->string('email')->nullable()->unique()->after('name');
        });

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
