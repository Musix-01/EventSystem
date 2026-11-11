<?php

namespace Tests\Feature\Officer;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_officer_can_request_an_event_with_different_times_each_day(): void
    {
        $officer = User::factory()->create(['role' => Role::Officer->value]);
        $firstDate = today()->addDays(2)->toDateString();
        $secondDate = today()->addDays(4)->toDateString();

        $response = $this->actingAs($officer)->post(route('officer.events.store'), [
            'title' => 'Marketing Week',
            'venue' => 'Main Hall',
            'schedule' => [
                ['date' => $firstDate, 'start_time' => '09:00', 'end_time' => '12:00'],
                ['date' => $secondDate, 'start_time' => '13:00', 'end_time' => '17:00'],
            ],
            'visibility' => EventVisibility::Org->value,
            'purpose' => 'A week of marketing activities for students.',
        ]);

        $response->assertRedirect(route('officer.events.index'));
        $event = Event::query()->where('title', 'Marketing Week')->firstOrFail();
        $this->assertSame($firstDate.' 09:00:00', $event->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame($secondDate.' 17:00:00', $event->ends_at->format('Y-m-d H:i:s'));
        $this->assertTrue(Event::query()->whereKey($event->id)->hasUpcomingSchedule()->exists());
        $this->assertFalse(Event::query()->whereKey($event->id)->happeningNow()->exists());
        $this->assertDatabaseHas('event_schedules', [
            'event_id' => $event->id,
            'date' => $firstDate,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);
        $this->assertDatabaseHas('event_schedules', [
            'event_id' => $event->id,
            'date' => $secondDate,
            'start_time' => '13:00:00',
            'end_time' => '17:00:00',
        ]);

        $this->get(route('officer.events.index'))
            ->assertOk()
            ->assertSee(today()->addDays(2)->format('l, F j, Y'))
            ->assertSee(today()->addDays(4)->format('l, F j, Y'))
            ->assertSee('9:00 AM to 12:00 PM')
            ->assertSee('1:00 PM to 5:00 PM');
        $this->get(route('officer.events.create'))->assertOk()->assertSee('Add another day');
    }

    public function test_officer_cannot_request_a_day_that_ends_before_it_starts(): void
    {
        $officer = User::factory()->create(['role' => Role::Officer->value]);

        $response = $this->actingAs($officer)->from(route('officer.events.create'))
            ->post(route('officer.events.store'), [
                'title' => 'Invalid schedule',
                'venue' => 'Main Hall',
                'schedule' => [
                    [
                        'date' => today()->addDay()->toDateString(),
                        'start_time' => '16:00',
                        'end_time' => '09:00',
                    ],
                ],
                'visibility' => EventVisibility::Org->value,
                'purpose' => 'A valid length purpose description.',
            ]);

        $response->assertRedirect(route('officer.events.create'));
        $response->assertSessionHasErrors(['schedule.0.end_time']);
        $this->assertDatabaseMissing('events', ['title' => 'Invalid schedule']);
        $this->get(route('officer.events.create'))
            ->assertOk()
            ->assertSee('The end time must be later than the start time on the same day.');
    }

    public function test_venue_conflicts_are_checked_for_each_scheduled_day(): void
    {
        $officer = User::factory()->create(['role' => Role::Officer->value]);
        $approvedEvent = Event::create([
            'requested_by' => $officer->id,
            'organization' => $officer->organization->value,
            'title' => 'Existing event',
            'venue' => 'Main Hall',
            'starts_at' => today()->addDays(2)->setTime(9, 0),
            'ends_at' => today()->addDays(2)->setTime(12, 0),
            'visibility' => EventVisibility::Org->value,
            'status' => EventStatus::Approved->value,
        ]);
        $approvedEvent->schedules()->create([
            'date' => today()->addDays(2)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);
        $requestedEvent = Event::create([
            'requested_by' => $officer->id,
            'organization' => $officer->organization->value,
            'title' => 'Overlapping event',
            'venue' => 'MAIN HALL',
            'starts_at' => today()->addDays(2)->setTime(11, 0),
            'ends_at' => today()->addDays(2)->setTime(14, 0),
            'visibility' => EventVisibility::Org->value,
            'status' => EventStatus::Pending->value,
        ]);
        $requestedEvent->schedules()->create([
            'date' => today()->addDays(2)->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '14:00:00',
        ]);

        $conflict = $requestedEvent->venueConflict();

        $this->assertNotNull($conflict);
        $this->assertSame($approvedEvent->id, $conflict->event->id);

        $admin = User::factory()->create([
            'role' => Role::Admin->value,
            'student_number' => null,
            'organization' => null,
        ]);
        $this->actingAs($admin)
            ->get(route('admin.events.index'))
            ->assertOk()
            ->assertSee('Venue clash')
            ->assertSee('Existing event');
    }

    public function test_event_is_only_ongoing_during_its_scheduled_hours(): void
    {
        $this->travelTo(now()->startOfDay()->setTime(10, 0));
        $officer = User::factory()->create(['role' => Role::Officer->value]);
        $event = Event::create([
            'requested_by' => $officer->id,
            'organization' => $officer->organization->value,
            'title' => 'Scheduled event',
            'venue' => 'Main Hall',
            'starts_at' => today()->setTime(9, 0),
            'ends_at' => today()->setTime(11, 0),
            'visibility' => EventVisibility::Org->value,
            'status' => EventStatus::Approved->value,
        ]);
        $event->schedules()->create([
            'date' => today()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        $this->assertTrue(Event::query()->whereKey($event->id)->happeningNow()->exists());

        $this->travelTo(now()->startOfDay()->setTime(12, 0));

        $this->assertFalse(Event::query()->whereKey($event->id)->happeningNow()->exists());
    }
}
