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
    <link rel="stylesheet" href="{{ asset('css/extra.css') }}">
</head>
<body>
<main class="auth">
    <section class="auth-brand">
        @hasSection('brand')
            @yield('brand')
        @else
            <h1>Sign up to check current events happening around campus.</h1>

        @endif
    </section>

    <section class="auth-panel">
        <div class="auth-card">
            @yield('content')
        </div>
    </section>
</main>
</body>
</html>
