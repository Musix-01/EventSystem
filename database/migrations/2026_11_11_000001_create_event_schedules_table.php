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
        Schema::create('event_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->unique(['event_id', 'date']);
            $table->index(['date', 'start_time', 'end_time']);
        });

        DB::table('events')->orderBy('id')->chunk(500, function ($events) {
            foreach ($events as $event) {
                $startsAt = Carbon::parse($event->starts_at);
                $endsAt = Carbon::parse($event->ends_at);

                for ($date = $startsAt->copy()->startOfDay(); $date->lte($endsAt->copy()->startOfDay()); $date->addDay()) {
                    DB::table('event_schedules')->insert([
                        'event_id' => $event->id,
                        'date' => $date->toDateString(),
                        'start_time' => $startsAt->format('H:i:s'),
                        'end_time' => $endsAt->format('H:i:s'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_schedules');
    }
};
