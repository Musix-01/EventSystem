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
</head>
<body>
@php($user = auth()->user())
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="{{ route('dashboard') }}">Campus Events</a>

        <nav class="nav" aria-label="Main">
            <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>Dashboard</a>
            <a href="{{ route('events.index') }}" @if(request()->routeIs('events.index', 'events.show')) aria-current="page" @endif>Events</a>

            @unless ($user->isStaff())
                <a href="{{ route('registrations.index') }}" @if(request()->routeIs('registrations.*')) aria-current="page" @endif>My events</a>
            @endunless

            @if ($user->isOfficer())
                <a href="{{ route('officer.events.index') }}" @if(request()->routeIs('officer.events.*', 'attendance.*', 'letters.*')) aria-current="page" @endif>My org's events</a>
            @endif

            @if ($user->isAdmin())
                @php($pendingCount = \App\Models\Event::where('status', 'pending')->count())
                @php($unreadLetters = \App\Models\Letter::whereNull('read_at')->count())
                <a href="{{ route('admin.events.index') }}" @if(request()->routeIs('admin.events.*')) aria-current="page" @endif>
                    Event requests
                    @if ($pendingCount > 0)<span class="badge">{{ $pendingCount }}</span>@endif
                </a>
                <a href="{{ route('admin.letters.index') }}" @if(request()->routeIs('admin.letters.*', 'letters.*')) aria-current="page" @endif>
                    Letters
                    @if ($unreadLetters > 0)<span class="badge">{{ $unreadLetters }}</span>@endif
                </a>
                <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>Users</a>
            @endif
        </nav>

        <div class="who">
            <span>{{ $user->name }} ({{ $user->affiliation() }})</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-quiet btn-sm">Log out</button>
            </form>
        </div>
    </div>
</header>

<main class="page">
    @if (session('status')) <div class="flash flash-ok" role="status">{{ session('status') }}</div> @endif
    @if (session('error'))  <div class="flash flash-err" role="alert">{{ session('error') }}</div> @endif

    @yield('content')
</main>
</body>
</html>
