<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Campus Events</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/campus.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/student.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/extra.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shell.css') }}">
</head>
<body>
@php($user = auth()->user())
<div class="app-frame">

    {{-- Left rail --}}
    <aside class="rail no-print">
        <a class="rail-logo" href="{{ route('dashboard') }}" aria-label="Campus Events home">

        <nav class="rail-nav" aria-label="Main">
            <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                @include('partials.icon', ['name' => 'grid'])<span>Home</span>
            </a>
            <a href="{{ route('events.index') }}" @if(request()->routeIs('events.index', 'events.show')) aria-current="page" @endif>
                @include('partials.icon', ['name' => 'calendar'])<span>Events</span>
            </a>

            @unless ($user->isStaff())
                <a href="{{ route('registrations.index') }}" @if(request()->routeIs('registrations.*')) aria-current="page" @endif>
                    @include('partials.icon', ['name' => 'ticket'])<span>My events</span>
                </a>
            @endunless

            @if ($user->isOfficer())
                <a href="{{ route('officer.events.index') }}" @if(request()->routeIs('officer.events.*', 'attendance.*', 'letters.*')) aria-current="page" @endif>
                    @include('partials.icon', ['name' => 'check'])<span>Org events</span>
                </a>
            @endif

            @if ($user->isAdmin())
                @php($pendingCount = \App\Models\Event::where('status', 'pending')->count())
                @php($unreadLetters = \App\Models\Letter::whereNull('read_at')->count())
                <a href="{{ route('admin.events.index') }}" @if(request()->routeIs('admin.events.*')) aria-current="page" @endif>
                    @include('partials.icon', ['name' => 'check'])<span>Requests</span>
                    @if ($pendingCount > 0)<b class="rail-badge">{{ $pendingCount }}</b>@endif
                </a>
                <a href="{{ route('admin.letters.index') }}" @if(request()->routeIs('admin.letters.*', 'letters.*')) aria-current="page" @endif>
                    @include('partials.icon', ['name' => 'mail'])<span>Letters</span>
                    @if ($unreadLetters > 0)<b class="rail-badge">{{ $unreadLetters }}</b>@endif
                </a>
                <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>
                    @include('partials.icon', ['name' => 'users'])<span>Users</span>
                </a>
            @endif
        </nav>

        <form class="rail-out" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">@include('partials.icon', ['name' => 'logout'])<span>Log out</span></button>
        </form>
    </aside>

    <div class="app-main">
        {{-- Top bar --}}
        <header class="top no-print">
            <h1 class="top-title">@yield('topbar-title')</h1>

            <form class="search" role="search" method="GET" action="{{ route('events.index') }}">
                @include('partials.icon', ['name' => 'search', 'size' => 18])
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search event or anything" aria-label="Search events">
            </form>

            @if ($user->isOfficer())
                <a class="btn btn-inline top-action" href="{{ route('officer.events.create') }}">@include('partials.icon', ['name' => 'plus', 'size' => 18]) Request event</a>
            @elseif ($user->isAdmin())
                <a class="btn btn-inline top-action" href="{{ route('admin.events.index') }}">Review requests</a>
            @else
                <a class="btn btn-inline top-action" href="{{ route('events.index') }}">Browse events</a>
            @endif

            <details class="usermenu">
                <summary>
                    <span class="av av0" aria-hidden="true">{{ $user->initials() }}</span>
                    <span class="um-name">{{ $user->name }}</span>
                    @include('partials.icon', ['name' => 'chevron', 'size' => 16])
                </summary>
                <div class="menu">
                    <p><strong>{{ $user->name }}</strong><br>
                       <span>{{ $user->affiliation() }}</span><br>
                       <span>{{ $user->student_number ?? $user->email }}</span></p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-quiet btn-sm">Log out</button>
                    </form>
                </div>
            </details>
        </header>

        <main class="page">
            @if (session('status')) <div class="flash flash-ok" role="status">{{ session('status') }}</div> @endif
            @if (session('error'))  <div class="flash flash-err" role="alert">{{ session('error') }}</div> @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    // Close the user menu when clicking elsewhere.
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details.usermenu[open]').forEach(function (d) {
            if (!d.contains(e.target)) d.removeAttribute('open');
        });
    });
</script>
</body>
</html>
