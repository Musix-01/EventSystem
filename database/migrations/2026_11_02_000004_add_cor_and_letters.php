<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Certificate of Registration (COR) uploaded at sign-up. Stored privately.
        Schema::table('users', function (Blueprint $table) {
            $table->string('cor_path')->nullable()->after('password');
        });

        // The official request letter that comes with every event request.
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            $table->text('purpose');
            $table->string('attachment_path')->nullable();   // optional signed scan
            $table->timestamp('read_at')->nullable();        // set when the admin opens it
            $table->timestamps();
        });

        // SR is the campus branch, so fix the two test numbers that used OF / ST.
        DB::table('users')->where('student_number', '2024-00001-OF-0')->update(['student_number' => '2024-00001-SR-0']);
        DB::table('users')->where('student_number', '2024-00002-ST-0')->update(['student_number' => '2024-00002-SR-0']);
    }

    public function down(): void
    {
        Schema::dropIfExists('letters');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cor_path');
        });
    }
};
