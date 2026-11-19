<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per student per event day. registrations.attended_at stays as
        // "checked in on at least one day" and is kept in sync by the app.
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_schedule_id')->constrained('event_schedules')->cascadeOnDelete();
            $table->timestamp('attended_at');
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['registration_id', 'event_schedule_id']);
        });

        // Existing check-ins become attendance for the day they happened on (or the first day).
        DB::table('registrations')->whereNotNull('attended_at')->orderBy('id')->chunk(200, function ($registrations) {
            foreach ($registrations as $registration) {
                $days = DB::table('event_schedules')
                    ->where('event_id', $registration->event_id)
                    ->orderBy('date')
                    ->get();

                if ($days->isEmpty()) {
                    continue;
                }

                $attendedDate = Carbon::parse($registration->attended_at)->toDateString();
                $day = $days->first(fn ($d) => substr((string) $d->date, 0, 10) === $attendedDate) ?? $days->first();

                DB::table('attendances')->insert([
                    'registration_id'   => $registration->id,
                    'event_schedule_id' => $day->id,
                    'attended_at'       => $registration->attended_at,
                    'checked_in_by'     => $registration->checked_in_by,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
