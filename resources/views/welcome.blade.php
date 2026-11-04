<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> Campus Events </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/campus.css') }}">
    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
</head>
<body class="welcome-page">
<main class="welcome">
    <h1>Hello! Who is logging in?</h1>
    <p class="lead">Please click or tap your destination.</p>

    <nav class="choices" aria-label="Choose your account type">
        @foreach (collect(\App\Http\Controllers\Auth\LoginController::portals())->only(['student', 'officer', 'faculty']) as $key => $portal)
            <a class="choice" href="{{ route('login', ['as' => $key]) }}">
                <strong>{{ $portal['title'] }}</strong>
            </a>
        @endforeach
    </nav>
</main>
</body>
</html>
