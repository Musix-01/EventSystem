<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // e.g. "BSIT 3-1". Nullable so registrations made before this change still work.
            $table->string('year_section', 30)->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['year_section']);
            $table->dropColumn('year_section');
        });
    }
};
