{{-- Needs $upcoming: the user's joined events that have not ended yet. --}}
@if ($upcoming->isEmpty())
    <p class="note-empty">
        You have not joined an upcoming event yet. <a href="{{ route('events.index') }}">Browse events</a>
    </p>
@else
    @php($next = $upcoming->first())
    <section class="next" aria-label="Your next event">
        <div>
            <small>Your next event</small>
            <h2>{{ $next->event->title }}</h2>
            <p>{{ $next->event->starts_at->format('l, M j, g:i A') }} at {{ $next->event->venue }}</p>
        </div>
        <div class="next-code">
            <small>Check-in code</small>
            <strong>{{ $next->spacedCode() }}</strong>
            <a class="btn btn-sm btn-gold" href="{{ route('events.show', $next->event) }}">Show QR code</a>
        </div>
    </section>

    @if ($upcoming->count() > 1)
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Event</th><th>When</th><th>Venue</th><th class="num">Code</th></tr></thead>
                <tbody>
                @foreach ($upcoming->skip(1) as $r)
                    <tr>
                        <td><a href="{{ route('events.show', $r->event) }}">{{ $r->event->title }}</a>
                            <span class="sub">{{ $r->event->organization->value }}</span></td>
                        <td>{{ $r->event->starts_at->format('M j, g:i A') }}</td>
                        <td>{{ $r->event->venue }}</td>
                        <td class="num">{{ $r->spacedCode() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endif
