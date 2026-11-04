{{-- $event, $href, $variant (ongoing|upcoming), optional $line, optional $tag.
     Reads $event->avatarUsers and $event->registrations_count when they are loaded. --}}
@php
    $variant = $variant ?? 'upcoming';
    $palette = crc32($event->organization->value) % 4;
    $users   = $event->avatarUsers ?? collect();
    $more    = max(0, ($event->registrations_count ?? 0) - $users->count());
@endphp
<a class="ecard" href="{{ $href }}">
    <div class="banner b{{ $palette }}" aria-hidden="true"><span>{{ $event->organization->value }}</span></div>
    <div class="ecard-body">
        @if ($variant === 'upcoming')
            <span class="date-pill">{{ $event->starts_at->format('j M Y') }}</span>
        @endif
        <h3>{{ $event->title }}</h3>
        <p class="clock">
            @include('partials.icon', ['name' => 'clock', 'size' => 15])
            @if ($variant === 'ongoing')
                {{ $event->starts_at->format('M j, g:i A') }} to {{ $event->ends_at->format('g:i A') }}
            @else
                {{ $event->starts_at->format('g:i A') }} to {{ $event->ends_at->format('g:i A') }}
            @endif
        </p>
        @if (! empty($line)) <p class="eline">{{ $line }}</p> @endif
        @if ($users->isNotEmpty())
            <div class="avs" aria-label="{{ $event->registrations_count ?? $users->count() }} registered">
                @foreach ($users as $u)
                    <span class="av av{{ crc32($u->name) % 4 }}" title="{{ $u->name }}">{{ $u->initials() }}</span>
                @endforeach
                @if ($more > 0) <span class="av more">+{{ $more }}</span> @endif
            </div>
        @endif
        @if (! empty($tag)) <span class="tag tag-joined">{{ $tag }}</span> @endif
    </div>
</a>
