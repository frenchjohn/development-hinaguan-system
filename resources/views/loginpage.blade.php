<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Hinaguan Nature Park staff and admin login page.">
    <title>Login — Hinaguan Nature Park</title>

    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    @vite(['resources/css/app.css', 'resources/css/loginpage.css', 'resources/js/loginpage.js'])
</head>
<body class="login-page" style="--lp-page-bg: url('{{ asset('images/background.jpeg') }}')">

    <main class="login-page__wrapper">
        <section class="login-card">
            <div class="login-card__intro">
                <div class="login-card__logo-wrap">
                    <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Nature Park" class="login-card__logo" onerror="this.style.display='none'">
                </div>
                <h2>Sign In</h2>
                <p>Authorized user only</p>
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

                {{-- Cloudflare Turnstile "I am not a robot" CAPTCHA --}}
                @php
                    $isLocalhost = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1']);
                    $turnstileSiteKey = ($isLocalhost && env('CLOUDFLARE_TURNSTILE_USE_TEST_ON_LOCAL', true))
                        ? '1x00000000000000000000AA'
                        : (config('services.cloudflare.turnstile_site_key') ?? '0x4AAAAAAEfCC0KJOL1zsgrX');
                @endphp
                <div class="login-form__group" style="display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0.5rem 0;">
                    <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-theme="dark"></div>
                    @error('cf-turnstile-response')
                        <p class="login-form__error" style="text-align: center; margin-top: 0.5rem;">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="login-form__submit" id="loginSubmitBtn">
                    <span class="login-form__submit-spinner" aria-hidden="true">
                        <svg class="login-spinner-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle class="login-spinner-track" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3.5"></circle>
                            <path class="login-spinner-head" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    <span class="login-form__submit-text">Log in</span>
                </button>
            </form>
        </section>
    </main>

</body>
</html>
