{{-- $items: collection of ['badge', 'who', 'text', 'at'] --}}
<section class="panel">
    <div class="panel-head"><h2>Recent activity</h2></div>
    @if ($items->isEmpty())
        <p class="muted-note">Nothing yet. Activity shows up here.</p>
    @else
        <ul class="activity">
            {{-- .take(5) ensures only the first 5 records are printed --}}
            @foreach ($items->take(5) as $item)
                <li>
                    <span class="av av{{ crc32($item['who']) % 4 }}" aria-hidden="true">{{ $item['badge'] }}</span>
                    <div>
                        <p><strong>{{ $item['who'] }}</strong> {{ $item['text'] }}</p>
                        <time datetime="{{ $item['at']->toIso8601String() }}">{{ $item['at']->diffForHumans() }}</time>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
