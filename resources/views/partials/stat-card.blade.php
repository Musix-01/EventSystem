{{-- icon, tone (maroon|gold|green), title, value, caption, optional href --}}
@php($tagName = ! empty($href) ? 'a' : 'div')
<{{ $tagName }} class="stat-card tone-{{ $tone }}" @if (! empty($href)) href="{{ $href }}" @endif>
    <span class="stat-ico">@include('partials.icon', ['name' => $icon])</span>
    <span class="stat-text">
        <strong>{{ $title }}</strong>

    </span>
</{{ $tagName }}>
