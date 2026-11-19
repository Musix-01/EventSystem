@extends('layouts.app')
@section('title', $event->title)

@section('content')
    <a class="back" href="{{ route('events.index') }}">Back to events</a>

    <div class="detail">
        <section>
            <span class="org-chip">{{ $event->organization->value }}</span>
            @if ($event->status->value !== 'approved')
                <span class="status status-{{ $event->status->value }}">{{ $event->status->label() }}</span>
            @endif
            <h1>{{ $event->title }}</h1>

            <dl class="facts">
                <div><dt>Schedule</dt><dd>@include('partials.event-schedule', ['event' => $event])</dd></div>
                <div><dt>Venue</dt><dd>{{ $event->venue }}</dd></div>
                <div><dt>Who can join</dt><dd>{{ $event->visibility->label() }}</dd></div>
                @if ($event->capacity)
                    <div><dt>Seats</dt><dd>{{ $event->registrations_count }} of {{ $event->capacity }} taken</dd></div>
                @endif
            </dl>

            @if ($event->description)
                <p class="prose">{{ $event->description }}</p>
            @endif

            @include('partials.announcements', [
                'event' => $event, 'announcements' => $announcements, 'canPost' => $canPost,
                'canModerate' => $canModerate, 'seesAnnouncements' => $seesAnnouncements,
            ])
        </section>

        <aside>
            @if ($registration)
                <div class="pass">
                    <div class="pass-top">{!! $registration->qrSvg(220) !!}</div>
                    <div class="pass-cut"></div>
                    <small>Your check-in code</small>
                    <div class="pass-code">{{ $registration->spacedCode() }}</div>
                    @php($attendedByDay = $registration->attendances->keyBy('event_schedule_id'))
                    @if ($registration->attendances->isEmpty())
                        <small>Show this to the organizer at the entrance.</small>
                    @elseif ($event->schedules->count() > 1)
                        <div class="pass-state ok">Checked in on {{ $registration->attendances->count() }} of {{ $event->schedules->count() }} days</div>
                    @else
                        <div class="pass-state ok">Checked in at {{ $registration->attendances->first()->attended_at->format('g:i A') }}</div>
                    @endif

                    @if ($event->schedules->count() > 1)
                        <ul class="day-checks" aria-label="Your attendance by day">
                            @foreach ($event->schedules as $s)
                                @php($att = $attendedByDay->get($s->id))
                                <li class="{{ $att ? 'ok' : '' }}">
                                    <span>{{ $s->date->format('M j') }}</span>
                                    <b>{{ $att ? 'Present ' . $att->attended_at->format('g:i A') : '-' }}</b>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if ($certificateReady)
                    <div class="side-actions">
                        <a class="btn btn-inline" style="width:100%;text-align:center" href="{{ route('certificate.mine', $event) }}" target="_blank">Certificate of attendance</a>
                    </div>
                @endif

                @unless ($registration->hasAttended() || $event->starts_at->isPast())
                    <div class="side-actions">
                        <form method="POST" action="{{ route('events.leave', $event) }}"
                              onsubmit="return confirm('Leave this event? Your code will stop working.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-quiet">Leave this event</button>
                        </form>
                    </div>
                @endunless
            @elseif ($blockedReason)
                <div class="side-note">{{ $blockedReason }}</div>
            @else
                <form method="POST" action="{{ route('events.join', $event) }}" class="join-form">
                    @csrf
                    <fieldset class="yearsec">
                        <legend>Your year and section <span aria-hidden="true">*</span></legend>
                        <div class="yearsec-row">
                            <div class="field {{ $errors->has('program') ? 'has-error' : '' }}">
                                <label for="program">Program</label>
                                <select id="program" name="program" required>
                                    <option value="" disabled @selected(! old('program', $prefill['program']))>Select</option>
                                    @foreach (config('campus.programs') as $p)
                                        <option value="{{ $p }}" @selected(old('program', $prefill['program']) === $p)>{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field {{ $errors->has('year') ? 'has-error' : '' }}">
                                <label for="year">Year</label>
                                <select id="year" name="year" required>
                                    <option value="" disabled @selected(! old('year', $prefill['year']))>Year</option>
                                    @foreach (config('campus.years') as $y)
                                        <option value="{{ $y }}" @selected((string) old('year', $prefill['year']) === (string) $y)>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field {{ $errors->has('section') ? 'has-error' : '' }}">
                                <label for="section">Section</label>
                                <select id="section" name="section" required>
                                    <option value="" disabled @selected(! old('section', $prefill['section']))>Section</option>
                                    @foreach (config('campus.sections') as $sec)
                                        <option value="{{ $sec }}" @selected((string) old('section', $prefill['section']) === (string) $sec)>{{ $sec }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <p class="yearsec-preview" id="yearsec-preview" aria-live="polite"></p>
                        @foreach (['program', 'year', 'section'] as $f)
                            @error($f) <div class="error">{{ $message }}</div> @enderror
                        @endforeach
                    </fieldset>

                    <button type="submit" class="btn">Join this event</button>
                </form>
                <script>
                    // Shows "You are joining as BSIT 3-1" so the student can double-check.
                    (function () {
                        const ids = ['program', 'year', 'section'].map(id => document.getElementById(id));
                        const out = document.getElementById('yearsec-preview');
                        function update() {
                            const [p, y, s] = ids.map(el => el.value);
                            out.textContent = (p && y && s) ? 'Joining as ' + p + ' ' + y + '-' + s : '';
                        }
                        ids.forEach(el => el.addEventListener('change', update));
                        update();
                    })();
                </script>
                <p class="hint" style="margin-top:.6rem;color:var(--muted)">You will get a QR code and a 6-digit code to show at the entrance.</p>
            @endif
        </aside>
    </div>
@endsection
