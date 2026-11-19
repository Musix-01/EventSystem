{{-- $event, $announcements, $canPost, $canModerate, $seesAnnouncements --}}
<section class="announcements" id="announcements" aria-labelledby="announcements-title">
    <h2 id="announcements-title">Announcements</h2>

    @if (! $seesAnnouncements)
        <p class="muted-note">Students who join this event can read its announcements here.</p>
    @else
        @if ($canPost)
            <form class="announce-form" method="POST" action="{{ route('announcements.store', $event) }}" novalidate>
                @csrf
                <div class="field {{ $errors->has('title') ? 'has-error' : '' }}">
                    <label for="announce-title">Title</label>
                    <input type="text" id="announce-title" name="title" value="{{ old('title') }}" maxlength="120" required>
                    @error('title') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field {{ $errors->has('body') ? 'has-error' : '' }}">
                    <label for="announce-body">Message</label>
                    <textarea id="announce-body" name="body" rows="3" maxlength="2000" required>{{ old('body') }}</textarea>
                    @error('body') <div class="error">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-sm btn-inline">Post announcement</button>
            </form>
        @endif

        @forelse ($announcements as $a)
            <article class="announcement">
                <header>
                    <h3>{{ $a->title }}</h3>
                    <time datetime="{{ $a->created_at->toIso8601String() }}" title="{{ $a->created_at->format('M j, Y, g:i A') }}">{{ $a->created_at->diffForHumans() }}</time>
                </header>
                <p>{{ $a->body }}</p>
                <footer>
                    <span>Posted by {{ $a->author?->name ?? 'an organizer' }}</span>
                    @if ($canModerate)
                        <form class="inline-form" method="POST" action="{{ route('announcements.destroy', $a) }}"
                              onsubmit="return confirm('Remove this announcement?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-btn danger">Remove</button>
                        </form>
                    @endif
                </footer>
            </article>
        @empty
            <p class="muted-note">No announcements yet.</p>
        @endforelse
    @endif
</section>
