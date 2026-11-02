<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 6);                       // 6-digit check-in code
            $table->timestamp('attended_at')->nullable();    // set when an officer scans or enters the code
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);  // a student joins an event once
            $table->unique(['event_id', 'code']);     // codes are unique within an event
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
