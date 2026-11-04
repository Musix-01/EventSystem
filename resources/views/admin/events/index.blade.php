@extends('layouts.app')
@section('title', 'Event requests')

@section('content')
    <div class="page-head">
        <div>
            <h1>Event requests</h1>
            <p>Review what student org officers submitted.</p>
        </div>
    </div>

    <nav class="tabs" aria-label="Filter by status">
        @foreach ($statuses as $s)
            <a href="{{ route('admin.events.index', ['status' => $s->value]) }}"
               @if ($s === $current) aria-current="page" @endif>
                {{ $s->label() }}<span class="count">{{ $counts[$s->value] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    @forelse ($events as $event)
        @php($conflict = $event->isPending() ? $event->venueConflict() : null)
        <article class="event">
            <div class="event-top">
                <div>
                    <h3>{{ $event->title }}</h3>
                    <p class="meta">
                        {{ $event->organization->value }}, requested by {{ $event->requester->name }}
                        ({{ $event->requester->student_number }})
                    </p>
                    <p class="meta">
                        {{ $event->starts_at->format('M j, Y, g:i A') }} to {{ $event->ends_at->format('g:i A') }}
                        at {{ $event->venue }}
                    </p>
                    <p class="meta">
                        {{ $event->visibility->label() }}
                        @if ($event->capacity), limit {{ $event->capacity }} students @endif
                    </p>
                </div>
                <div><span class="status status-{{ $event->status->value }}">{{ $event->status->label() }}</span></div>
            </div>

            @if ($event->description) <p class="desc">{{ $event->description }}</p> @endif

            @if ($conflict)
                <div class="warn">
                    <strong>Venue clash:</strong> {{ $conflict->venue }} is already booked for
                    “{{ $conflict->title }}” ({{ $conflict->organization->value }}),
                    {{ $conflict->starts_at->format('M j, g:i A') }} to {{ $conflict->ends_at->format('g:i A') }}.
                </div>
            @endif

            @if ($event->letter)
                <p class="meta" style="margin-top:.75rem">
                    <a href="{{ route('letters.show', $event->letter) }}">Read request letter</a>
                    @if ($event->letter->attachment_path)
                        , <a href="{{ route('letters.attachment', $event->letter) }}" target="_blank" rel="noopener">signed copy</a>
                    @endif
                </p>
            @endif

            @if ($event->reviewed_at)
                <p class="meta" style="margin-top:.75rem">
                    Reviewed by {{ $event->reviewer?->name ?? 'a removed admin' }}
                    on {{ $event->reviewed_at->format('M j, Y, g:i A') }}
                </p>
            @endif

            @if ($event->decline_reason)
                <div class="reason"><strong>Reason:</strong> {{ $event->decline_reason }}</div>
            @endif

            @if ($event->isPending())
                <div class="event-actions">
                    <form method="POST" action="{{ route('admin.events.approve', $event) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm">Approve</button>
                    </form>

                    <details class="decline" @if ($errors->has('decline_reason') && old('event_id') == $event->id) open @endif>
                        <summary class="btn btn-sm btn-danger">Decline</summary>
                        <form method="POST" action="{{ route('admin.events.decline', $event) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="event_id" value="{{ $event->id }}">
                            <textarea name="decline_reason" rows="3" placeholder="Tell the officers why" required>{{ old('event_id') == $event->id ? old('decline_reason') : '' }}</textarea>
                            @if ($errors->has('decline_reason') && old('event_id') == $event->id)
                                <div class="error">{{ $errors->first('decline_reason') }}</div>
                            @endif
                            <button type="submit" class="btn btn-sm btn-danger">Decline event</button>
                        </form>
                    </details>
                </div>
            @endif
        </article>
    @empty
        <div class="empty">
            @if ($current->value === 'pending') No requests waiting for review.
            @else No {{ $current->value }} events. @endif
        </div>
    @endforelse
@endsection
