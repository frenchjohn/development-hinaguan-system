<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $status === 'success' ? 'Payment Successful' : 'Payment Status' }} — Hinaguan Nature Park</title>
    <style>
        :root {
            --green-dark: #001a11;
            --green: #0d2c1d;
            --gold: #c8a45d;
            --gold-light: #d4b06a;
            --gold-dark: #a8843f;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background:
                radial-gradient(120% 90% at 20% 0%, rgba(26, 61, 42, 0.6) 0%, rgba(11, 36, 24, 0) 55%),
                linear-gradient(180deg, #0b2418 0%, #001a11 100%);
            color: #fff;
            padding: 1.5rem;
        }
        .card {
            width: min(100%, 26rem);
            background: rgba(11, 36, 24, 0.96);
            border: 1px solid rgba(200, 164, 93, 0.35);
            border-radius: 1.25rem;
            padding: 2.25rem 1.75rem;
            text-align: center;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.45);
            animation: pop .3s ease;
        }
        @keyframes pop { from { opacity: 0; transform: translateY(14px) scale(.98); } to { opacity: 1; transform: none; } }
        .icon {
            width: 4.5rem; height: 4.5rem;
            border-radius: 50%;
            display: grid; place-items: center;
            margin: 0 auto 1.25rem;
            font-size: 2.1rem;
        }
        .icon--ok { background: linear-gradient(135deg, #22c55e, #15803d); box-shadow: 0 8px 26px rgba(34, 197, 94, .35); }
        .icon--wait { background: linear-gradient(135deg, var(--gold-light), var(--gold-dark)); color: var(--green-dark); box-shadow: 0 8px 26px rgba(200, 164, 93, .35); }
        .icon--err { background: linear-gradient(135deg, #f87171, #b91c1c); box-shadow: 0 8px 26px rgba(248, 113, 113, .3); }
        h1 { font-size: 1.4rem; margin-bottom: .6rem; }
        p { color: rgba(255, 255, 255, .78); font-size: .95rem; line-height: 1.65; }
        .meta { margin-top: 1.25rem; padding: .9rem; border-radius: .7rem; background: rgba(0, 0, 0, .3); border: 1px solid rgba(200, 164, 93, .25); font-size: .85rem; color: rgba(255, 255, 255, .7); }
        .btn {
            display: inline-block; margin-top: 1.5rem; padding: .8rem 1.75rem;
            border-radius: 999px; border: none; cursor: pointer;
            background: linear-gradient(135deg, var(--gold-light), var(--gold-dark));
            color: var(--green-dark); font-weight: 700; font-size: .875rem;
            text-decoration: none; letter-spacing: .03em;
        }
        .btn--ghost { background: rgba(255, 255, 255, .1); color: #fff; border: 1px solid rgba(255, 255, 255, .2); }
        .spinner {
            width: 1.4rem; height: 1.4rem; margin: 0 auto 1.25rem;
            border: 3px solid rgba(200, 164, 93, .2); border-top-color: var(--gold);
            border-radius: 50%; animation: spin .9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    @php
        $isSuccess = $status === 'success';
        $title = $isSuccess ? 'Payment Successful' : match ($status) {
            'processing' => 'Payment Processing…',
            'cancelled' => 'Payment Cancelled',
            'error' => 'Something Went Wrong',
            default => 'Payment Not Found',
        };
    @endphp

    <div class="card">
        @if ($isSuccess)
            <div class="icon icon--ok">&#10003;</div>
            <h1>Thank you! Your booking is confirmed.</h1>
            <p>Your deposit was received and a QR code is on its way to your email. Present it at the park entrance on your reservation day.</p>
            <div class="meta">
                Reservation #{{ $reservation->id }} &middot; {{ $reservation->booker_name }}<br>
                <strong>₱{{ number_format((float) $reservation->amount_paid, 2) }}</strong> paid &middot;
                Balance ₱{{ number_format((float) $reservation->remaining_balance, 2) }}
            </div>
            <a class="btn" href="{{ route('reservation') }}">Done</a>
        @elseif ($status === 'processing')
            <div class="spinner"></div>
            <h1>{{ $title }}</h1>
            <p>Your payment is still being confirmed. This window can be closed — your booking status will update automatically.</p>
            <button class="btn" id="retryBtn">Check status again</button>
        @elseif ($status === 'cancelled')
            <div class="icon icon--wait">&#10007;</div>
            <h1>{{ $title }}</h1>
            <p>You closed the payment before completing it. Your reservation is being held, but it is <strong>not confirmed</strong> until the deposit is paid.</p>
            <a class="btn" href="{{ route('reservation') }}">Back to reservation page</a>
        @else
            <div class="icon icon--err">&#33;</div>
            <h1>{{ $title }}</h1>
            <p>@if ($status === 'error') We could not verify your payment just now. Please contact us if you were charged.
                @else No payment was found for this link. It may have expired.
                @endif</p>
            <a class="btn btn--ghost" href="{{ route('reservation') }}">Back to reservation page</a>
        @endif
    </div>

    <script>
        // If this page lives in a popup opened by the reservation page, tell the
        // opener the outcome and close ourselves so the guest lands back on the
        // booking page seamlessly.
        (function () {
            var outcome = @json($status);

            if (window.opener && !window.opener.closed) {
                try {
                    window.opener.postMessage({ source: 'hinaguan-paymongo', status: outcome }, window.location.origin);
                } catch (e) { /* cross-origin opener — ignore */ }
                setTimeout(function () { window.close(); }, 900);
            } else if (outcome === 'success') {
                var btn = document.querySelector('a.btn');
                if (btn) btn.textContent = 'Book another visit';
            }

            var retryBtn = document.getElementById('retryBtn');
            if (retryBtn) {
                retryBtn.addEventListener('click', function () {
                    window.location.reload();
                });
            }
        })();
    </script>
</body>
</html>
