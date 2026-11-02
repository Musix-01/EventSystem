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
</head>
<body>
<main class="auth">
    <section class="auth-brand">
        @hasSection('brand')
            @yield('brand')
        @else
            <h1>Join the events your org is hosting.</h1>
            <p>Sign up with your student number, pick your organization, and get a check-in code for every event you join.</p>

            <div class="ticket" aria-hidden="true">
                <div class="ticket-main">
                    <small>Your check-in code</small>
                    <strong>ELITE General Assembly</strong>
                </div>
                <div class="ticket-code">482 193</div>
            </div>
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
