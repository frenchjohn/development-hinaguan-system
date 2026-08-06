<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\StaffChatbotController;
use App\Http\Controllers\GuestChatbotController;
use App\Http\Controllers\HomeController;
use App\Mail\ReservationQrMail;
use App\Models\Amenity;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
use App\Services\WeatherService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('/chatbot', [StaffChatbotController::class, 'chat'])->name('chatbot.chat');

Route::post('/guest-chatbot', [GuestChatbotController::class, 'chat'])->name('chatbot.guest')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/api/active-guests-count', function () {
    $count = ReservationGuest::query()
        ->whereNull('checked_out_at')
        ->whereHas('reservation', function ($query) {
            $query->where('status', 'Checked In');
        })
        ->count();

    return response()->json([
        'count' => $count,
    ]);
})->name('api.active-guests-count');

Route::get('/api/park-settings', function () {
    $settings = \App\Models\ParkSetting::first();
    
    return response()->json([
        'daytime_adult_entrance_fee' => $settings->daytime_adult_entrance_fee ?? 0,
        'daytime_child_entrance_fee' => $settings->daytime_child_entrance_fee ?? 0,
        'nighttime_adult_entrance_fee' => $settings->nighttime_adult_entrance_fee ?? 0,
        'nighttime_child_entrance_fee' => $settings->nighttime_child_entrance_fee ?? 0,
        'day_pool_fee' => $settings->day_pool_fee ?? 0,
        'night_pool_fee' => $settings->night_pool_fee ?? 0,
        'daytime_start' => $settings->daytime_start ?? '06:00',
        'daytime_end' => $settings->daytime_end ?? '18:00',
        'nighttime_start' => $settings->nighttime_start ?? '18:00',
        'nighttime_end' => $settings->nighttime_end ?? '06:00',
    ]);
})->name('api.park-settings');

Route::get('/amenities', function () {
    $amenities = Amenity::where('status', true)
        ->orderBy('amenities_name')
        ->get();

    $availability = [];
    $today = now()->startOfDay();

    foreach ($amenities as $amenity) {
        $slots = [];
        $dateCursor = $today->copy();

        for ($i = 0; $i < 30; $i++) {
            $date = $dateCursor->toDateString();
            $hasDaytime = ! Reservation::query()
                ->whereDate('reservation_date', $date)
                ->whereNotIn('status', ['Cancelled', 'Checked Out'])
                ->whereHas('reservationAmenities', function ($query) use ($amenity): void {
                    $query->where('amenity_id', $amenity->id)
                        ->whereIn('pricing_type', ['Daytime', 'Daytime Aircon', 'DayNight Time', 'DayNight Time Aircon']);
                })
                ->exists();
            $hasNighttime = ! Reservation::query()
                ->whereDate('reservation_date', $date)
                ->whereNotIn('status', ['Cancelled', 'Checked Out'])
                ->whereHas('reservationAmenities', function ($query) use ($amenity): void {
                    $query->where('amenity_id', $amenity->id)
                        ->whereIn('pricing_type', ['Nighttime', 'Nighttime Aircon', 'DayNight Time', 'DayNight Time Aircon']);
                })
                ->exists();

            $slots[] = [
                'date' => $date,
                'daytime' => $hasDaytime,
                'nighttime' => $hasNighttime,
            ];

            $dateCursor->addDay();
        }

        $availability[$amenity->id] = $slots;
    }

    return view('amenities', [
        'amenities' => $amenities,
        'availability' => $availability,
    ]);
})->name('amenities');

Route::get('/reservation/weather-preview', function (Request $request, WeatherService $weather) {
    $date = $request->query('date');
    $forecast = $date ? $weather->getForecastForDate($date) : null;

    if (! $forecast) {
        return response()->json([
            'available' => false,
            'message' => 'Weather forecast is available for up to 3 days ahead.',
        ]);
    }

    return response()->json([
        'available' => true,
        'date' => $forecast['date'],
        'condition' => $forecast['condition'],
        'icon' => $forecast['icon'],
        'max_temp_c' => $forecast['max_temp_c'],
        'min_temp_c' => $forecast['min_temp_c'],
        'chance_of_rain' => $forecast['chance_of_rain'],
    ]);
})->name('reservation.weather-preview');

Route::get('/reservation/availability', function (Request $request) {
    $date = $request->query('date');
    $slot = $request->query('slot');

    if (! $date || ! $slot) {
        return response()->json([
            'date' => $date,
            'slot' => $slot,
            'occupied_amenity_ids' => [],
        ]);
    }

    // Determine pricing types based on slot
    if ($slot === 'Nighttime') {
        $pricingTypes = ['Nighttime', 'Nighttime Aircon'];
        // Also exclude DayNight Time bookings since they cover nighttime
        $excludedTypes = ['Nighttime', 'Nighttime Aircon', 'DayNight Time', 'DayNight Time Aircon'];
    } elseif ($slot === 'DayNight Time') {
        // DayNight Time conflicts with ALL booking types since it covers the entire day
        $excludedTypes = ['Daytime', 'Daytime Aircon', 'Nighttime', 'Nighttime Aircon', 'DayNight Time', 'DayNight Time Aircon'];
    } else {
        // Daytime (default)
        $pricingTypes = ['Daytime', 'Daytime Aircon'];
        // Also exclude DayNight Time bookings since they cover daytime
        $excludedTypes = ['Daytime', 'Daytime Aircon', 'DayNight Time', 'DayNight Time Aircon'];
    }

    $occupiedAmenityIds = Reservation::query()
        ->whereDate('reservation_date', $date)
        ->whereNotIn('status', ['Cancelled', 'Checked Out'])
        ->whereHas('reservationAmenities', function ($query) use ($excludedTypes): void {
            $query->whereIn('pricing_type', $excludedTypes);
        })
        ->with('reservationAmenities')
        ->get()
        ->flatMap(fn (Reservation $reservation) => $reservation->reservationAmenities
            ->pluck('amenity_id'))
        ->unique()
        ->values()
        ->all();

    return response()->json([
        'date' => $date,
        'slot' => $slot,
        'occupied_amenity_ids' => $occupiedAmenityIds,
    ]);
})->name('reservation.availability');

Route::get('/reservation/availability/calendar', function (Request $request) {
    $amenityId = $request->query('amenity_id');
    $slot = $request->query('slot', 'Daytime');
    $month = $request->query('month');
    $year = $request->query('year');

    if (! $amenityId) {
        return response()->json([
            'amenity_id' => $amenityId,
            'slot' => $slot,
            'availability' => [],
        ]);
    }

    // Use today's date in the application's timezone to avoid offset issues
    if ($month !== null && $year !== null) {
        // If month and year are provided, start from the first day of that month
        $startDate = \Carbon\Carbon::createFromDate($year, $month + 1, 1)->startOfDay();
    } else {
        // Default to today and show 30 days
        $startDate = \Carbon\Carbon::today()->startOfDay();
    }
    
    $availability = [];
    
    // Determine the number of days to show
    if ($month !== null && $year !== null) {
        // Show entire month
        $daysInMonth = $startDate->daysInMonth;
        $numDays = $daysInMonth;
    } else {
        // Show 30 days
        $numDays = 30;
    }

    for ($i = 0; $i < $numDays; $i++) {
        $date = $startDate->copy()->addDays($i)->toDateString();
        
        // Check daytime availability separately
        // For Daytime, check Daytime and DayNight Time bookings
        $daytimePricingTypes = ['Daytime', 'Daytime Aircon', 'DayNight Time', 'DayNight Time Aircon'];
        $isDaytimeBooked = Reservation::query()
            ->whereDate('reservation_date', $date)
            ->whereNotIn('status', ['Cancelled', 'Checked Out'])
            ->whereHas('reservationAmenities', function ($query) use ($amenityId, $daytimePricingTypes): void {
                $query->where('amenity_id', $amenityId)
                    ->whereIn('pricing_type', $daytimePricingTypes);
            })
            ->exists();

        // Check nighttime availability separately
        // For Nighttime, check Nighttime and DayNight Time bookings
        $nighttimePricingTypes = ['Nighttime', 'Nighttime Aircon', 'DayNight Time', 'DayNight Time Aircon'];
        $isNighttimeBooked = Reservation::query()
            ->whereDate('reservation_date', $date)
            ->whereNotIn('status', ['Cancelled', 'Checked Out'])
            ->whereHas('reservationAmenities', function ($query) use ($amenityId, $nighttimePricingTypes): void {
                $query->where('amenity_id', $amenityId)
                    ->whereIn('pricing_type', $nighttimePricingTypes);
            })
            ->exists();

        // For DayNight Time, check if both daytime and nighttime are available
        $isDayNightAvailable = ! $isDaytimeBooked && ! $isNighttimeBooked;
        
        $availability[] = [
            'date' => $date,
            'daytime' => ! $isDaytimeBooked,
            'nighttime' => ! $isNighttimeBooked,
            'daynight' => $isDayNightAvailable,
        ];
    }

    return response()->json([
        'amenity_id' => $amenityId,
        'slot' => $slot,
        'availability' => $availability,
    ]);
})->name('reservation.availability.calendar');

Route::get('/reservation', function (WeatherService $weather) {
    $amenities = Amenity::where('status', true)
        ->orderBy('amenities_name')
        ->get();

    if ($amenities->isEmpty()) {
        $sampleAmenities = [
            ['id' => 'amenity-1', 'amenities_name' => 'Cottage A', 'daytime_price' => 500, 'nighttime_price' => 700, 'daytime_aircon_price' => 800, 'nighttime_aircon_price' => 900, 'additional_per_head' => 100, 'minimum_capacity' => 10, 'maximum_capacity' => 20, 'description' => 'Cozy cottage with garden view.', 'image' => null, 'status' => true],
            ['id' => 'amenity-2', 'amenities_name' => 'Cottage B', 'daytime_price' => 550, 'nighttime_price' => 750, 'daytime_aircon_price' => 850, 'nighttime_aircon_price' => 950, 'additional_per_head' => 100, 'minimum_capacity' => 12, 'maximum_capacity' => 22, 'description' => 'Spacious cottage for family gatherings.', 'image' => null, 'status' => true],
            ['id' => 'amenity-3', 'amenities_name' => 'Picnic Area', 'daytime_price' => 300, 'nighttime_price' => 450, 'daytime_aircon_price' => null, 'nighttime_aircon_price' => null, 'additional_per_head' => 50, 'minimum_capacity' => 8, 'maximum_capacity' => 15, 'description' => 'Open picnic ground near the river.', 'image' => null, 'status' => true],
            ['id' => 'amenity-4', 'amenities_name' => 'Camping Ground', 'daytime_price' => 350, 'nighttime_price' => 500, 'daytime_aircon_price' => null, 'nighttime_aircon_price' => null, 'additional_per_head' => 75, 'minimum_capacity' => 6, 'maximum_capacity' => 20, 'description' => 'Camping spot with a scenic view.', 'image' => null, 'status' => true],
            ['id' => 'amenity-5', 'amenities_name' => 'Function Hall', 'daytime_price' => 1200, 'nighttime_price' => 1600, 'daytime_aircon_price' => 1500, 'nighttime_aircon_price' => 1900, 'additional_per_head' => 120, 'minimum_capacity' => 20, 'maximum_capacity' => 50, 'description' => 'Indoor hall for events and gatherings.', 'image' => null, 'status' => true],
            ['id' => 'amenity-6', 'amenities_name' => 'Viewing Deck', 'daytime_price' => 400, 'nighttime_price' => 600, 'daytime_aircon_price' => null, 'nighttime_aircon_price' => null, 'additional_per_head' => 50, 'minimum_capacity' => 5, 'maximum_capacity' => 12, 'description' => 'A scenic viewing deck for small groups.', 'image' => null, 'status' => true],
        ];

        foreach ($sampleAmenities as $sampleAmenity) {
            Amenity::firstOrCreate(['id' => $sampleAmenity['id']], $sampleAmenity);
        }

        $amenities = Amenity::where('status', true)
            ->orderBy('amenities_name')
            ->get();
    }

    $selectedDate = now()->toDateString();
    $maxReservationDate = now()->addDays(3)->toDateString();
    $weatherPreview = $weather->getForecastForDate($selectedDate);

    return view('reservationpage', [
        'amenities' => $amenities,
        'weatherPreview' => $weatherPreview,
        'maxReservationDate' => $maxReservationDate,
    ]);
})->name('reservation');

Route::post('/reservation/prototype', function (Request $request) {
    $data = $request->validate([
        'booker_name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'number_of_guests' => ['required', 'integer', 'min:1'],
        'check_in' => ['nullable', 'date'],
        'reservation_date' => ['nullable', 'date'],
        'slot' => ['nullable', 'string'],
        'amenities' => ['nullable', 'array'],
        'amenities.*.amenity_id' => ['required_with:amenities', 'string'],
        'amenities.*.pricing_type' => ['required_with:amenities', 'string'],
        'amenities.*.price_at_booking' => ['required_with:amenities', 'numeric'],
        'amenity_id' => ['nullable', 'string'],
        'pricing_type' => ['nullable', 'string'],
        'price_at_booking' => ['nullable', 'numeric'],
    ]);

    $amenities = is_array($data['amenities'] ?? null) && count($data['amenities']) > 0
        ? $data['amenities']
        : [[
            'amenity_id' => $data['amenity_id'] ?? null,
            'pricing_type' => $data['pricing_type'] ?? $data['slot'] ?? 'Daytime',
            'price_at_booking' => $data['price_at_booking'] ?? 0,
        ]];

    $amenities = array_values(array_filter($amenities, fn ($amenity) => ! empty($amenity['amenity_id'])));

    if ($amenities === []) {
        return response()->json([
            'success' => false,
            'message' => 'At least one amenity is required.',
        ], 422);
    }

    $reservationDate = $data['reservation_date'] ?? $data['check_in'] ?? null;

    // Block Daytime and DayNight Time bookings for today's date
    $today = now()->toDateString();
    foreach ($amenities as $amenity) {
        $pricingType = $amenity['pricing_type'];

        // Check if reservation date is today and pricing type is Daytime or DayNight Time
        if ($reservationDate === $today) {
            if ($pricingType === 'Daytime' || $pricingType === 'Daytime Aircon' ||
                $pricingType === 'DayNight Time' || $pricingType === 'DayNight Time Aircon') {
                return response()->json([
                    'success' => false,
                    'message' => 'Daytime and DayNight Time bookings are not allowed for today. Please choose Nighttime or select a different date.',
                ], 409);
            }
        }
    }

    // Check for duplicate reservations (same date, amenity_id, and pricing_type)
    foreach ($amenities as $amenity) {
        $pricingType = $amenity['pricing_type'];
        $amenityId = $amenity['amenity_id'];

        // For DayNight Time, check if either Daytime or Nighttime is already booked
        if ($pricingType === 'DayNight Time' || $pricingType === 'DayNight Time Aircon') {
            $conflictExists = Reservation::query()
                ->whereDate('reservation_date', $reservationDate)
                ->whereNotIn('status', ['Cancelled', 'Checked Out'])
                ->whereHas('reservationAmenities', function ($query) use ($amenityId): void {
                    $query->where('amenity_id', $amenityId)
                        ->whereIn('pricing_type', ['Daytime', 'Daytime Aircon', 'Nighttime', 'Nighttime Aircon', 'DayNight Time', 'DayNight Time Aircon']);
                })
                ->exists();

            if ($conflictExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This amenity is already booked for the selected time slot. Please choose a different time or amenity.',
                ], 409);
            }
        } else {
            // For Daytime or Nighttime, check for exact match or DayNight Time conflict
            $exists = Reservation::query()
                ->whereDate('reservation_date', $reservationDate)
                ->whereNotIn('status', ['Cancelled', 'Checked Out'])
                ->whereHas('reservationAmenities', function ($query) use ($amenityId, $pricingType): void {
                    $query->where('amenity_id', $amenityId);

                    // If booking Daytime, check for Daytime or DayNight Time conflicts
                    if ($pricingType === 'Daytime' || $pricingType === 'Daytime Aircon') {
                        $query->whereIn('pricing_type', ['Daytime', 'Daytime Aircon', 'DayNight Time', 'DayNight Time Aircon']);
                    }
                    // If booking Nighttime, check for Nighttime or DayNight Time conflicts
                    elseif ($pricingType === 'Nighttime' || $pricingType === 'Nighttime Aircon') {
                        $query->whereIn('pricing_type', ['Nighttime', 'Nighttime Aircon', 'DayNight Time', 'DayNight Time Aircon']);
                    }
                    // Otherwise, check for exact match
                    else {
                        $query->where('pricing_type', $pricingType);
                    }
                })
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This amenity is already booked for the selected time slot. Please choose a different time or amenity.',
                ], 409);
            }
        }
    }

    // Calculate total from all amenities
    $totalAmount = array_sum(array_column($amenities, 'price_at_booking'));

    $reservation = Reservation::create([
        'booker_name' => $data['booker_name'],
        'phone' => $data['phone'],
        'email' => $data['email'],
        'reservation_date' => $reservationDate ? now()->parse($reservationDate)->toDateTimeString() : null,
        'check_in' => null,
        'number_of_guests' => $data['number_of_guests'],
        'status' => 'Pending',
        'total_amount' => $totalAmount,
        'amount_paid' => $totalAmount * 0.5,
        'remaining_balance' => $totalAmount * 0.5,
        'payment_status' => 'Partially Paid',
    ]);

    // Don't create customer yet - only create when staff checks in and fills the check-in modal

    // Create a ReservationAmenity record for each amenity
    foreach ($amenities as $amenity) {
        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity['amenity_id'],
            'pricing_type' => $amenity['pricing_type'],
            'price_at_booking' => $amenity['price_at_booking'],
            'quantity' => 1,
            'remarks' => 'Prototype reservation from reservation page. Slot: ' . ($data['slot'] ?? 'Daytime'),
        ]);
    }

    try {
        Mail::to($data['email'])->send(new ReservationQrMail($reservation));
    } catch (\Throwable $exception) {
        report($exception);
    }

    return response()->json([
        'success' => true,
        'reservation_id' => $reservation->id,
        'message' => 'Prototype reservation recorded and marked partially paid.',
    ]);
})->name('reservation.prototype')->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/reservation/check-in/{reservation}', function (Request $request, Reservation $reservation) {
    // Harden: only authenticated staff/admin may check a reservation in.
    $user = $request->session()->get('auth_user');
    if (! $user || ! in_array($user['role'], ['staff', 'admin'], true)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    if ($reservation->status === 'Checked In') {
        return response()->json([
            'success' => true,
            'reservation_id' => $reservation->id,
            'message' => 'Reservation is already checked in.',
        ]);
    }

    $reservation->update([
        'status' => 'Checked In',
        'check_in' => now()->toDateTimeString(),
    ]);

    return response()->json([
        'success' => true,
        'reservation_id' => $reservation->id,
        'message' => 'Reservation checked in successfully.',
    ]);
})->name('reservation.check-in');

Route::get('/park-portal', [LoginController::class, 'show'])->name('login');
Route::post('/park-portal', [LoginController::class, 'authenticate'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        // Optimize queries by only fetching needed data
        $totalReservations = Reservation::count();
        $totalGuests = Reservation::sum('number_of_guests');
        $todayVisitors = Reservation::whereDate('check_in', now()->toDateString())->sum('number_of_guests');
        $currentMonthRevenue = Reservation::whereMonth('check_in', now()->month)
            ->whereYear('check_in', now()->year)
            ->sum('amount_paid');
        $pendingReservations = Reservation::where('status', 'Pending')->count();
        $cancelledReservations = Reservation::where('status', 'Cancelled')->count();
        $checkedInGuests = ReservationGuest::query()
            ->whereNull('checked_out_at')
            ->whereHas('reservation', function ($query) {
                $query->where('status', 'Checked In');
            })
            ->count();

        // Get recent reservations with eager loading for display only
        $recentReservations = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer'])
            ->orderByDesc('created_at')
            ->take(4)
            ->get();

        // Calculate unique customer count from recent reservations only (for performance)
        $uniqueCustomerCount = $recentReservations
            ->flatMap(function ($reservation) {
                $guestNames = $reservation->reservationGuests
                    ->map(fn ($guest) => trim(($guest->customer?->first_name ?? '') . ' ' . ($guest->customer?->last_name ?? '')))
                    ->filter();

                return $guestNames->push($reservation->booker_name)->filter();
            })
            ->unique()
            ->filter()
            ->count();

        // Calculate top amenity from recent reservations only (for performance)
        $topAmenity = $recentReservations
            ->flatMap(fn ($reservation) => $reservation->reservationAmenities)
            ->groupBy(fn ($item) => $item->amenity?->amenities_name ?? 'Unknown')
            ->map(fn ($items) => [
                'name' => $items->first()->amenity?->amenities_name ?? 'Unknown',
                'count' => $items->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->first();

        return view('admin.admin_dashboard', [
            'totalReservations' => $totalReservations,
            'totalGuests' => $totalGuests,
            'todayVisitors' => $todayVisitors,
            'currentMonthRevenue' => $currentMonthRevenue,
            'pendingReservations' => $pendingReservations,
            'cancelledReservations' => $cancelledReservations,
            'checkedInGuests' => $checkedInGuests,
            'uniqueCustomerCount' => $uniqueCustomerCount,
            'topAmenity' => $topAmenity,
            'recentReservations' => $recentReservations,
        ]);
    })->name('dashboard');

    Route::get('/amenities', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        return view('admin.admin_amenitiesmanagement', [
            'amenities' => Amenity::orderBy('amenities_name')->get(),
        ]);
    })->name('amenities');

    Route::post('/amenities', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'amenities_name' => ['required', 'string', 'max:255'],
            'daytime_price' => ['required', 'numeric'],
            'nighttime_price' => ['required', 'numeric'],
            'daytime_aircon_price' => ['nullable', 'numeric'],
            'nighttime_aircon_price' => ['nullable', 'numeric'],
            'additional_per_head' => ['nullable', 'numeric'],
            'minimum_capacity' => ['nullable', 'numeric'],
            'maximum_capacity' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'image', 'max:4096'],
            'status' => ['nullable', 'in:enabled,disabled'],
            'sale_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('amenities_images', 'public');
        }

        $salePercentage = $data['sale_percentage'] ?? 0;
        $daytimePrice = $data['daytime_price'];
        $nighttimePrice = $data['nighttime_price'];
        $daytimeAirconPrice = $data['daytime_aircon_price'] ?? null;
        $nighttimeAirconPrice = $data['nighttime_aircon_price'] ?? null;

        // Calculate current prices based on sale percentage
        $currentDaytimePrice = $salePercentage > 0 ? $daytimePrice * (1 - $salePercentage / 100) : $daytimePrice;
        $currentNighttimePrice = $salePercentage > 0 ? $nighttimePrice * (1 - $salePercentage / 100) : $nighttimePrice;
        $currentDaytimeAirconPrice = $daytimeAirconPrice && $salePercentage > 0 ? $daytimeAirconPrice * (1 - $salePercentage / 100) : $daytimeAirconPrice;
        $currentNighttimeAirconPrice = $nighttimeAirconPrice && $salePercentage > 0 ? $nighttimeAirconPrice * (1 - $salePercentage / 100) : $nighttimeAirconPrice;

        Amenity::create([
            'id' => Str::uuid(),
            'amenities_name' => $data['amenities_name'],
            'daytime_price' => $currentDaytimePrice,
            'nighttime_price' => $currentNighttimePrice,
            'daytime_aircon_price' => $currentDaytimeAirconPrice,
            'nighttime_aircon_price' => $currentNighttimeAirconPrice,
            'original_daytime_price' => $daytimePrice,
            'original_nighttime_price' => $nighttimePrice,
            'original_daytime_aircon_price' => $daytimeAirconPrice,
            'original_nighttime_aircon_price' => $nighttimeAirconPrice,
            'additional_per_head' => $data['additional_per_head'] ?? null,
            'minimum_capacity' => $data['minimum_capacity'] ?? null,
            'maximum_capacity' => $data['maximum_capacity'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $imagePath,
            'status' => ($data['status'] ?? 'enabled') === 'enabled',
            'sale_percentage' => $salePercentage,
        ]);

        return redirect()->route('admin.amenities')->with('success', 'Amenity created successfully.');
    })->name('amenities.store');

    Route::put('/amenities/{amenity}', function (Request $request, Amenity $amenity) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'amenities_name' => ['required', 'string', 'max:255'],
            'daytime_price' => ['required', 'numeric'],
            'nighttime_price' => ['required', 'numeric'],
            'daytime_aircon_price' => ['nullable', 'numeric'],
            'nighttime_aircon_price' => ['nullable', 'numeric'],
            'additional_per_head' => ['nullable', 'numeric'],
            'minimum_capacity' => ['nullable', 'numeric'],
            'maximum_capacity' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'image', 'max:4096'],
            'existing_image' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:enabled,disabled'],
            'sale_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $imagePath = $data['existing_image'] ?? $amenity->image;
        if ($request->hasFile('image')) {
            if ($amenity->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($amenity->image);
            }
            $imagePath = $request->file('image')->store('amenities_images', 'public');
        }

        $salePercentage = $data['sale_percentage'] ?? 0;
        $daytimePrice = $data['daytime_price'];
        $nighttimePrice = $data['nighttime_price'];
        $daytimeAirconPrice = $data['daytime_aircon_price'] ?? null;
        $nighttimeAirconPrice = $data['nighttime_aircon_price'] ?? null;

        // Calculate current prices based on sale percentage
        $currentDaytimePrice = $salePercentage > 0 ? $daytimePrice * (1 - $salePercentage / 100) : $daytimePrice;
        $currentNighttimePrice = $salePercentage > 0 ? $nighttimePrice * (1 - $salePercentage / 100) : $nighttimePrice;
        $currentDaytimeAirconPrice = $daytimeAirconPrice && $salePercentage > 0 ? $daytimeAirconPrice * (1 - $salePercentage / 100) : $daytimeAirconPrice;
        $currentNighttimeAirconPrice = $nighttimeAirconPrice && $salePercentage > 0 ? $nighttimeAirconPrice * (1 - $salePercentage / 100) : $nighttimeAirconPrice;

        $amenity->update([
            'amenities_name' => $data['amenities_name'],
            'daytime_price' => $currentDaytimePrice,
            'nighttime_price' => $currentNighttimePrice,
            'daytime_aircon_price' => $currentDaytimeAirconPrice,
            'nighttime_aircon_price' => $currentNighttimeAirconPrice,
            'original_daytime_price' => $daytimePrice,
            'original_nighttime_price' => $nighttimePrice,
            'original_daytime_aircon_price' => $daytimeAirconPrice,
            'original_nighttime_aircon_price' => $nighttimeAirconPrice,
            'additional_per_head' => $data['additional_per_head'] ?? null,
            'minimum_capacity' => $data['minimum_capacity'] ?? null,
            'maximum_capacity' => $data['maximum_capacity'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $imagePath,
            'status' => ($data['status'] ?? 'enabled') === 'enabled',
            'sale_percentage' => $salePercentage,
        ]);

        return redirect()->route('admin.amenities')->with('success', 'Amenity updated successfully.');
    })->name('amenities.update');

    Route::delete('/amenities/{amenity}', function (Request $request, Amenity $amenity) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        if ($amenity->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($amenity->image);
        }

        $amenity->delete();
        return redirect()->route('admin.amenities')->with('success', 'Amenity deleted successfully.');
    })->name('amenities.destroy');

    Route::get('/users', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        return view('admin.admin_usermanagement', [
            'staffAccounts' => StaffAccount::orderBy('name')->get(),
        ]);
    })->name('users');

    Route::post('/users', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:staff_accounts,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'ban_status' => ['nullable', 'boolean'],
        ]);

        StaffAccount::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'ban_status' => (bool) ($data['ban_status'] ?? false),
        ]);

        return redirect()->route('admin.users')->with('success', 'Staff account created successfully.');
    })->name('users.store');

    Route::put('/users/{staffAccount}', function (Request $request, StaffAccount $staffAccount) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:staff_accounts,email,' . $staffAccount->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'ban_status' => ['nullable', 'boolean'],
        ]);

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'ban_status' => (bool) ($data['ban_status'] ?? false),
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $staffAccount->update($update);

        return redirect()->route('admin.users')->with('success', 'Staff account updated successfully.');
    })->name('users.update');

    Route::patch('/users/{staffAccount}/ban', function (Request $request, StaffAccount $staffAccount) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        $staffAccount->update([
            'ban_status' => ! $staffAccount->ban_status,
        ]);

        return redirect()->route('admin.users')->with('success', $staffAccount->ban_status ? 'Staff account banned.' : 'Staff account unbanned.');
    })->name('users.toggle-ban');

    Route::delete('/users/{staffAccount}', function (Request $request, StaffAccount $staffAccount) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        $staffAccount->delete();

        return redirect()->route('admin.users')->with('success', 'Staff account deleted successfully.');
    })->name('users.destroy');

    Route::get('/reports', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        // Optimize by fetching only needed fields for aggregations
        $reservations = Reservation::select('id', 'reservation_type', 'payment_status', 'amount_paid', 'status', 'number_of_guests', 'booker_name', 'reservation_date', 'created_at')
            ->with(['reservationAmenities.amenity', 'reservationGuests.customer'])
            ->orderByDesc('created_at')
            ->get();

        $totalReservations = $reservations->count();
        $checkedInGuests = ReservationGuest::query()
            ->whereNull('checked_out_at')
            ->whereHas('reservation', function ($query) {
                $query->where('status', 'Checked In');
            })
            ->count();

        $revenue = $reservations->sum('amount_paid');
        $pendingReservations = $reservations->where('status', 'Pending')->count();
        $cancelledReservations = $reservations->where('status', 'Cancelled')->count();

        $reservationTypeBreakdown = $reservations
            ->groupBy('reservation_type')
            ->map(fn ($items, $type) => [
                'type' => ucfirst(str_replace('_', ' ', $type)),
                'count' => $items->count(),
            ])
            ->values();

        $paymentStatusBreakdown = $reservations
            ->groupBy('payment_status')
            ->map(fn ($items, $status) => [
                'status' => $status,
                'count' => $items->count(),
            ])
            ->values();

        $amenityBreakdown = $reservations
            ->flatMap(fn ($reservation) => $reservation->reservationAmenities)
            ->groupBy(fn ($item) => $item->amenity?->amenities_name ?? 'Unknown')
            ->map(fn ($items) => [
                'name' => $items->first()->amenity?->amenities_name ?? 'Unknown',
                'count' => $items->count(),
                'revenue' => $items->sum(fn ($item) => (float) $item->price_at_booking * (int) $item->quantity),
            ])
            ->sortByDesc('count')
            ->values();

        $totalGuests = $reservations->sum('number_of_guests');

        $uniqueCustomers = $reservations
            ->flatMap(function ($reservation) {
                $guestNames = $reservation->reservationGuests
                    ->map(fn ($guest) => trim(($guest->customer?->first_name ?? '') . ' ' . ($guest->customer?->last_name ?? '')))
                    ->filter();

                return $guestNames->push($reservation->booker_name)->filter();
            })
            ->unique()
            ->filter()
            ->values();

        $customerCount = $uniqueCustomers->count();

        $mostBookedAmenity = $amenityBreakdown->first()['name'] ?? 'None';
        $mostBookedAmenityCount = $amenityBreakdown->first()['count'] ?? 0;

        $dailyBookingCounts = $reservations
            ->filter(fn ($reservation) => $reservation->reservation_date)
            ->groupBy(fn ($reservation) => $reservation->reservation_date)
            ->map->count()
            ->sortDesc();

        $peakBookedDay = $dailyBookingCounts->keys()->first() ?? null;
        $peakBookedDayCount = $dailyBookingCounts->first() ?? 0;

        $monthlyBookingCounts = $reservations
            ->filter(fn ($reservation) => $reservation->reservation_date)
            ->groupBy(fn ($reservation) => \Illuminate\Support\Carbon::parse($reservation->reservation_date)->format('F Y'))
            ->map->count()
            ->sortDesc();

        $peakBookedMonth = $monthlyBookingCounts->keys()->first() ?? null;
        $peakBookedMonthCount = $monthlyBookingCounts->first() ?? 0;

        $amenityOptions = $amenityBreakdown
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();

        $statusOptions = $reservations
            ->pluck('status')
            ->unique()
            ->sort()
            ->values();

        $checkInDates = $reservations
            ->pluck('reservation_date')
            ->filter()
            ->sort()
            ->values();

        $firstCheckInDate = $checkInDates->first() ?: now()->toDateString();
        $lastCheckInDate = $checkInDates->last() ?: now()->toDateString();

        return view('admin.admin_reports', [
            'reservations' => $reservations,
            'totalReservations' => $totalReservations,
            'checkedInGuests' => $checkedInGuests,
            'totalGuests' => $totalGuests,
            'customerCount' => $customerCount,
            'revenue' => $revenue,
            'pendingReservations' => $pendingReservations,
            'cancelledReservations' => $cancelledReservations,
            'reservationTypeBreakdown' => $reservationTypeBreakdown,
            'paymentStatusBreakdown' => $paymentStatusBreakdown,
            'amenityBreakdown' => $amenityBreakdown,
            'amenityOptions' => $amenityOptions,
            'statusOptions' => $statusOptions,
            'mostBookedAmenity' => $mostBookedAmenity,
            'mostBookedAmenityCount' => $mostBookedAmenityCount,
            'peakBookedDay' => $peakBookedDay,
            'peakBookedDayCount' => $peakBookedDayCount,
            'peakBookedMonth' => $peakBookedMonth,
            'peakBookedMonthCount' => $peakBookedMonthCount,
            'firstCheckInDate' => $firstCheckInDate,
            'lastCheckInDate' => $lastCheckInDate,
        ]);
    })->name('reports');

    Route::get('/settings', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }
        $parkSettings = \App\Models\ParkSetting::first();
        return view('admin.admin_settings', ['parkSettings' => $parkSettings]);
    })->name('settings');

    Route::post('/settings/park/update', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'daytime_start' => 'required',
            'daytime_end' => 'required',
            'nighttime_start' => 'required',
            'nighttime_end' => 'required',
            'daytime_adult_entrance_fee' => 'required|numeric|min:0',
            'daytime_child_entrance_fee' => 'required|numeric|min:0',
            'nighttime_adult_entrance_fee' => 'required|numeric|min:0',
            'nighttime_child_entrance_fee' => 'required|numeric|min:0',
            'day_pool_fee' => 'required|numeric|min:0',
            'night_pool_fee' => 'required|numeric|min:0',
            'facebook_link' => 'nullable|url|max:255',
        ]);

        $parkSettings = \App\Models\ParkSetting::first();
        if (!$parkSettings) {
            $parkSettings = new \App\Models\ParkSetting();
        }

        $parkSettings->fill($validated);
        $parkSettings->save();

        return redirect()->route('admin.settings')->with('success', 'Park settings updated successfully.');
    })->name('settings.park.update');

    Route::post('/send-password-otp', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        $admin = \App\Models\AdminAccount::find($user['id']);

        // Verify current password
        if (!Hash::check($data['current_password'], $admin->password)) {
            return response()->json([
                'errors' => [
                    'current_password' => ['Current password is incorrect'],
                ],
            ], 422);
        }

        // Generate and store OTP
        $otp = random_int(100000, 999999);
        $admin->update(['password_otp' => $otp]);

        // Send OTP email
        try {
            Mail::to($admin->email)->send(new \App\Mail\AdminSettingsOtpMail($otp, $admin->name));
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to send OTP email'], 500);
        }

        return response()->json(['message' => 'OTP sent to recovery email']);
    })->name('send-password-otp')->withoutMiddleware([VerifyCsrfToken::class]);

    Route::post('/verify-password-otp', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'otp_code' => ['required', 'string', 'size:6'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        $admin = \App\Models\AdminAccount::find($user['id']);

        // Verify OTP
        if ($admin->password_otp !== $data['otp_code']) {
            return response()->json(['message' => 'Invalid OTP code'], 422);
        }

        // Update password and clear OTP
        $admin->update([
            'password' => Hash::make($data['new_password']),
            'password_otp' => null,
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    })->name('verify-password-otp')->withoutMiddleware([VerifyCsrfToken::class]);

    Route::post('/send-email-otp', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'new_email' => ['required', 'email', 'unique:admin_accounts,email'],
        ]);

        $admin = \App\Models\AdminAccount::find($user['id']);

        // Generate and store OTP
        $otp = random_int(100000, 999999);
        $admin->update(['password_otp' => $otp]);

        // Send OTP email to CURRENT email
        try {
            Mail::to($admin->email)->send(new \App\Mail\AdminEmailChangeOtpMail($otp, $admin->name, $data['new_email']));
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to send OTP email'], 500);
        }

        return response()->json(['message' => 'OTP sent to your current email']);
    })->name('send-email-otp')->withoutMiddleware([VerifyCsrfToken::class]);

    Route::post('/verify-email-otp', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'otp_code' => ['required', 'string', 'size:6'],
            'new_email' => ['required', 'email'],
        ]);

        $admin = \App\Models\AdminAccount::find($user['id']);

        // Verify OTP
        if ($admin->password_otp !== $data['otp_code']) {
            return response()->json(['message' => 'Invalid OTP code'], 422);
        }

        // Update email and clear OTP
        $admin->update([
            'email' => $data['new_email'],
            'password_otp' => null,
        ]);

        // Update session
        $user['email'] = $data['new_email'];
        $request->session()->put('auth_user', $user);

        return response()->json(['message' => 'Email changed successfully']);
    })->name('verify-email-otp')->withoutMiddleware([VerifyCsrfToken::class]);
});

Route::prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $today = now()->toDateString();

        $todayCheckIns = Reservation::query()
            ->whereDate('check_in', $today)
            ->where('status', 'Checked In')
            ->count();

        $pendingReservationsCount = Reservation::query()
            ->where('reservation_type', 'online')
            ->where(function ($query) {
                $query->where('status', 'Pending')
                    ->orWhere('status', 'Confirmed');
            })
            ->count();

        $guestsOnSiteCount = ReservationGuest::query()
            ->whereNull('checked_out_at')
            ->whereHas('reservation', function ($query) {
                $query->where('status', 'Checked In');
            })
            ->count();

        $recentActivity = collect([
            [
                'text' => 'New online reservations need confirmation.',
                'time' => 'Just updated',
            ],
        ]);

        $latestReservations = Reservation::query()
            ->with(['reservationGuests.customer'])
            ->orderByDesc('created_at')
            ->take(4)
            ->get();

        $activityItems = $latestReservations->map(function ($reservation) {
            $guestNames = $reservation->reservationGuests->map(function ($guestEntry) {
                return trim(($guestEntry->customer?->first_name ?? '') . ' ' . ($guestEntry->customer?->last_name ?? ''));
            })->filter()->implode(', ');

            return [
                'text' => $guestNames !== ''
                    ? $guestNames . ' reserved ' . $reservation->number_of_guests . ' guest' . ($reservation->number_of_guests > 1 ? 's' : '') . ' for ' . $reservation->check_in
                    : 'Reservation received for ' . $reservation->check_in,
                'time' => $reservation->created_at?->diffForHumans() ?? 'recently added',
            ];
        })->values();

        // ---- Chart data for the redesigned dashboard ----
        // Bookings + collected revenue for the last 7 days (oldest first).
        $weekDays = [];
        $weekReservationCounts = [];
        $weekRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $weekDays[] = $date->format('D');
            $weekReservationCounts[] = Reservation::query()
                ->whereDate('reservation_date', $date)
                ->whereNotIn('status', ['Cancelled'])
                ->count();
            $weekRevenue[] = (float) Reservation::query()
                ->whereDate('check_in', $date)
                ->whereNotIn('status', ['Cancelled'])
                ->sum('amount_paid');
        }
        $todayRevenue = $weekRevenue[6] ?? 0;

        // Reservation status breakdown (only statuses that actually exist).
        $statusCounts = Reservation::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        $statusBreakdown = collect(['Pending', 'Confirmed', 'Checked In', 'Checked Out', 'Cancelled'])
            ->mapWithKeys(fn ($status) => [$status => (int) ($statusCounts[$status] ?? 0)])
            ->filter()
            ->toArray();

        // Most booked amenities (by reservation_amenities rows).
        $topAmenities = ReservationAmenity::query()
            ->selectRaw('amenity_id, COUNT(*) as total')
            ->whereNotNull('amenity_id')
            ->with('amenity')
            ->groupBy('amenity_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($ra) => [
                'name' => $ra->amenity?->amenities_name ?? 'Unknown',
                'total' => (int) $ra->total,
            ]);
        $topAmenityMax = $topAmenities->max('total') ?: 1;

        // Today's expected arrivals (still pending confirmation or confirmed).
        $todayArrivals = Reservation::query()
            ->whereDate('reservation_date', now()->toDateString())
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->orderBy('reservation_date')
            ->get(['booker_name', 'number_of_guests', 'status', 'reservation_date']);

        return view('staff.staff_dashboard', compact(
            'todayCheckIns',
            'pendingReservationsCount',
            'guestsOnSiteCount',
            'activityItems',
            'weekDays',
            'weekReservationCounts',
            'weekRevenue',
            'todayRevenue',
            'statusBreakdown',
            'topAmenities',
            'topAmenityMax',
            'todayArrivals'
        ));
    })->name('dashboard');

    Route::get('/reservations', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $reservations = Reservation::query()
            ->with(['reservationAmenities.amenity', 'reservationGuests.customer'])
            ->where('reservation_type', 'online')
            ->where(function ($query) {
                $query->whereNull('check_in')
                    ->orWhere('check_in', '');
            })
            ->where(function ($query) {
                $query->where('status', 'Pending')
                    ->orWhere('status', 'Confirmed');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $reservationData = $reservations->mapWithKeys(function ($reservation) {
            // Extract unique time slots from reservation amenities
            $timeSlots = $reservation->reservationAmenities
                ->pluck('pricing_type')
                ->map(function ($pricingType) {
                    // Normalize pricing type to base slot (remove Aircon suffix)
                    $baseSlot = str_replace([' Aircon', 'Aircon'], '', $pricingType);
                    // Map to standard slot names
                    if (str_contains($baseSlot, 'Daytime')) return 'Daytime';
                    if (str_contains($baseSlot, 'Nighttime')) return 'Nighttime';
                    if (str_contains($baseSlot, 'DayNight')) return 'DayNight Time';
                    return $baseSlot;
                })
                ->unique()
                ->values()
                ->sort()
                ->toArray();

            return [$reservation->id => [
                'id' => $reservation->id,
                'booker_name' => $reservation->booker_name,
                'phone' => $reservation->phone,
                'email' => $reservation->email,
                'reservation_date' => $reservation->reservation_date,
                'check_in' => $reservation->check_in,
                'number_of_guests' => $reservation->number_of_guests,
                'status' => $reservation->status,
                'reservation_type' => $reservation->reservation_type,
                'total_amount' => $reservation->total_amount,
                'amount_paid' => $reservation->amount_paid,
                'remaining_balance' => $reservation->remaining_balance,
                'payment_status' => $reservation->payment_status,
                'time_slots' => $timeSlots,
                'reservation_amenities' => $reservation->reservationAmenities->map(function ($reservationAmenity) {
                    return [
                        'pricing_type' => $reservationAmenity->pricing_type,
                        'price_at_booking' => $reservationAmenity->price_at_booking,
                        'quantity' => $reservationAmenity->quantity,
                        'remarks' => $reservationAmenity->remarks,
                        'amenity' => [
                            'amenities_name' => $reservationAmenity->amenity?->amenities_name,
                        ],
                    ];
                })->values(),
                'reservation_guests' => $reservation->reservationGuests->map(function ($guestEntry) {
                    $customer = $guestEntry->customer;
                    return [
                        'id' => $guestEntry->id,
                        'customer_id' => $customer?->id,
                        'is_primary_guest' => (bool) $guestEntry->is_primary_guest,
                        'customer' => [
                            'first_name' => $customer?->first_name,
                            'middle_name' => $customer?->middle_name,
                            'last_name' => $customer?->last_name,
                            'age' => $customer?->age,
                            'gender' => $customer?->gender,
                            'is_foreigner' => (bool) ($customer?->is_foreigner ?? false),
                            'phone' => $customer?->phone,
                            'email' => $customer?->email,
                        ],
                    ];
                })->values(),
            ]];
        });

        return view('staff.staff_reservations', compact('reservations', 'reservationData'));
    })->name('reservations');

    Route::get('/occupancy-monitor', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $amenities = \App\Models\Amenity::where('status', true)
            ->orderBy('amenities_name')
            ->get();

        $today = now()->toDateString();

        // Fetch reservations for occupancy monitor
        // Include: Checked In (any date), Pending (today only)
        // Exclude: Cancelled, Checked Out
        $reservations = \App\Models\Reservation::query()
            ->where(function ($query) use ($today) {
                $query->where('status', 'Checked In')
                      ->orWhere(function ($q) use ($today) {
                          $q->where('status', 'Pending')
                            ->whereDate('reservation_date', $today);
                      });
            })
            ->whereNotIn('status', ['Cancelled', 'Checked Out'])
            ->with(['reservationAmenities' => function ($query) {
                $query->with('amenity');
            }])
            ->get();

        // Build occupancy data for each amenity
        $occupancyData = [];
        foreach ($amenities as $amenity) {
            $occupancyData[$amenity->id] = [
                'occupied' => [],
                'reserved' => [],
            ];

            foreach ($reservations as $reservation) {
                foreach ($reservation->reservationAmenities as $ra) {
                    if ($ra->amenity_id === $amenity->id) {
                        $timeSlot = $ra->pricing_type;
                        // Normalize time slot
                        if (str_contains($timeSlot, 'Daytime')) $timeSlot = 'Daytime';
                        elseif (str_contains($timeSlot, 'Nighttime')) $timeSlot = 'Nighttime';
                        elseif (str_contains($timeSlot, 'DayNight')) $timeSlot = 'DayNight Time';

                        $entry = [
                            'reservation_id' => $reservation->id,
                            'time_slot' => $timeSlot,
                            'status' => $reservation->status,
                        ];

                        if ($reservation->status === 'Checked In') {
                            $occupancyData[$amenity->id]['occupied'][] = $entry;
                        } elseif ($reservation->status === 'Pending') {
                            $reservationDate = \Illuminate\Support\Carbon::parse($reservation->reservation_date)->toDateString();
                            if ($reservationDate === $today) {
                                $occupancyData[$amenity->id]['reserved'][] = $entry;
                            }
                        }
                    }
                }
            }
        }

        return view('staff.staff_occupancy_monitor', compact('amenities', 'occupancyData'));
    })->name('occupancy-monitor');

    Route::get('/reports', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $reservations = Reservation::query()
            ->with(['reservationAmenities.amenity', 'reservationGuests.customer'])
            ->orderByDesc('reservation_date')
            ->get();

        $reportRows = $reservations->map(function ($reservation) {
            $customer = $reservation->reservationGuests->first()?->customer;
            $customerName = $customer ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) : $reservation->booker_name;
            $amenityNames = $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->values();

            return [
                'id' => $reservation->id,
                'customer_name' => $customerName ?: $reservation->booker_name,
                'reservation_date' => $reservation->reservation_date,
                'check_in' => $reservation->check_in,
                'amenities' => $amenityNames->isEmpty() ? 'None' : $amenityNames->join(', '),
                'status' => $reservation->status,
                'payment_status' => $reservation->payment_status,
                'total_amount' => $reservation->total_amount,
            ];
        });

        $customerOptions = $reportRows->pluck('customer_name')->unique()->sort()->values();
        $amenityOptions = $reportRows->flatMap(function ($row) {
            return $row['amenities'] === 'None' ? [] : explode(', ', $row['amenities']);
        })->unique()->sort()->values();
        $statusOptions = $reportRows->pluck('status')->unique()->sort()->values();

        $reservationDates = $reportRows->pluck('reservation_date')->filter();
        $firstCheckInDate = $reservationDates->min() ?? now()->toDateString();
        $lastCheckInDate = $reservationDates->max() ?? now()->toDateString();

        $totalReservations = $reportRows->count();
        $customerCount = $reportRows->pluck('customer_name')->unique()->count();
        $amenityCount = $reportRows->flatMap(function ($row) {
            return $row['amenities'] === 'None' ? [] : explode(', ', $row['amenities']);
        })->unique()->count();

        $totalRevenue = $reportRows->sum('total_amount');

        return view('staff.staff_reports', compact(
            'reportRows',
            'customerOptions',
            'amenityOptions',
            'statusOptions',
            'firstCheckInDate',
            'lastCheckInDate',
            'totalReservations',
            'customerCount',
            'amenityCount',
            'totalRevenue'
        ));
    })->name('reports');

    Route::get('/check-ins', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $customers = Customer::with(['reservationGuests' => function ($query) {
            $query->with([
                'reservation' => function ($reservationQuery) {
                    $reservationQuery->with(['reservationAmenities' => function ($amenityQuery) {
                        $amenityQuery->with('amenity');
                    }, 'reservationGuests.customer']);
                },
                'customer',
            ]);
        }])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $activeReservations = Reservation::whereNotNull('check_in')
            ->whereNull('check_out')
            ->with(['reservationGuests' => function ($query) {
                $query->with('customer');
            }, 'reservationAmenities.amenity'])
            ->orderBy('check_in', 'desc')
            ->get();


        // NOTE: number_of_guests is kept in sync inside the check-in POST
        // handler (a GET request must never mutate data).
        $reservationData = $activeReservations->mapWithKeys(function ($reservation) {
            $primaryGuest = $reservation->reservationGuests->firstWhere('is_primary_guest', true);
            $primaryCustomer = $primaryGuest?->customer;

            return [$reservation->id => [
                'id' => $reservation->id,
                'booker_name' => $reservation->booker_name,
                'check_in' => $reservation->check_in,
                'check_out' => $reservation->check_out,
                'reservation_date' => $reservation->reservation_date,
                'status' => $reservation->status,
                'reservation_type' => $reservation->reservation_type,
                'number_of_guests' => $reservation->number_of_guests,
                'total_amount' => $reservation->total_amount,
                'amount_paid' => $reservation->amount_paid,
                'remaining_balance' => $reservation->remaining_balance,
                'payment_status' => $reservation->payment_status,
                'phone' => $primaryCustomer?->phone ?? $reservation->phone,
                'email' => $primaryCustomer?->email ?? $reservation->email,
                'reservation_guests' => $reservation->reservationGuests->map(function ($guestEntry) {
                    $customer = $guestEntry->customer;
                    return [
                        'id' => $guestEntry->id,
                        'customer_id' => $customer?->id,
                        'is_primary_guest' => (bool) $guestEntry->is_primary_guest,
                        'checked_out_at' => $guestEntry->checked_out_at,
                        'customer' => [
                            'first_name' => $customer?->first_name,
                            'middle_name' => $customer?->middle_name,
                            'last_name' => $customer?->last_name,
                            'age' => $customer?->age,
                            'gender' => $customer?->gender,
                            'is_foreigner' => (bool) ($customer?->is_foreigner ?? false),
                            'phone' => $customer?->phone,
                            'email' => $customer?->email,
                        ],
                    ];
                })->values(),
                'reservation_amenities' => $reservation->reservationAmenities->map(function ($amenity) {
                    return [
                        'amenity_name' => $amenity->amenity?->amenities_name ?? ($amenity->amenity_id ?? 'Unknown'),
                        'pricing_type' => $amenity->pricing_type,
                        'price' => $amenity->price_at_booking ?? 0,
                        'quantity' => $amenity->quantity ?? 1,
                        'amenity_id' => $amenity->amenity_id,
                    ];
                })->values(),
            ]];
        });

        $amenities = Amenity::where('status', true)
            ->orderBy('amenities_name')
            ->get();

        $guestData = $customers->mapWithKeys(function ($customer) {
            return [$customer->id => [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'middle_name' => $customer->middle_name,
                'last_name' => $customer->last_name,
                'age' => $customer->age,
                'gender' => $customer->gender,
                'is_foreigner' => (bool) $customer->is_foreigner,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'reservation_guests' => $customer->reservationGuests->map(function ($reservationGuest) {
                    return [
                        'id' => $reservationGuest->id,
                        'checked_out_at' => $reservationGuest->checked_out_at,
                        'is_primary_guest' => (bool) $reservationGuest->is_primary_guest,
                        'reservation' => $reservationGuest->reservation ? [
                            'id' => $reservationGuest->reservation->id,
                            'reservation_type' => $reservationGuest->reservation->reservation_type,
                            'status' => $reservationGuest->reservation->status,
                            'check_in' => $reservationGuest->reservation->check_in,
                            'booker_name' => $reservationGuest->reservation->booker_name,
                            'reservation_amenities' => $reservationGuest->reservation->reservationAmenities->map(function ($reservationAmenity) {
                                return [
                                    'pricing_type' => $reservationAmenity->pricing_type,
                                    'amenity' => [
                                        'amenities_name' => $reservationAmenity->amenity?->amenities_name,
                                    ],
                                ];
                            })->values(),
                            'reservation_guests' => $reservationGuest->reservation->reservationGuests->map(function ($guestEntry) {
                                return [
                                    'is_primary_guest' => (bool) $guestEntry->is_primary_guest,
                                    'customer' => [
                                        'first_name' => $guestEntry->customer?->first_name,
                                        'last_name' => $guestEntry->customer?->last_name,
                                    ],
                                ];
                            })->values(),
                        ] : null,
                    ];
                })->values(),
            ]];
        });

        return view('staff.staff_check_ins', compact('customers', 'guestData', 'amenities', 'activeReservations', 'reservationData'));
    })->name('checkins');

    Route::get('/check-ins/lookup', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservationId = $request->query('reservation_id');
        if (! $reservationId) {
            return response()->json(['message' => 'Reservation ID is required'], 422);
        }

        $reservation = Reservation::query()
            ->with(['reservationGuests.customer', 'reservationAmenities.amenity'])
            ->where('id', $reservationId)
            ->where('reservation_type', 'online')
            ->whereIn('status', ['Pending', 'Confirmed', 'Checked In'])
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'Reservation not found or cannot be checked in.'], 404);
        }

        return response()->json([
            'reservation' => [
                'id' => $reservation->id,
                'booker_name' => $reservation->booker_name,
                'email' => $reservation->email,
                'phone' => $reservation->phone,
                'reservation_date' => $reservation->reservation_date,
                'check_in' => $reservation->check_in,
                'check_out' => $reservation->check_out,
                'reservation_type' => $reservation->reservation_type,
                'number_of_guests' => $reservation->number_of_guests,
                'status' => $reservation->status,
                'payment_status' => $reservation->payment_status,
                'total_amount' => $reservation->total_amount,
                'amount_paid' => $reservation->amount_paid,
                'remaining_balance' => $reservation->remaining_balance,
                'reservation_guests' => $reservation->reservationGuests->map(function ($guestEntry) {
                    $customer = $guestEntry->customer;
                    return [
                        'id' => $guestEntry->id,
                        'customer_id' => $guestEntry->customer_id,
                        'is_primary_guest' => (bool) $guestEntry->is_primary_guest,
                        'checked_out_at' => $guestEntry->checked_out_at,
                        'customer' => [
                            'first_name' => $customer?->first_name,
                            'middle_name' => $customer?->middle_name,
                            'last_name' => $customer?->last_name,
                            'age' => $customer?->age,
                            'gender' => $customer?->gender,
                            'is_foreigner' => (bool) ($customer?->is_foreigner ?? false),
                            'phone' => $customer?->phone,
                            'email' => $customer?->email,
                        ],
                    ];
                })->values(),
                'reservation_amenities' => $reservation->reservationAmenities->map(function ($amenity) {
                    return [
                        'amenity_name' => $amenity->amenity?->amenities_name,
                        'pricing_type' => $amenity->pricing_type,
                        'price_at_booking' => $amenity->price_at_booking,
                        'quantity' => $amenity->quantity,
                    ];
                })->values(),
            ],
        ]);
    })->name('checkins.lookup');

    Route::get('/records', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        // Get all checked-out reservation guests
        $checkedOutGuests = ReservationGuest::with(['customer', 'reservation' => function ($query) {
            $query->with(['reservationAmenities.amenity', 'reservationGuests.customer']);
        }])
            ->whereNotNull('checked_out_at')
            ->orderBy('checked_out_at', 'desc')
            ->get();

        // Get all checked-out reservations (reservations that are completed/checked out)
        $checkedOutReservations = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer'])
            ->where(function ($query) {
                $query->where('status', 'Checked Out')
                    ->orWhereNotNull('check_out')
                    ->orWhereHas('reservationGuests', function ($q) {
                        $q->whereNotNull('checked_out_at');
                    });
            })
            ->orderBy('check_out', 'desc')
            ->get();

        $amenities = Amenity::where('status', true)
            ->orderBy('amenities_name')
            ->get();

        $guestData = $checkedOutGuests->mapWithKeys(function ($guest) {
            return [$guest->customer_id => [
                'id' => $guest->customer->id,
                'first_name' => $guest->customer->first_name,
                'middle_name' => $guest->customer->middle_name,
                'last_name' => $guest->customer->last_name,
                'age' => $guest->customer->age,
                'gender' => $guest->customer->gender,
                'is_foreigner' => (bool) $guest->customer->is_foreigner,
                'checked_out_at' => $guest->checked_out_at,
                'reservation_guests' => [[
                    'reservation' => $guest->reservation ? [
                        'id' => $guest->reservation->id,
                        'status' => $guest->reservation->status,
                        'check_in' => $guest->reservation->check_in,
                        'check_out' => $guest->reservation->check_out,
                        'booker_name' => $guest->reservation->booker_name,
                        'reservation_amenities' => $guest->reservation->reservationAmenities->map(function ($ra) {
                            return [
                                'amenity' => ['amenities_name' => $ra->amenity?->amenities_name],
                                'pricing_type' => $ra->pricing_type,
                            ];
                        })->toArray(),
                        'reservation_guests' => $guest->reservation->reservationGuests->map(function ($rg) {
                            return [
                                'is_primary_guest' => $rg->is_primary_guest,
                                'customer' => $rg->customer ? [
                                    'first_name' => $rg->customer->first_name,
                                    'last_name' => $rg->customer->last_name,
                                ] : null,
                            ];
                        })->toArray(),
                    ] : null,
                    'checked_out_at' => $guest->checked_out_at,
                ]],
            ]];
        });

        $reservationData = $checkedOutReservations->mapWithKeys(function ($reservation) {
            return [$reservation->id => [
                'id' => $reservation->id,
                'booker_name' => $reservation->booker_name,
                'email' => $reservation->email,
                'phone' => $reservation->phone,
                'reservation_date' => $reservation->reservation_date,
                'check_in' => $reservation->check_in,
                'check_out' => $reservation->check_out,
                'number_of_guests' => $reservation->number_of_guests,
                'status' => $reservation->status,
                'reservation_type' => $reservation->reservation_type,
                'total_amount' => $reservation->total_amount,
                'amount_paid' => $reservation->amount_paid,
                'created_at' => $reservation->created_at,
                'reservation_guests' => $reservation->reservationGuests->map(function ($guest) {
                    return [
                        'is_primary_guest' => $guest->is_primary_guest,
                        'checked_out_at' => $guest->checked_out_at,
                        'customer' => $guest->customer ? [
                            'first_name' => $guest->customer->first_name,
                            'middle_name' => $guest->customer->middle_name,
                            'last_name' => $guest->customer->last_name,
                            'age' => $guest->customer->age,
                            'gender' => $guest->customer->gender,
                            'is_foreigner' => (bool) $guest->customer->is_foreigner,
                        ] : null,
                    ];
                })->toArray(),
                'reservation_amenities' => $reservation->reservationAmenities->map(function ($amenity) {
                    return [
                        'amenity' => ['amenities_name' => $amenity->amenity?->amenities_name],
                        'amenity_name' => $amenity->amenity?->amenities_name,
                        'pricing_type' => $amenity->pricing_type,
                        'price_at_booking' => $amenity->price_at_booking,
                        'price' => $amenity->price_at_booking,
                        'quantity' => $amenity->quantity,
                    ];
                })->toArray(),
            ]];
        });

        return view('staff.staff_records', compact('checkedOutGuests', 'checkedOutReservations', 'guestData', 'reservationData', 'amenities'));
    })->name('records');

    Route::post('/check-ins/guests', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'guest_mode' => ['required', 'in:with_primary,visitors_only'],
            'reservation_type' => ['required', 'in:walk_in,online'],
            'check_in' => ['nullable', 'date'],
            'primary_guest' => ['nullable', 'array'],
            'primary_guest.first_name' => ['nullable', 'string', 'max:255'],
            'primary_guest.middle_name' => ['nullable', 'string', 'max:255'],
            'primary_guest.last_name' => ['nullable', 'string', 'max:255'],
            'primary_guest.age' => ['nullable', 'integer', 'min:0'],
            'primary_guest.gender' => ['nullable', 'in:Male,Female'],
            'primary_guest.is_foreigner' => ['nullable', 'boolean'],
            'primary_guest.phone' => ['nullable', 'string', 'max:255'],
            'primary_guest.email' => ['nullable', 'email', 'max:255'],
            'companions' => ['nullable', 'array'],
            'companions.*.first_name' => ['required_with:companions.*.last_name', 'string', 'max:255'],
            'companions.*.middle_name' => ['nullable', 'string', 'max:255'],
            'companions.*.last_name' => ['required_with:companions.*.first_name', 'string', 'max:255'],
            'companions.*.age' => ['nullable', 'integer', 'min:0'],
            'companions.*.gender' => ['nullable', 'in:Male,Female'],
            'companions.*.is_foreigner' => ['nullable', 'boolean'],
            'companions.*.phone' => ['nullable', 'string', 'max:255'],
            'companions.*.email' => ['nullable', 'email', 'max:255'],
            'selected_amenities' => ['nullable', 'array'],
            'selected_amenities.*.amenity_id' => ['required_with:selected_amenities.*.pricing_type', 'string'],
            'selected_amenities.*.pricing_type' => ['required_with:selected_amenities.*.amenity_id', 'in:Daytime,Nighttime,Daytime Aircon,Nighttime Aircon'],
            'selected_amenities.*.price_at_booking' => ['required_with:selected_amenities.*.amenity_id', 'numeric'],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $primaryGuestCount = ($data['guest_mode'] === 'with_primary' && ! empty($data['primary_guest'])) ? 1 : 0;
        $companionCount = count($data['companions'] ?? []);
        $guestCount = $primaryGuestCount + $companionCount;

        $reservation = Reservation::create([
            'booker_name' => trim(($data['primary_guest']['first_name'] ?? '') . ' ' . ($data['primary_guest']['last_name'] ?? '')),
            'phone' => $data['primary_guest']['phone'] ?? '',
            'email' => $data['primary_guest']['email'] ?? '',
            'reservation_date' => now()->toDateTimeString(),
            'check_in' => $data['check_in'] ?? now()->toDateString(),
            'number_of_guests' => $guestCount > 0 ? $guestCount : 1,
            'reservation_type' => $data['reservation_type'],
            'status' => 'Checked In',
            'total_amount' => $data['total_amount'],
            'amount_paid' => 0,
            'remaining_balance' => $data['total_amount'],
            'payment_status' => 'Partially Paid',
        ]);

        $primaryCustomer = null;
        if ($data['guest_mode'] === 'with_primary') {
            $primaryGuestData = $data['primary_guest'] ?? [];
            $primaryFirstName = trim((string) ($primaryGuestData['first_name'] ?? '')) ?: 'Main';
            $primaryLastName = trim((string) ($primaryGuestData['last_name'] ?? '')) ?: 'Guest';
            $primaryEmail = trim((string) ($primaryGuestData['email'] ?? '')) ?: null;
            $primaryPhone = trim((string) ($primaryGuestData['phone'] ?? '')) ?: null;

            $primaryIsForeigner = (bool) ($primaryGuestData['is_foreigner'] ?? false);

            $primaryCustomer = Customer::firstOrCreate(
                [
                    'first_name' => $primaryFirstName,
                    'last_name' => $primaryLastName,
                    'email' => $primaryEmail,
                    'phone' => $primaryPhone,
                ],
                [
                    'first_name' => $primaryFirstName,
                    'middle_name' => $primaryGuestData['middle_name'] ?? null,
                    'last_name' => $primaryLastName,
                    'age' => $primaryGuestData['age'] ?? null,
                    'gender' => $primaryGuestData['gender'] ?? 'Male',
                    'is_foreigner' => $primaryIsForeigner,
                    'phone' => $primaryPhone,
                    'email' => $primaryEmail,
                ]
            );
        }

        if ($primaryCustomer) {
            ReservationGuest::create([
                'reservation_id' => $reservation->id,
                'customer_id' => $primaryCustomer->id,
                'is_primary_guest' => true,
            ]);
        }

        foreach ($data['companions'] ?? [] as $companionData) {
            $companionFirstName = trim((string) ($companionData['first_name'] ?? '')) ?: 'Companion';
            $companionLastName = trim((string) ($companionData['last_name'] ?? '')) ?: 'Guest';
            $companionEmail = trim((string) ($companionData['email'] ?? '')) ?: null;
            $companionPhone = trim((string) ($companionData['phone'] ?? '')) ?: null;

            $companionIsForeigner = (bool) ($companionData['is_foreigner'] ?? false);

            $companionCustomer = Customer::firstOrCreate(
                [
                    'first_name' => $companionFirstName,
                    'last_name' => $companionLastName,
                    'email' => $companionEmail,
                    'phone' => $companionPhone,
                ],
                [
                    'first_name' => $companionFirstName,
                    'middle_name' => $companionData['middle_name'] ?? null,
                    'last_name' => $companionLastName,
                    'age' => $companionData['age'] ?? null,
                    'gender' => $companionData['gender'] ?? 'Male',
                    'is_foreigner' => $companionIsForeigner,
                    'phone' => $companionPhone,
                    'email' => $companionEmail,
                ]
            );

            ReservationGuest::create([
                'reservation_id' => $reservation->id,
                'customer_id' => $companionCustomer->id,
                'is_primary_guest' => false,
            ]);
        }

        foreach ($data['selected_amenities'] ?? [] as $selectedAmenity) {
            ReservationAmenity::create([
                'reservation_id' => $reservation->id,
                'amenity_id' => $selectedAmenity['amenity_id'],
                'pricing_type' => $selectedAmenity['pricing_type'],
                'price_at_booking' => $selectedAmenity['price_at_booking'],
                'quantity' => 1,
                'remarks' => 'Reserved from staff check-ins form',
            ]);
        }

        return redirect()->route('staff.checkins')->with('success', 'Guest reservation created successfully.');
    })->name('checkins.guests.store');

    Route::post('/checkins/visit-only-check-in', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'guest_mode' => ['required', 'in:visitors_only'],
            'age_type' => ['required', 'in:adult,child'],
            'time_type' => ['required', 'in:daytime,nighttime,daynight'],
            'include_pool' => ['required', 'boolean'],
            'total_amount' => ['required', 'numeric'],
            'companions' => ['nullable', 'array'],
            'companions.*.first_name' => ['nullable', 'string', 'max:255'],
            'companions.*.middle_name' => ['nullable', 'string', 'max:255'],
            'companions.*.last_name' => ['nullable', 'string', 'max:255'],
            'companions.*.age' => ['nullable', 'string', 'max:255'],
            'companions.*.gender' => ['nullable', 'in:Male,Female'],
            'companions.*.is_foreigner' => ['nullable', 'boolean'],
            'companions.*.phone' => ['nullable', 'string', 'max:255'],
            'companions.*.email' => ['nullable', 'email', 'max:255'],
        ]);

        // Create a reservation for visit-only (without amenities)
        $reservation = Reservation::create([
            'booker_name' => 'Visit Only Guest',
            'phone' => '',
            'email' => '',
            'reservation_date' => now(),
            'check_in' => now(),
            'number_of_guests' => 1 + count($data['companions'] ?? []),
            'reservation_type' => 'walk_in',
            'status' => 'Checked In',
            'total_amount' => $data['total_amount'],
            'amount_paid' => 0,
            'remaining_balance' => $data['total_amount'],
            'payment_status' => 'Partially Paid',
        ]);

        // Create reservation amenity record for entrance fee (reservation_id can be null as per user request)
        ReservationAmenity::create([
            'reservation_id' => null, // null as requested
            'amenity_id' => null, // null for entrance fee
            'pricing_type' => ucfirst($data['time_type']),
            'price_at_booking' => $data['total_amount'],
            'quantity' => 1,
            'remarks' => 'Visit Only - ' . ucfirst($data['age_type']) . ' - ' . ucfirst($data['time_type']) . ($data['include_pool'] ? ' with Pool' : ''),
        ]);

        // Create main guest record
        $mainGuest = Customer::create([
            'first_name' => 'Visit',
            'middle_name' => 'Only',
            'last_name' => 'Guest',
            'age' => $data['age_type'] === 'adult' ? '18' : '12',
            'gender' => 'Male',
            'is_foreigner' => false,
            'phone' => '',
            'email' => '',
        ]);

        ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $mainGuest->id,
            'is_primary_guest' => true,
            'checked_out_at' => null,
        ]);

        // Create companion records
        foreach ($data['companions'] ?? [] as $companionData) {
            $companion = Customer::create([
                'first_name' => $companionData['first_name'] ?? '',
                'middle_name' => $companionData['middle_name'] ?? '',
                'last_name' => $companionData['last_name'] ?? '',
                'age' => $companionData['age'] ?? '',
                'gender' => $companionData['gender'] ?? 'Male',
                'is_foreigner' => isset($companionData['is_foreigner']) ? $companionData['is_foreigner'] : false,
                'phone' => $companionData['phone'] ?? '',
                'email' => $companionData['email'] ?? '',
            ]);

            ReservationGuest::create([
                'reservation_id' => $reservation->id,
                'customer_id' => $companion->id,
                'is_primary_guest' => false,
                'checked_out_at' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful',
            'reservation_id' => $reservation->id,
        ]);
    })->name('checkins.visit-only-check-in');

    Route::post('/reservations/{reservation}/check-in', function (Request $request, Reservation $reservation) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'guest_mode' => ['required', 'in:with_primary,visitors_only'],
            'primary_guest_id' => ['nullable', 'integer', 'exists:customers,id'],
            'primary_guest' => ['nullable', 'array'],
            'primary_guest.first_name' => ['nullable', 'string', 'max:255'],
            'primary_guest.middle_name' => ['nullable', 'string', 'max:255'],
            'primary_guest.last_name' => ['nullable', 'string', 'max:255'],
            'primary_guest.age' => ['nullable', 'string', 'max:255'],
            'primary_guest.gender' => ['nullable', 'in:Male,Female'],
            'primary_guest.is_foreigner' => ['nullable', 'boolean'],
            'primary_guest.phone' => ['nullable', 'string', 'max:255'],
            'primary_guest.email' => ['nullable', 'email', 'max:255'],
            'companions' => ['nullable', 'array'],
            'companions.*.first_name' => ['required_with:companions.*.last_name', 'string', 'max:255'],
            'companions.*.middle_name' => ['nullable', 'string', 'max:255'],
            'companions.*.last_name' => ['required_with:companions.*.first_name', 'string', 'max:255'],
            'companions.*.age' => ['nullable', 'string', 'max:255'],
            'companions.*.gender' => ['nullable', 'in:Male,Female'],
            'companions.*.is_foreigner' => ['nullable', 'boolean'],
            'companions.*.phone' => ['nullable', 'string', 'max:255'],
            'companions.*.email' => ['nullable', 'email', 'max:255'],
        ]);

        // Delete existing reservation guests to replace them
        ReservationGuest::where('reservation_id', $reservation->id)->delete();

        $primaryCustomer = null;

        // Handle primary guest - either update existing or create new
        if ($data['guest_mode'] === 'with_primary' && ! empty($data['primary_guest'])) {
            $primaryGuestData = $data['primary_guest'];
            $primaryFirstName = trim((string) ($primaryGuestData['first_name'] ?? '')) ?: 'Main';
            $primaryLastName = trim((string) ($primaryGuestData['last_name'] ?? '')) ?: 'Guest';
            $primaryEmail = trim((string) ($primaryGuestData['email'] ?? '')) ?: null;
            $primaryPhone = trim((string) ($primaryGuestData['phone'] ?? '')) ?: null;

            $primaryIsForeigner = (bool) ($primaryGuestData['is_foreigner'] ?? false);

            // If primary_guest_id is provided, update the existing customer
            if ($data['primary_guest_id']) {
                $primaryCustomer = Customer::find($data['primary_guest_id']);
                if ($primaryCustomer) {
                    $primaryCustomer->update([
                        'first_name' => $primaryFirstName,
                        'middle_name' => $primaryGuestData['middle_name'] ?? null,
                        'last_name' => $primaryLastName,
                        'age' => $primaryGuestData['age'] ?? null,
                        'gender' => $primaryGuestData['gender'] ?? 'Male',
                        'is_foreigner' => $primaryIsForeigner,
                        'phone' => $primaryPhone,
                        'email' => $primaryEmail,
                    ]);
                }
            } else {
                // Create new customer
                $primaryCustomer = Customer::firstOrCreate(
                    [
                        'first_name' => $primaryFirstName,
                        'last_name' => $primaryLastName,
                        'email' => $primaryEmail,
                        'phone' => $primaryPhone,
                    ],
                    [
                        'first_name' => $primaryFirstName,
                        'middle_name' => $primaryGuestData['middle_name'] ?? null,
                        'last_name' => $primaryLastName,
                        'age' => $primaryGuestData['age'] ?? null,
                        'gender' => $primaryGuestData['gender'] ?? 'Male',
                        'is_foreigner' => $primaryIsForeigner,
                        'phone' => $primaryPhone,
                        'email' => $primaryEmail,
                    ]
                );
            }

            if ($primaryCustomer) {
                ReservationGuest::create([
                    'reservation_id' => $reservation->id,
                    'customer_id' => $primaryCustomer->id,
                    'is_primary_guest' => true,
                ]);
            }
        }

        // Create companions
        foreach ($data['companions'] ?? [] as $companionData) {
            $companionFirstName = trim((string) ($companionData['first_name'] ?? '')) ?: 'Companion';
            $companionLastName = trim((string) ($companionData['last_name'] ?? '')) ?: 'Guest';
            $companionEmail = trim((string) ($companionData['email'] ?? '')) ?: null;
            $companionPhone = trim((string) ($companionData['phone'] ?? '')) ?: null;

            $companionIsForeigner = (bool) ($companionData['is_foreigner'] ?? false);

            // For bulk companions without email/phone, create a new customer each time
            if (empty($companionEmail) && empty($companionPhone)) {
                $companionCustomer = Customer::create([
                    'first_name' => $companionFirstName,
                    'middle_name' => $companionData['middle_name'] ?? null,
                    'last_name' => $companionLastName,
                    'age' => $companionData['age'] ?? null,
                    'gender' => $companionData['gender'] ?? 'Male',
                    'is_foreigner' => $companionIsForeigner,
                    'phone' => $companionPhone,
                    'email' => $companionEmail,
                ]);
            } else {
                // For companions with email/phone, use firstOrCreate to avoid duplicates
                $companionCustomer = Customer::firstOrCreate(
                    [
                        'first_name' => $companionFirstName,
                        'last_name' => $companionLastName,
                        'email' => $companionEmail,
                        'phone' => $companionPhone,
                    ],
                    [
                        'first_name' => $companionFirstName,
                        'middle_name' => $companionData['middle_name'] ?? null,
                        'last_name' => $companionLastName,
                        'age' => $companionData['age'] ?? null,
                        'gender' => $companionData['gender'] ?? 'Male',
                        'is_foreigner' => $companionIsForeigner,
                        'phone' => $companionPhone,
                        'email' => $companionEmail,
                    ]
                );
            }

            ReservationGuest::create([
                'reservation_id' => $reservation->id,
                'customer_id' => $companionCustomer->id,
                'is_primary_guest' => false,
            ]);
        }

        // Update reservation with check-in date and status
        $reservation->update([
            'check_in' => now()->toDateTimeString(),
            'status' => 'Checked In',
        ]);

        // Keep number_of_guests in sync with the actual guest list
        // (moved here from the check-ins GET page so reads never mutate).
        $actualGuestCount = $reservation->reservationGuests()->count();
        if ((int) $reservation->number_of_guests !== $actualGuestCount) {
            $reservation->update(['number_of_guests' => $actualGuestCount]);
        }

        return response()->json([
            'success' => true,
            'check_in' => $reservation->check_in,
            'status' => $reservation->status,
        ]);
    })->name('reservations.check-in');

    Route::post('/reservations/{reservation}/check-out', function (Request $request, Reservation $reservation) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only check out guests who haven't been checked out yet
        ReservationGuest::where('reservation_id', $reservation->id)
            ->whereNull('checked_out_at')
            ->update([
                'checked_out_at' => now(),
            ]);

        // Update reservation checkout date and status
        $reservation->update([
            'check_out' => now()->toDateTimeString(),
            'status' => 'Checked Out',
        ]);

        // Send receipt emails to all guests with email addresses
        $reservation->load(['reservationGuests.customer', 'reservationAmenities.amenity']);
        $checkInDateTime = $reservation->check_in ? $reservation->check_in->format('F j, Y g:i A') : now()->format('F j, Y g:i A');
        $checkOutDateTime = now()->format('F j, Y g:i A');

        // Calculate total cost from amenities
        $totalCost = 0;
        $amenities = [];
        foreach ($reservation->reservationAmenities as $reservationAmenity) {
            $amenityCost = $reservationAmenity->price_at_booking * $reservationAmenity->quantity;
            $totalCost += $amenityCost;
            $amenities[] = [
                'name' => $reservationAmenity->amenity?->amenities_name ?? 'Amenity',
                'price' => $amenityCost,
            ];
        }

        \Log::info('Reservation checkout - attempting to send receipts', [
            'reservation_id' => $reservation->id,
            'total_guests' => $reservation->reservationGuests->count(),
            'total_cost' => $totalCost,
        ]);

        // Send email to each guest who has an email
        foreach ($reservation->reservationGuests as $reservationGuest) {
            $customer = $reservationGuest->customer;
            \Log::info('Checking guest for email', [
                'reservation_guest_id' => $reservationGuest->id,
                'has_customer' => $customer ? true : false,
                'customer_email' => $customer?->email,
            ]);

            if ($customer && $customer->email) {
                try {
                    \Log::info('Sending checkout receipt to guest', [
                        'customer_email' => $customer->email,
                    ]);

                    Mail::mailer('smtp')->send(
                        new \App\Mail\CheckoutReceiptMail(
                            $customer,
                            $reservation,
                            $amenities,
                            $checkInDateTime,
                            $checkOutDateTime,
                            $totalCost
                        )
                    );

                    \Log::info('Checkout receipt sent successfully to guest', [
                        'customer_email' => $customer->email,
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Failed to send checkout receipt email: ' . $e->getMessage(), [
                        'customer_id' => $customer->id,
                        'reservation_id' => $reservation->id,
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                \Log::info('Skipping guest - no email', [
                    'reservation_guest_id' => $reservationGuest->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'check_out' => $reservation->check_out,
        ]);
    })->name('reservations.checkout');

    Route::post('/reservations/{reservation}/update', function (Request $request, Reservation $reservation) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'booker_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'reservation_date' => 'required|date',
            'number_of_guests' => 'required|integer|min:1',
            'status' => 'required|in:Pending,Confirmed,Checked In,Checked Out,Cancelled',
        ]);

        $reservation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reservation updated successfully.',
            'reservation' => $reservation,
        ]);
    })->name('reservations.update');

    Route::get('/reservations/refresh', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservations = Reservation::query()
            ->with(['reservationAmenities.amenity', 'reservationGuests.customer'])
            ->where('reservation_type', 'online')
            ->where(function ($query) {
                $query->whereNull('check_in')
                    ->orWhere('check_in', '');
            })
            ->where(function ($query) {
                $query->where('status', 'Pending')
                    ->orWhere('status', 'Confirmed');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $reservationData = $reservations->mapWithKeys(function ($reservation) {
            return [$reservation->id => [
                'id' => $reservation->id,
                'booker_name' => $reservation->booker_name,
                'phone' => $reservation->phone,
                'email' => $reservation->email,
                'reservation_date' => $reservation->reservation_date,
                'check_in' => $reservation->check_in,
                'number_of_guests' => $reservation->number_of_guests,
                'status' => $reservation->status,
                'reservation_type' => $reservation->reservation_type,
                'total_amount' => $reservation->total_amount,
                'amount_paid' => $reservation->amount_paid,
                'remaining_balance' => $reservation->remaining_balance,
                'payment_status' => $reservation->payment_status,
                'reservation_amenities' => $reservation->reservationAmenities->map(function ($reservationAmenity) {
                    return [
                        'pricing_type' => $reservationAmenity->pricing_type,
                        'price_at_booking' => $reservationAmenity->price_at_booking,
                        'quantity' => $reservationAmenity->quantity,
                        'remarks' => $reservationAmenity->remarks,
                        'amenity' => [
                            'amenities_name' => $reservationAmenity->amenity?->amenities_name,
                        ],
                    ];
                })->values(),
                'reservation_guests' => $reservation->reservationGuests->map(function ($guestEntry) {
                    $customer = $guestEntry->customer;
                    return [
                        'id' => $guestEntry->id,
                        'is_primary_guest' => $guestEntry->is_primary_guest,
                        'checked_out_at' => $guestEntry->checked_out_at,
                        'customer' => $customer ? [
                            'id' => $customer->id,
                            'first_name' => $customer->first_name,
                            'middle_name' => $customer->middle_name,
                            'last_name' => $customer->last_name,
                            'age' => $customer->age,
                            'gender' => $customer->gender,
                            'is_foreigner' => $customer->is_foreigner,
                            'phone' => $customer->phone,
                            'email' => $customer->email,
                        ] : null,
                    ];
                })->values(),
            ]];
        })->toArray();

        return response()->json([
            'reservations' => $reservationData,
        ]);
    })->name('reservations.refresh');

    Route::post('/reservation-guests/{reservationGuest}/check-out', function (Request $request, ReservationGuest $reservationGuest) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservationGuest->update([
            'checked_out_at' => now(),
        ]);

        // Check if all guests in the reservation are now checked out
        $reservation = $reservationGuest->reservation;
        $allGuestsCheckedOut = false;

        if ($reservation) {
            $allGuestsCheckedOut = ReservationGuest::where('reservation_id', $reservation->id)
                ->whereNotNull('checked_out_at')
                ->count() === $reservation->reservationGuests->count();

            if ($allGuestsCheckedOut) {
                $reservation->update([
                    'check_out' => now()->toDateTimeString(),
                    'status' => 'Checked Out',
                ]);
            }
        }

        // Send receipt email if customer has email
        $customer = $reservationGuest->customer;
        \Log::info('Checkout attempt', [
            'reservation_guest_id' => $reservationGuest->id,
            'has_customer' => $customer ? true : false,
            'customer_email' => $customer?->email,
            'reservation_id' => $reservation?->id,
        ]);

        if ($customer && $customer->email) {
            try {
                $checkInDateTime = $reservation && $reservation->check_in
                    ? $reservation->check_in->format('F j, Y g:i A')
                    : now()->format('F j, Y g:i A');

                $checkOutDateTime = now()->format('F j, Y g:i A');

                // Calculate total cost from amenities
                $totalCost = 0;
                $amenities = [];

                if ($reservation) {
                    $reservation->load('reservationAmenities.amenity');
                    foreach ($reservation->reservationAmenities as $reservationAmenity) {
                        $amenityCost = $reservationAmenity->price_at_booking * $reservationAmenity->quantity;
                        $totalCost += $amenityCost;
                        $amenities[] = [
                            'name' => $reservationAmenity->amenity?->amenities_name ?? 'Amenity',
                            'price' => $amenityCost,
                        ];
                    }
                }

                \Log::info('Sending checkout receipt', [
                    'customer_email' => $customer->email,
                    'total_cost' => $totalCost,
                ]);

                Mail::mailer('smtp')->send(
                    new \App\Mail\CheckoutReceiptMail(
                        $customer,
                        $reservation,
                        $amenities,
                        $checkInDateTime,
                        $checkOutDateTime,
                        $totalCost
                    )
                );

                \Log::info('Checkout receipt sent successfully', [
                    'customer_email' => $customer->email,
                ]);
            } catch (\Throwable $e) {
                \Log::error('Failed to send checkout receipt email: ' . $e->getMessage(), [
                    'customer_id' => $customer->id,
                    'reservation_guest_id' => $reservationGuest->id,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        } else {
            \Log::info('No email sent - customer has no email', [
                'reservation_guest_id' => $reservationGuest->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'checked_out_at' => $reservationGuest->checked_out_at,
        ]);
    })->name('reservation-guests.checkout');

    Route::get('/settings', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }
        return view('staff.staff_settings');
    })->name('settings');

    Route::post('/settings/update', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:staff_accounts,email,' . $user['id']],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $otp = random_int(100000, 999999);

        try {
            Mail::mailer('smtp')->send('emails.staff_settings_otp', [
                'otp' => $otp,
                'name' => $data['name'],
            ], function ($message) use ($data) {
                $message->from('parkhinaguan@gmail.com', 'Hinaguan Nature Park')
                    ->to($data['email'])
                    ->subject('Hinaguan Nature Park — Verify your profile change');
            });

            // Only store pending change after mail was sent successfully
            $request->session()->put('staff_profile_change', [
                'id' => $user['id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'] ?? null,
                'otp' => $otp,
            ]);

        } catch (\Throwable $e) {
            \Log::error('OTP email failed: ' . $e->getMessage(), ['exception' => $e]);
            // Ensure we do not leave a pending change in session when send fails
            $request->session()->forget('staff_profile_change');
            $request->session()->flash('error', 'Unable to send OTP email right now.');
            return redirect()->route('staff.settings');
        }

        return redirect()->route('staff.settings')->with('success', 'A verification code has been sent to your email.');
    })->name('settings.update');

    Route::post('/settings/verify', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $pending = $request->session()->get('staff_profile_change');
        $code = $request->validate(['code' => ['required', 'digits:6']])['code'];

        if (! $pending || (string) $pending['otp'] !== (string) $code) {
            return redirect()->route('staff.settings')->with('error', 'The verification code is invalid.');
        }

        $staffAccount = StaffAccount::findOrFail($pending['id']);
        $update = [
            'name' => $pending['name'],
            'email' => $pending['email'],
        ];

        if (! empty($pending['password'])) {
            $update['password'] = Hash::make($pending['password']);
        }

        $staffAccount->update($update);

        $request->session()->forget('staff_profile_change');
        $request->session()->put('auth_user', [
            'id' => $staffAccount->id,
            'name' => $staffAccount->name,
            'email' => $staffAccount->email,
            'role' => 'staff',
        ]);

        return redirect()->route('staff.settings')->with('success', 'Your account details were updated successfully.');
    })->name('settings.verify');
});
