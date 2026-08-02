<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="Hinaguan Nature Park staff and admin login page.">

    <title>Login — Hinaguan Nature Park</title>

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/loginpage.css', 'resources/js/loginpage.js'])

</head>

<body class="login-page" style="--lp-page-bg: url('{{ asset('images/background.jpeg') }}')">

    <main class="login-page__wrapper">

        <section class="login-page__split">

            <aside class="login-panel login-panel--message">

                <div class="login-panel__content">

                    <div class="login-panel__logo">

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">

                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 2.5-4 5-4 8a4 4 0 108 0c0-3-2.5-5.5-4-8z"/>

                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M10 18h4"/>

                        </svg>

                        <span>Hinaguan Nature Park</span>

                    </div>

                    <span class="login-panel__badge">Private Access</span>

                    <h1 class="login-panel__title">Staff & Admin Portal</h1>

                    <p class="login-panel__text">

                        Secure access for authorized personnel only. This area is restricted to park staff and administrators for managing reservations and operations.

                    </p>

                </div>

            </aside>



            <aside class="login-panel login-panel--form">

                <section class="login-card">

                    <div class="login-card__intro">

                        <span class="login-card__label">Sign In</span>

                        <h2>Welcome Back</h2>

                        <p>Only staff and admin can access this area.</p>

                    </div>



                    @if(session('error'))

                        <div class="login-card__alert">{{ session('error') }}</div>

                    @endif



                    <form method="POST" action="{{ route('login.submit') }}" class="login-form">

                        @csrf



                        <div class="login-form__group">

                            <label for="email">Email</label>

                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="Enter your email" />

                            @error('email')

                                <p class="login-form__error">{{ $message }}</p>

                            @enderror

                        </div>



                        <div class="login-form__group">

                            <label for="password">Password</label>

                            <div class="login-form__password">

                                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password" />

                                <button type="button" class="login-form__toggle" data-password-toggle>Show</button>

                            </div>

                            @error('password')

                                <p class="login-form__error">{{ $message }}</p>

                            @enderror

                        </div>



                        <button type="submit" class="login-form__submit">Log in</button>

                    </form>

                </section>

            </aside>

        </section>

    </main>

</body>

</html>

