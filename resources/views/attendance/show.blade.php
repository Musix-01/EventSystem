@extends('layouts.app')
@section('title', 'Attendance')

@section('content')
    @php
        $isMulti = $event->schedules->count() > 1;
        $present = $registrations->filter(fn ($r) => $r->day_attended_at !== null)->count();
        $dayNumber = $event->schedules->search(fn ($s) => $s->id === $day->id) + 1;
    @endphp

    <div class="page-head">
        <div>
            <h1>{{ $event->title }}</h1>
            <p>
                {{ $event->organization->value }}, {{ $event->venue }}
            </p>
            @include('partials.event-schedule', ['event' => $event])
        </div>
        <div class="cell-actions">
            <a href="{{ route('attendance.print', array_filter([$event, 'day' => $day->id, 'section' => $section])) }}" target="_blank">Print {{ $isMulti ? 'this day' : 'attendance sheet' }}</a>
            <a href="{{ route('attendance.print', array_filter([$event, 'day' => $day->id, 'only' => 'present', 'section' => $section])) }}" target="_blank">Print present only</a>
            @if ($isMulti)
                <a href="{{ route('attendance.print', array_filter([$event, 'day' => 'all', 'section' => $section])) }}" target="_blank">Print all days</a>
            @endif
        </div>
    </div>

    @if ($isMulti)
        <nav class="day-tabs" aria-label="Event days">
            @foreach ($event->schedules as $s)
                <a href="{{ route('attendance.show', array_filter([$event, 'day' => $s->id, 'section' => $section])) }}"
                   @if ($s->id === $day->id) aria-current="page" @endif>
                    <strong>Day {{ $loop->iteration }}</strong>
                    <span>{{ $s->date->format('M j') }}@if ($s->date->isToday()), today @endif</span>
                    <b>{{ $dayCounts[$s->id] ?? 0 }}</b>
                </a>
            @endforeach
        </nav>
    @endif

    <p class="day-note">
        Checking in for <strong>{{ $isMulti ? 'Day ' . $dayNumber . ', ' : '' }}{{ $day->date->format('l, F j') }}</strong>
        ({{ \Illuminate\Support\Carbon::parse($day->start_time)->format('g:i A') }} to {{ \Illuminate\Support\Carbon::parse($day->end_time)->format('g:i A') }}).
        @unless ($day->date->isToday())
            <span class="day-warn">This is not today's session.</span>
        @endunless
    </p>

    <div class="stats">
        <div class="stat">
            <strong>{{ $registrations->count() }}@if ($event->capacity)<small style="font-size:1rem;color:var(--muted)"> / {{ $event->capacity }}</small>@endif</strong>
            <span>Registered</span>
        </div>
        <div class="stat"><strong id="stat-present">{{ $present }}</strong><span>Checked in</span></div>
        <div class="stat"><strong id="stat-notyet">{{ $registrations->count() - $present }}</strong><span>Not yet</span></div>
    </div>

    @if ($totalAll > 0)
        <nav class="sections" aria-label="Filter by year and section">
            <a href="{{ route('attendance.show', array_filter([$event, 'day' => $day->id])) }}" @if(! $section) aria-current="true" @endif>
                All <b>{{ $totalAll }}</b>
            </a>
            @foreach ($sections as $label => $group)
                @if ($label !== 'No section')
                    <a href="{{ route('attendance.show', [$event, 'day' => $day->id, 'section' => $label]) }}" @if($section === $label) aria-current="true" @endif>
                        {{ $label }} <b>{{ $group->filter(fn ($r) => $r->day_attended_at !== null)->count() }}/{{ $group->count() }}</b>
                    </a>
                @else
                    <span class="sections-none">No section <b>{{ $group->count() }}</b></span>
                @endif
            @endforeach
        </nav>
    @endif

    @if ($canManage)
        <form class="checkin" method="POST" action="{{ route('attendance.checkin', $event) }}">
            @csrf
            <input type="hidden" name="day" value="{{ $day->id }}">
            <div class="field {{ $errors->has('code') ? 'has-error' : '' }}">
                <label for="code">Check in with a 6-digit code</label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" inputmode="numeric"
                       autocomplete="off" placeholder="482 193" autofocus>
                @error('code') <div class="error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn" style="margin-top:1.7rem">Check in</button>
            <button type="button" id="scan-start" class="btn btn-quiet" style="margin-top:1.7rem">Scan with camera</button>
        </form>

        <section id="scanner" class="scanner" hidden aria-label="Camera scanner">
            <div id="reader"></div>
            <div id="scan-msg" class="scan-msg" role="status" aria-live="polite"></div>
            <button type="button" id="scan-stop" class="btn btn-quiet btn-sm">Stop camera</button>
        </section>
    @endif

    @if ($registrations->isEmpty())
        <div class="empty">Nobody has joined this event yet.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th><th>Student</th><th>Year &amp; Section</th><th>Organization</th><th>Status{{ $isMulti ? ' (this day)' : '' }}</th>
                        @if ($isMulti)<th>Days</th>@endif
                        @if ($canManage)<th>Action</th>@endif
                    </tr>
                </thead>
                <tbody>
                @foreach ($registrations as $r)
                    <tr id="reg-{{ $r->id }}">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $r->user->name }}<span class="sub">{{ $r->user->student_number }}</span></td>
                        <td>{{ $r->year_section ?? '-' }}</td>
                        <td>{{ $r->user->organization?->value }}</td>
                        <td data-status>
                            @if ($r->day_attended_at)
                                <span class="tag tag-joined">Checked in {{ $r->day_attended_at->format('g:i A') }}</span>
                            @else
                                <span class="tag">Not yet</span>
                            @endif
                        </td>
                        @if ($isMulti)
                            <td>{{ $r->attendances->count() }} / {{ $event->schedules->count() }}</td>
                        @endif
                        @if ($canManage)
                            <td>
                                <div class="cell-actions">
                                    <form class="inline-form" method="POST" action="{{ route('attendance.toggle', [$event, $r]) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="day" value="{{ $day->id }}">
                                        @if ($r->day_attended_at)
                                            <button type="submit" class="link-btn danger" data-toggle>Undo</button>
                                        @else
                                            <button type="submit" class="link-btn" data-toggle>Mark present</button>
                                        @endif
                                    </form>
                                    @if ($r->attendances->isNotEmpty() && $event->ends_at->isPast())
                                        <a href="{{ route('certificate.student', [$event, $r]) }}" target="_blank">Certificate</a>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($canManage)
        <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script>
        (function () {
            const SCAN_URL = {{ \Illuminate\Support\Js::from(route('attendance.scan', $event)) }};
            const TOKEN    = {{ \Illuminate\Support\Js::from(csrf_token()) }};
            const DAY_ID   = {{ \Illuminate\Support\Js::from($day->id) }};

            const startBtn = document.getElementById('scan-start');
            const stopBtn  = document.getElementById('scan-stop');
            const panel    = document.getElementById('scanner');
            const msg      = document.getElementById('scan-msg');

            let scanner = null, busy = false, lastText = '', lastAt = 0;

            function show(kind, text) {
                msg.className = 'scan-msg ' + kind;
                msg.textContent = text;           // textContent: names are never treated as HTML
            }

            function updateRow(data) {
                const row = document.getElementById('reg-' + data.registration_id);
                if (!row) return;
                const cell = row.querySelector('[data-status]');
                if (cell) {
                    const tag = document.createElement('span');
                    tag.className = 'tag tag-joined';
                    tag.textContent = 'Checked in ' + data.attended_at;
                    cell.replaceChildren(tag);
                }
                const btn = row.querySelector('[data-toggle]');
                if (btn) { btn.textContent = 'Undo'; btn.classList.add('danger'); }
                row.classList.add('just-scanned');
            }

            function updateCounts(data) {
                const p = document.getElementById('stat-present');
                const n = document.getElementById('stat-notyet');
                if (p) p.textContent = data.present;
                if (n) n.textContent = data.total - data.present;
            }

            async function onScan(text) {
                const now = Date.now();
                if (busy || (text === lastText && now - lastAt < 4000)) return;
                busy = true; lastText = text; lastAt = now;

                try {
                    const res = await fetch(SCAN_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': TOKEN
                        },
                        body: JSON.stringify({ code: text, day: DAY_ID })
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();

                    show(data.status, data.message);
                    updateCounts(data);
                    if (data.status === 'ok') {
                        updateRow(data);
                        if (navigator.vibrate) navigator.vibrate(80);
                    }
                } catch (e) {
                    show('error', 'Could not reach the server. Reload the page and try again.');
                }

                setTimeout(function () { busy = false; }, 1200);
            }

            async function start() {
                panel.hidden = false;
                startBtn.hidden = true;

                const local = ['localhost', '127.0.0.1'].includes(location.hostname);
                if (!window.isSecureContext && !local) {
                    show('error', 'Browsers only allow the camera on HTTPS or localhost. Use the code box, or open this page over HTTPS.');
                    return;
                }

                show('', 'Starting camera...');
                scanner = new Html5Qrcode('reader');
                try {
                    await scanner.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 240, height: 240 } },
                        onScan,
                        function () {}
                    );
                    show('', 'Point the camera at the student\u2019s QR code.');
                } catch (e) {
                    scanner = null;
                    show('error', 'Could not start the camera. Allow camera access for this site, or use the code box.');
                }
            }

            async function stop() {
                if (scanner) {
                    try { await scanner.stop(); scanner.clear(); } catch (e) {}
                    scanner = null;
                }
                panel.hidden = true;
                startBtn.hidden = false;
            }

            startBtn.addEventListener('click', start);
            stopBtn.addEventListener('click', stop);
        })();
        </script>
    @endif
@endsection
