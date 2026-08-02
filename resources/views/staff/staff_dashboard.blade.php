<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Dashboard — Hinaguan Nature Park</title>
    <script>
        // Prevent flash of wrong theme by setting theme immediately
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/sidemenu.css',
        'resources/css/staff_css/staff_dashboard.css',
        'resources/css/chatbot.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_dashboard.js',
        'resources/js/staff_chatbot.js',
    ])
    <style>
        .dash-main::before {
            background-image: url('{{ asset('storage/design_images/background_image1.png') }}');
        }
    </style>
</head>
<body class="antialiased">
    <div class="dash-layout">
        <x-staff_sidemenu active="dashboard" />

        <div class="dash-main">
            <div class="page-transition-overlay" id="pageTransitionOverlay">
                <div class="page-transition-skeleton">
                    <div class="page-transition-skeleton__header skeleton"></div>
                    <div class="page-transition-skeleton__stats">
                        <div class="page-transition-skeleton__stat skeleton"></div>
                        <div class="page-transition-skeleton__stat skeleton"></div>
                        <div class="page-transition-skeleton__stat skeleton"></div>
                    </div>
                    <div class="page-transition-skeleton__grid">
                        <div class="page-transition-skeleton__panel skeleton"></div>
                        <div class="page-transition-skeleton__panel skeleton"></div>
                    </div>
                </div>
            </div>

            <x-header
                title="Staff Dashboard"
                subtitle="Daily tasks and guest activity at the park"
                userName="Staff User"
                userRole="Staff"
                :settingsUrl="route('staff.settings')"
            />

            <main class="dash-content">
                <section class="dash-welcome">
                    <span class="hp-section__label">Staff Portal</span>
                    <h2 class="dash-welcome__title">Welcome back, Staff!</h2>
                    <p class="dash-welcome__text">
                        Track today's check-ins, pending reservations, and on-site guests at Hinaguan Nature Park.
                    </p>
                </section>

                <div class="dash-stats">
                    <article class="dash-stat-card">
                        <div class="dash-stat-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="dash-stat-card__label">Today's Check-ins</p>
                        <p class="dash-stat-card__value">{{ $todayCheckIns }}</p>
                        <p class="dash-stat-card__hint">Reservations marked as checked in today</p>
                    </article>
                    <article class="dash-stat-card">
                        <div class="dash-stat-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="dash-stat-card__label">Pending Reservations</p>
                        <p class="dash-stat-card__value">{{ $pendingReservationsCount }}</p>
                        <p class="dash-stat-card__hint">Online reservations awaiting action</p>
                    </article>
                    <article class="dash-stat-card">
                        <div class="dash-stat-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <p class="dash-stat-card__label">Guests On-site</p>
                        <p class="dash-stat-card__value">{{ $guestsOnSiteCount }}</p>
                        <p class="dash-stat-card__hint">Guests still active and not checked out</p>
                    </article>
                </div>

                <div class="dash-grid-2">
                    <section class="dash-panel">
                        <div class="dash-panel__head">
                            <h3 class="dash-panel__title">Today's Tasks</h3>
                        </div>
                        <ul class="dash-task-list">
                            @foreach ([
                                ['title' => 'Prepare Cottage A for 2:00 PM check-in', 'meta' => 'Guest: Maria Santos'],
                                ['title' => 'Verify picnic area reservation list', 'meta' => 'Before noon'],
                                ['title' => 'Restock welcome desk supplies', 'meta' => 'Front office'],
                                ['title' => 'Assist camping ground orientation', 'meta' => '3:30 PM group'],
                            ] as $task)
                                <li class="dash-task">
                                    <input type="checkbox" class="dash-task__check" aria-label="Mark task complete">
                                    <div class="dash-task__body">
                                        <p class="dash-task__title">{{ $task['title'] }}</p>
                                        <p class="dash-task__meta">{{ $task['meta'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    <section class="dash-panel">
                        <div class="dash-panel__head">
                            <h3 class="dash-panel__title">Recent Activity</h3>
                        </div>
                        <ul class="dash-activity-list">
                            @forelse ($activityItems as $activity)
                                <li class="dash-activity">
                                    <span class="dash-activity__dot" aria-hidden="true"></span>
                                    <div>
                                        <p class="dash-activity__text">{{ $activity['text'] }}</p>
                                        <p class="dash-activity__time">{{ $activity['time'] }}</p>
                                    </div>
                                </li>
                            @empty
                                <li class="dash-activity">
                                    <span class="dash-activity__dot" aria-hidden="true"></span>
                                    <div>
                                        <p class="dash-activity__text">No recent reservation activity yet.</p>
                                        <p class="dash-activity__time">Check back soon</p>
                                    </div>
                                </li>
                            @endforelse
                        </ul>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>
