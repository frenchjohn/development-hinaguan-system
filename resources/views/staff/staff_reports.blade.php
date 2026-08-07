<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Reports — Hinaguan Nature Park</title>
    <script>
        // Prevent flash of wrong theme by setting theme immediately
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700|poppins:300,400,500,600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/staff_css/staff_reports.css',
        'resources/css/staff_css/staff_theme.css',
        'resources/css/chatbot.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_reports.js',
        'resources/js/staff_chatbot.js',
    ])
    <style>
        .dash-main::before {
            background-image: url('{{ asset('storage/design_images/background_image1.png') }}');
        }
    </style>
</head>
<body class="antialiased staff-portal">
    <div class="dash-layout">
        <x-staff_sidemenu active="reports" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">

            <main class="dash-content">
                <x-header
                    title="Staff Reports"
                    subtitle="Customer, reservation, and amenity insights"
                />

                <section class="reports-filters" id="reportsFilters">
                    <div class="reports-filter-group">
                        <label for="customerFilter">Customer</label>
                        <select id="customerFilter">
                            <option value="all">All customers</option>
                            @foreach($customerOptions as $customerOption)
                                <option value="{{ $customerOption }}">{{ $customerOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="reports-filter-group">
                        <label for="amenityFilter">Amenity</label>
                        <select id="amenityFilter">
                            <option value="all">All amenities</option>
                            @foreach($amenityOptions as $amenityOption)
                                <option value="{{ $amenityOption }}">{{ $amenityOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="reports-filter-group">
                        <label for="statusFilter">Reservation Status</label>
                        <select id="statusFilter">
                            <option value="all">All statuses</option>
                            @foreach($statusOptions as $statusOption)
                                <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="reports-filter-group">
                        <label for="dateFrom">Check-in from</label>
                        <input id="dateFrom" type="date" value="{{ $firstCheckInDate }}">
                    </div>
                    <div class="reports-filter-group">
                        <label for="dateTo">Check-in to</label>
                        <input id="dateTo" type="date" value="{{ $lastCheckInDate }}">
                    </div>
                </section>

                <section class="reports-print-summary" id="reportsPrintSummary" aria-hidden="true">
                    <div class="reports-print-summary__row">
                        <strong>Customer:</strong>
                        <span id="printCustomerText">All customers</span>
                    </div>
                    <div class="reports-print-summary__row">
                        <strong>Amenity:</strong>
                        <span id="printAmenityText">All amenities</span>
                    </div>
                    <div class="reports-print-summary__row">
                        <strong>Status:</strong>
                        <span id="printStatusText">All statuses</span>
                    </div>
                    <div class="reports-print-summary__row">
                        <strong>Check-in range:</strong>
                        <span id="printDateRangeText">{{ $firstCheckInDate }} - {{ $lastCheckInDate }}</span>
                    </div>
                </section>

                <div class="reports-metrics">
                    <article class="reports-metric-card">
                        <span class="reports-metric-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </span>
                        <div>
                            <p class="reports-metric-card__value" id="kpiReservations">{{ $totalReservations }}</p>
                            <p class="reports-metric-card__label">Total Reservations</p>
                        </div>
                    </article>
                    <article class="reports-metric-card">
                        <span class="reports-metric-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        <div>
                            <p class="reports-metric-card__value">{{ $totalGuests }}</p>
                            <p class="reports-metric-card__label">Total Guests</p>
                        </div>
                    </article>
                    <article class="reports-metric-card">
                        <span class="reports-metric-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <p class="reports-metric-card__value" id="kpiRevenue">₱{{ number_format($totalRevenue, 2) }}</p>
                            <p class="reports-metric-card__label">Total Revenue</p>
                        </div>
                    </article>
                    <article class="reports-metric-card">
                        <span class="reports-metric-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9 9 0 1020.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        </span>
                        <div>
                            <p class="reports-metric-card__value">₱{{ number_format($averageSpend, 2) }}</p>
                            <p class="reports-metric-card__label">Avg per Reservation</p>
                        </div>
                    </article>
                </div>

                <section class="reports-charts">
                    <section class="dash-panel dash-chart">
                        <div class="dash-panel__head">
                            <h3 class="dash-panel__title">Revenue — Last 6 Months</h3>
                            <span class="reports-panel__meta">Total amount per month</span>
                        </div>
                        @php
                            $monthlyMax = max(1, max($monthlyRevenue));
                        @endphp
                        <div class="dash-barchart dash-barchart--6">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="dash-barchart__col">
                                    <div class="dash-barchart__bars">
                                        <div class="dash-barchart__bar dash-barchart__bar--revenue"
                                             style="--val: {{ round($monthlyRevenue[$i] / $monthlyMax * 100) }}%"
                                             title="₱{{ number_format($monthlyRevenue[$i]) }}"></div>
                                    </div>
                                    <span class="dash-barchart__value">@if($monthlyRevenue[$i] >= 1000){{ number_format($monthlyRevenue[$i] / 1000, 1) }}k @else {{ number_format($monthlyRevenue[$i]) }} @endif</span>
                                    <span class="dash-barchart__label">{{ $monthlyLabels[$i] }}</span>
                                </div>
                            @endfor
                        </div>
                    </section>

                    <section class="dash-panel dash-chart">
                        <div class="dash-panel__head">
                            <h3 class="dash-panel__title">Reservation Status</h3>
                        </div>
                        @php
                            $donutColors = [
                                'Pending' => '#d3a94e',
                                'Confirmed' => '#2e9d68',
                                'Checked In' => '#178a52',
                                'Checked Out' => '#93a297',
                                'Cancelled' => '#cf4b47',
                            ];
                            $donutTotal = array_sum($reportStatusCounts->toArray());
                            $donutStart = 0;
                            $donutStops = [];
                            foreach ($reportStatusCounts as $status => $count) {
                                $pct = $donutTotal > 0 ? ($count / $donutTotal) * 360 : 0;
                                $color = $donutColors[$status] ?? '#93a297';
                                $donutStops[] = "{$color} {$donutStart}deg " . ($donutStart + $pct) . 'deg';
                                $donutStart += $pct;
                            }
                            $donutStyle = $donutTotal > 0 ? 'background: conic-gradient(' . implode(', ', $donutStops) . ');' : '';
                        @endphp
                        @if ($donutTotal > 0)
                            <div class="dash-donut-wrap">
                                <div class="dash-donut" style="{{ $donutStyle }}">
                                    <div class="dash-donut__hole">
                                        <span class="dash-donut__total">{{ $donutTotal }}</span>
                                        <small>total</small>
                                    </div>
                                </div>
                                <ul class="dash-donut__legend">
                                    @foreach ($reportStatusCounts as $status => $count)
                                        <li>
                                            <i style="background: {{ $donutColors[$status] ?? '#93a297' }}"></i>
                                            {{ $status }}
                                            <strong>{{ $count }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="dash-chart__empty">No reservation data yet.</p>
                        @endif
                    </section>
                </section>

                <section class="reports-panel reports-panel--wide">
                    <div class="reports-panel__head">
                        <div>
                            <h3 class="reports-panel__title">Reservation Report</h3>
                            <span class="reports-panel__meta">Showing all filtered reservations</span>
                        </div>
                        <button type="button" class="reports-print-btn" id="printReportsButton">Print / Save PDF</button>
                    </div>
                    <div class="reports-table-wrap">
                        <table class="reports-table" id="reservationReportTable">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Check-in</th>
                                    <th>Amenities</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reportRows as $row)
                                    <tr data-customer="{{ $row['customer_name'] }}" data-amenity="{{ $row['amenities'] }}" data-status="{{ $row['status'] }}" data-checkin="{{ $row['check_in'] }}" data-amount="{{ (float) $row['total_amount'] }}">
                                        <td>{{ $row['customer_name'] }}</td>
                                        <td>{{ $row['check_in'] ? \Illuminate\Support\Carbon::parse($row['check_in'])->format('M d, Y') : 'TBD' }}</td>
                                        <td>{{ $row['amenities'] }}</td>
                                        <td>
                                            <span class="status-pill status-pill--{{ strtolower(str_replace(' ', '-', $row['status'])) }}">{{ $row['status'] }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $paymentClass = ($row['payment_status'] ?? '') === 'Paid' ? 'status-pill--paid' : (($row['payment_status'] ?? '') === 'Unpaid' ? 'status-pill--unpaid' : 'status-pill--partially-paid');
                                            @endphp
                                            <span class="status-pill {{ $paymentClass }}">{{ $row['payment_status'] ?? 'N/A' }}</span>
                                        </td>
                                        <td>₱{{ number_format($row['total_amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="reports-table-empty">No reservation report data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>
