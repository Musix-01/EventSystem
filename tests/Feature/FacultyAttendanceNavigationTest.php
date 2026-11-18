<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyAttendanceNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_event_cards_open_the_attendance_page(): void
    {
        $faculty = User::factory()->create([
            'role' => Role::Faculty->value,
            'student_number' => null,
            'organization' => null,
        ]);
        $event = Event::create([
            'requested_by' => User::factory()->create(['role' => Role::Officer->value])->id,
            'organization' => 'ELITE',
            'title' => 'Faculty attendance event',
            'venue' => 'Main Hall',
            'starts_at' => today()->addDays(2)->setTime(9, 0),
            'ends_at' => today()->addDays(2)->setTime(12, 0),
            'visibility' => EventVisibility::All->value,
            'status' => EventStatus::Approved->value,
        ]);
        $event->schedules()->create([
            'date' => today()->addDays(2)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->actingAs($faculty)
            ->get(route('events.index'))
            ->assertOk()
            ->assertSee(route('attendance.show', $event));

        $this->get(route('attendance.show', $event))
            ->assertOk()
            ->assertSee('Faculty attendance event')
            ->assertDontSee('Admins and faculty monitor events but do not join them.');
    }
}
