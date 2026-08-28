<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\StaffChatbotController;
use App\Http\Controllers\GuestChatbotController;
use App\Http\Controllers\AdminChatbotController;
use App\Http\Controllers\HomeController;
use App\Mail\ReservationQrMail;
use App\Models\ActivityLog;
use App\Models\Amenity;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
use App\Services\WeatherService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// ── Continuous Stay & Booking slot helpers ─────────────────────────────────
$formatLocalDate = function ($val, ?string $column = null): ?string {
    if (! $val) return null;
    $raw = null;
    if ($column && is_object($val) && method_exists($val, 'getRawOriginal')) {
        $raw = $val->getRawOriginal($column);
    }
    if ($column && is_array($val)) {
        $raw = $val[$column] ?? null;
    }
    $raw = $raw ?? ($column && is_object($val) ? ($val->{$column} ?? null) : $val);
    if (! $raw) return null;
    if (is_string($raw)) {
        return substr(trim($raw), 0, 10);
    }
    if ($raw instanceof \DateTimeInterface) {
        return $raw->setTimezone(new \DateTimeZone(config('app.timezone', 'Asia/Manila')))->format('Y-m-d');
    }
    return \Illuminate\Support\Carbon::parse($raw)->format('Y-m-d');
};

// Returns an array of [$dateString, $slotType] pairs for a continuous stay.
// $slotType is either 'Daytime' or 'Nighttime'.
$continuousSlotTimeline = function (string $startDate, ?string $endDate = null, string $startSlot = 'Daytime', string $endSlot = 'Daytime'): array {
    $start = \Illuminate\Support\Carbon::parse($startDate)->startOfDay();
    $end = $endDate ? \Illuminate\Support\Carbon::parse($endDate)->startOfDay() : $start->copy();
    
    // Normalize slots (clean out Aircon if present)
    if (str_contains($startSlot, 'NightToDay')) {
        $cleanStartSlot = 'Nighttime';
    } elseif (str_contains($startSlot, 'DayToNight') || str_contains($startSlot, 'Daytime') || str_starts_with($startSlot, 'Day')) {
        $cleanStartSlot = 'Daytime';
    } else {
        $cleanStartSlot = 'Nighttime';
    }

    if (str_contains($endSlot, 'NightToDay')) {
        $cleanEndSlot = 'Daytime';
    } elseif (str_contains($endSlot, 'DayToNight') || str_contains($endSlot, 'Nighttime') || str_ends_with($endSlot, 'Night')) {
        $cleanEndSlot = 'Nighttime';
    } else {
        $cleanEndSlot = 'Daytime';
    }

    if ($start->gt($end)) {
        $end = $start->copy();
    }

    $daysDiff = (int) round($start->diffInDays($end));
    $pairs = [];

    if ($daysDiff === 0) {
        if ($cleanStartSlot === 'Daytime' && $cleanEndSlot === 'Daytime') {
            $pairs[] = [$start->toDateString(), 'Daytime'];
        } elseif ($cleanStartSlot === 'Nighttime' && $cleanEndSlot === 'Nighttime') {
            $pairs[] = [$start->toDateString(), 'Nighttime'];
        } elseif ($cleanStartSlot === 'Daytime' && $cleanEndSlot === 'Nighttime') {
            $pairs[] = [$start->toDateString(), 'Daytime'];
            $pairs[] = [$start->toDateString(), 'Nighttime'];
        } else {
            // Nighttime to Daytime next day
            $pairs[] = [$start->toDateString(), 'Nighttime'];
            $pairs[] = [$start->copy()->addDay()->toDateString(), 'Daytime'];
        }
        return $pairs;
    }

    for ($i = 0; $i <= $daysDiff; $i++) {
        $currentDate = $start->copy()->addDays($i)->toDateString();
        
        if ($i === 0) {
            if ($cleanStartSlot === 'Daytime') {
                $pairs[] = [$currentDate, 'Daytime'];
                $pairs[] = [$currentDate, 'Nighttime'];
            } else {
                $pairs[] = [$currentDate, 'Nighttime'];
            }
        } elseif ($i === $daysDiff) {
            if ($cleanEndSlot === 'Daytime') {
                $pairs[] = [$currentDate, 'Daytime'];
            } else {
                $pairs[] = [$currentDate, 'Daytime'];
                $pairs[] = [$currentDate, 'Nighttime'];
            }
        } else {
            $pairs[] = [$currentDate, 'Daytime'];
            $pairs[] = [$currentDate, 'Nighttime'];
        }
    }

    return $pairs;
};

// Returns day count, night count, and total days span for a continuous booking
$calculateContinuousSlotsCount = function (string $startDate, ?string $endDate = null, string $startSlot = 'Daytime', string $endSlot = 'Daytime') use ($continuousSlotTimeline): array {
    $timeline = $continuousSlotTimeline($startDate, $endDate, $startSlot, $endSlot);
    $dayCount = 0;
    $nightCount = 0;
    foreach ($timeline as [$d, $s]) {
        if ($s === 'Daytime') {
            $dayCount++;
        } else {
            $nightCount++;
        }
    }
    $start = \Illuminate\Support\Carbon::parse($startDate)->startOfDay();
    $end = $endDate ? \Illuminate\Support\Carbon::parse($endDate)->startOfDay() : $start->copy();
    return [
        'day_count' => $dayCount,
        'night_count' => $nightCount,
        'total_slots' => count($timeline),
        'days_span' => max(1, $start->diffInDays($end) + 1),
    ];
};

// Returns timeline for an existing ReservationAmenity / Reservation model
$getReservationAmenityTimeline = function ($ra, $res = null) use ($continuousSlotTimeline, $formatLocalDate): array {
    $startDate = $formatLocalDate($ra, 'start_date') ?: $formatLocalDate($res, 'reservation_date');

    if (! $startDate) {
        return [];
    }

    $endDate = $formatLocalDate($ra, 'end_date') ?: ($formatLocalDate($res, 'end_date') ?: $startDate);

    $pricingType = (is_object($ra) ? ($ra->pricing_type ?? null) : ($ra['pricing_type'] ?? null))
        ?? ($res?->entranceFee?->pricing_type ?? 'Daytime');
    $baseType = rtrim(str_replace([' Aircon', 'Aircon'], '', $pricingType));

    $raStartSlot = is_object($ra) ? ($ra->getRawOriginal('start_slot') ?? $ra->start_slot) : ($ra['start_slot'] ?? null);
    $resStartSlot = $res ? ($res->getRawOriginal('start_slot') ?? $res->start_slot) : null;
    $startSlot = $raStartSlot ?: ($resStartSlot ?: ($baseType ?: 'Daytime'));

    $raEndSlot = is_object($ra) ? ($ra->getRawOriginal('end_slot') ?? $ra->end_slot) : ($ra['end_slot'] ?? null);
    $resEndSlot = $res ? ($res->getRawOriginal('end_slot') ?? $res->end_slot) : null;
    $endSlot = $raEndSlot ?: ($resEndSlot ?: ($raStartSlot ?: ($resStartSlot ?: ($baseType ?: 'Daytime'))));

    // Early check-in: guest arrived before the scheduled start — occupy the amenity
    // from actual check-in until the original scheduled checkout (end_date/end_slot).
    if ($res) {
        $resStatus = strtolower(trim((string) ($res->status ?? '')));
        $isCheckedIn = in_array($resStatus, ['checked in', 'checked-in', 'checked_in', 'active'], true);

        if ($isCheckedIn && $res->check_in) {
            $checkInCarbon = \Illuminate\Support\Carbon::parse($res->check_in);
            $checkInDate = $checkInCarbon->toDateString();

            if ($checkInDate < $startDate) {
                $settings = \App\Models\ParkSetting::first();
                $daytimeEnd = $settings->daytime_end ?? '18:00';
                $checkInTime = $checkInCarbon->format('H:i');
                $startDate = $checkInDate;
                $startSlot = ($checkInTime < $daytimeEnd) ? 'Daytime' : 'Nighttime';
            }
        }
    }

    $hasExplicitMultiDay = ($ra && (is_object($ra) ? $ra->start_date : ($ra['start_date'] ?? null)))
        || ($res && $res->end_date)
        || ($startDate !== $endDate);

    if ($hasExplicitMultiDay) {
        return $continuousSlotTimeline($startDate, $endDate, $startSlot, $endSlot);
    }

    return match ($baseType) {
        'Daytime' => [[$startDate, 'Daytime']],
        'Nighttime' => [[$startDate, 'Nighttime']],
        'DayToNight' => [[$startDate, 'Daytime'], [$startDate, 'Nighttime']],
        'NightToDay' => [[$startDate, 'Nighttime'], [\Illuminate\Support\Carbon::parse($startDate)->addDay()->toDateString(), 'Daytime']],
        default => [[$startDate, 'Daytime']],
    };
};

// Returns true when an amenity is already booked across any portion of a continuous range
$isAmenityRangeTaken = function (string $amenityId, string $startDate, ?string $endDate = null, string $startSlot = 'Daytime', string $endSlot = 'Daytime', ?int $excludeReservationId = null) use ($continuousSlotTimeline, $getReservationAmenityTimeline): bool {
    $requestedTimeline = $continuousSlotTimeline($startDate, $endDate, $startSlot, $endSlot);
    if (empty($requestedTimeline)) {
        return false;
    }

    $dates = array_unique(array_column($requestedTimeline, 0));
    $minDate = min($dates);
    $maxDate = max($dates);

    $activeAmenities = ReservationAmenity::query()
        ->where('amenity_id', $amenityId)
        ->where(function ($q) {
            $q->whereNull('status')
              ->orWhere('status', '!=', 'Completed');
        })
        ->whereHas('reservation', function ($rq) use ($excludeReservationId) {
            $rq->whereNotIn('status', ['Cancelled', 'Checked Out', 'cancelled', 'checked out', 'checked_out', 'checked-out'])
               ->when($excludeReservationId !== null, fn ($q) => $q->whereKeyNot($excludeReservationId));
        })
        ->where(function ($q) use ($minDate, $maxDate) {
            // Amenity's own start_date / end_date overlap
            $q->where(function ($aq) use ($minDate, $maxDate) {
                $aq->whereNotNull('start_date')
                   ->whereDate('start_date', '<=', $maxDate)
                   ->where(function ($sub) use ($minDate) {
                       $sub->whereDate('end_date', '>=', $minDate)
                           ->orWhere(function ($sub2) use ($minDate) {
                               $sub2->whereNull('end_date')
                                    ->whereDate('start_date', '>=', \Illuminate\Support\Carbon::parse($minDate)->subDays(2)->toDateString());
                           });
                   });
            })
            // OR parent reservation's reservation_date / end_date overlap
            ->orWhereHas('reservation', function ($rq) use ($minDate, $maxDate) {
                $rq->whereDate('reservation_date', '<=', $maxDate)
                   ->where(function ($sub) use ($minDate) {
                       $sub->whereDate('end_date', '>=', $minDate)
                           ->orWhere(function ($sub2) use ($minDate) {
                               $sub2->whereNull('end_date')
                                    ->whereDate('reservation_date', '>=', \Illuminate\Support\Carbon::parse($minDate)->subDays(2)->toDateString());
                           });
                   });
            })
            // OR parent reservation is currently Checked In (active on site)
            ->orWhereHas('reservation', function ($rq) {
                $rq->whereIn('status', ['Checked In', 'checked in', 'checked_in', 'Active', 'active']);
            });
        })
        ->with('reservation')
        ->get();

    $reqMap = [];
    foreach ($requestedTimeline as [$d, $s]) {
        $reqMap["{$d}_{$s}"] = true;
    }

    foreach ($activeAmenities as $ra) {
        $res = $ra->reservation;
        if (! $res) continue;

        $resStatus = strtolower(trim((string) $res->status));
        if (in_array($resStatus, ['cancelled', 'checked out', 'checkedout', 'checked-out'], true)) {
            continue;
        }

        if ($excludeReservationId !== null && (int) $res->id === (int) $excludeReservationId) {
            continue;
        }

        $existingTimeline = $getReservationAmenityTimeline($ra, $res);
        foreach ($existingTimeline as [$d, $s]) {
            if (isset($reqMap["{$d}_{$s}"])) {
                return true;
            }
        }
    }

    return false;
};

// Backward-compatible single-slot conflict helper
$isAmenitySlotTaken = function (string $amenityId, string $date, string $slot, ?int $excludeReservationId = null) use ($isAmenityRangeTaken): bool {
    $baseSlot = rtrim(str_replace([' Aircon', 'Aircon'], '', $slot));
    return match ($baseSlot) {
        'Daytime' => $isAmenityRangeTaken($amenityId, $date, $date, 'Daytime', 'Daytime', $excludeReservationId),
        'Nighttime' => $isAmenityRangeTaken($amenityId, $date, $date, 'Nighttime', 'Nighttime', $excludeReservationId),
        'DayToNight' => $isAmenityRangeTaken($amenityId, $date, $date, 'Daytime', 'Nighttime', $excludeReservationId),
        'NightToDay' => $isAmenityRangeTaken($amenityId, $date, \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString(), 'Nighttime', 'Daytime', $excludeReservationId),
        default => false,
    };
};

// Returns the checkout datetime (Carbon) for a continuous stay amenity or reservation
$amenityContinuousCheckoutAt = function (?string $endDate, string $endSlot = 'Daytime'): ?\Illuminate\Support\Carbon {
    if (! $endDate) {
        return null;
    }

    $settings = \App\Models\ParkSetting::first();
    $dayEnd = \Illuminate\Support\Carbon::parse($settings->daytime_end ?? '18:00');
    $nightEnd = \Illuminate\Support\Carbon::parse($settings->nighttime_end ?? '06:00');

    $base = \Illuminate\Support\Carbon::parse($endDate);
    $cleanEndSlot = str_contains($endSlot, 'Night') ? 'Nighttime' : 'Daytime';

    if ($cleanEndSlot === 'Nighttime') {
        return $base->copy()->addDay()->setTime($nightEnd->hour, $nightEnd->minute);
    }

    return $base->copy()->setTime($dayEnd->hour, $dayEnd->minute);
};

// Returns checkout datetime for a single legacy slot
$amenityCheckoutAt = function (?string $date, ?string $slot): ?\Illuminate\Support\Carbon {
    if (! $date || ! $slot) {
        return null;
    }

    $settings = \App\Models\ParkSetting::first();
    $dayEnd = \Illuminate\Support\Carbon::parse($settings->daytime_end ?? '18:00');
    $nightEnd = \Illuminate\Support\Carbon::parse($settings->nighttime_end ?? '06:00');

    $base = \Illuminate\Support\Carbon::parse($date);
    $baseSlot = str_replace([' Aircon', 'Aircon'], '', $slot);

    return match (true) {
        str_contains($baseSlot, 'DayToNight') => $base->copy()->addDay()->setTime($nightEnd->hour, $nightEnd->minute),
        str_contains($baseSlot, 'NightToDay') => $base->copy()->addDay()->setTime($dayEnd->hour, $dayEnd->minute),
        str_contains($baseSlot, 'Daytime') => $base->copy()->setTime($dayEnd->hour, $dayEnd->minute),
        str_contains($baseSlot, 'Nighttime') => $base->copy()->addDay()->setTime($nightEnd->hour, $nightEnd->minute),
        default => null,
    };
};

// Returns the checkout Carbon datetime for a reservation based ONLY on its
// master stay schedule (reservation_date / end_date / end_slot / total_days).
// Amenity rows are intentionally ignored: amenities are boundary-enforced to
// never exceed the master schedule, so the reservation's own checkout date is
// the single source of truth for checkout reminders and counters.
$computeReservationCheckoutAt = function ($reservation) use ($amenityCheckoutAt, $amenityContinuousCheckoutAt): ?\Illuminate\Support\Carbon {
    if (! $reservation) {
        return null;
    }

    $startDate = $reservation->reservation_date;
    if (! $startDate) {
        return null;
    }

    $totalDays = max(1, (int) ($reservation->total_days ?? 1));
    $endDate = $reservation->end_date ?: ($totalDays > 1 ? \Illuminate\Support\Carbon::parse($startDate)->addDays($totalDays - 1)->toDateString() : $startDate);
    $endSlot = $reservation->end_slot ?: ($reservation->entranceFee?->pricing_type ?: $reservation->start_slot ?: 'Daytime');

    if ($reservation->end_date || $totalDays > 1) {
        return $amenityContinuousCheckoutAt($endDate, $endSlot);
    }

    return $amenityCheckoutAt($startDate, $endSlot);
};

$reservationCheckoutAt = function (?string $date, array $slots, ?string $endDate = null, ?string $endSlot = null) use ($amenityCheckoutAt, $amenityContinuousCheckoutAt): ?\Illuminate\Support\Carbon {
    if (! $date) {
        return null;
    }

    if ($endDate) {
        return $amenityContinuousCheckoutAt($endDate, $endSlot ?: 'Daytime');
    }

    $latest = null;
    foreach ($slots as $slot) {
        $end = $amenityCheckoutAt($date, $slot);
        if ($end && (! $latest || $end->gt($latest))) {
            $latest = $end;
        }
    }

    return $latest;
};

// Returns list of occupied amenity IDs across a continuous timeline
$occupiedAmenityIdsForContinuousRange = function (string $startDate, ?string $endDate = null, string $startSlot = 'Daytime', string $endSlot = 'Daytime') use ($continuousSlotTimeline, $getReservationAmenityTimeline): array {
    $requestedTimeline = $continuousSlotTimeline($startDate, $endDate, $startSlot, $endSlot);
    if (empty($requestedTimeline)) {
        return [];
    }

    $dates = array_unique(array_column($requestedTimeline, 0));
    $minDate = min($dates);
    $maxDate = max($dates);

    $activeAmenities = ReservationAmenity::query()
        ->where(function ($q) {
            $q->whereNull('status')
              ->orWhere('status', '!=', 'Completed');
        })
        ->whereHas('reservation', function ($rq) {
            $rq->whereNotIn('status', ['Cancelled', 'Checked Out', 'cancelled', 'checked out', 'checked_out', 'checked-out']);
        })
        ->where(function ($q) use ($minDate, $maxDate) {
            // Amenity's own start_date / end_date overlap
            $q->where(function ($aq) use ($minDate, $maxDate) {
                $aq->whereNotNull('start_date')
                   ->whereDate('start_date', '<=', $maxDate)
                   ->where(function ($sub) use ($minDate) {
                       $sub->whereDate('end_date', '>=', $minDate)
                           ->orWhere(function ($sub2) use ($minDate) {
                               $sub2->whereNull('end_date')
                                    ->whereDate('start_date', '>=', \Illuminate\Support\Carbon::parse($minDate)->subDays(2)->toDateString());
                           });
                   });
            })
            // OR parent reservation's reservation_date / end_date overlap
            ->orWhereHas('reservation', function ($rq) use ($minDate, $maxDate) {
                $rq->whereDate('reservation_date', '<=', $maxDate)
                   ->where(function ($sub) use ($minDate) {
                       $sub->whereDate('end_date', '>=', $minDate)
                           ->orWhere(function ($sub2) use ($minDate) {
                               $sub2->whereNull('end_date')
                                    ->whereDate('reservation_date', '>=', \Illuminate\Support\Carbon::parse($minDate)->subDays(2)->toDateString());
                           });
                   });
            })
            // OR parent reservation is currently Checked In (active on site)
            ->orWhereHas('reservation', function ($rq) {
                $rq->whereIn('status', ['Checked In', 'checked in', 'checked_in', 'Active', 'active']);
            });
        })
        ->with('reservation')
        ->get();

    $reqMap = [];
    foreach ($requestedTimeline as [$d, $s]) {
        $reqMap["{$d}_{$s}"] = true;
    }

    $occupied = [];
    foreach ($activeAmenities as $ra) {
        if (! $ra->amenity_id) continue;
        $res = $ra->reservation;
        if (! $res) continue;

        $resStatus = strtolower(trim((string) $res->status));
        if (in_array($resStatus, ['cancelled', 'checked out', 'checkedout', 'checked-out'], true)) {
            continue;
        }

        $existingTimeline = $getReservationAmenityTimeline($ra, $res);
        foreach ($existingTimeline as [$d, $s]) {
            if (isset($reqMap["{$d}_{$s}"])) {
                $occupied[] = (string) $ra->amenity_id;
                break;
            }
        }
    }

    return array_values(array_unique($occupied));
};

$occupiedAmenityIdsForSlot = function (string $date, string $slot) use ($occupiedAmenityIdsForContinuousRange): array {
    $baseSlot = rtrim(str_replace([' Aircon', 'Aircon'], '', $slot));
    return match ($baseSlot) {
        'Daytime' => $occupiedAmenityIdsForContinuousRange($date, $date, 'Daytime', 'Daytime'),
        'Nighttime' => $occupiedAmenityIdsForContinuousRange($date, $date, 'Nighttime', 'Nighttime'),
        'DayToNight' => $occupiedAmenityIdsForContinuousRange($date, $date, 'Daytime', 'Nighttime'),
        'NightToDay' => $occupiedAmenityIdsForContinuousRange($date, \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString(), 'Nighttime', 'Daytime'),
        default => [],
    };
};

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('/chatbot', [StaffChatbotController::class, 'chat'])->name('chatbot.chat');
Route::get('/chatbot/history', [StaffChatbotController::class, 'history'])->name('chatbot.history');
Route::post('/chatbot/clear', [StaffChatbotController::class, 'clear'])->name('chatbot.clear');
Route::get('/chatbot/proactive', [StaffChatbotController::class, 'proactiveMessage'])->name('chatbot.proactive');

Route::post('/admin-chatbot', [AdminChatbotController::class, 'chat'])->name('admin.chatbot.chat');
Route::get('/admin-chatbot/history', [AdminChatbotController::class, 'history'])->name('admin.chatbot.history');
Route::post('/admin-chatbot/clear', [AdminChatbotController::class, 'clear'])->name('admin.chatbot.clear');
Route::get('/admin-chatbot/proactive', [AdminChatbotController::class, 'proactiveMessage'])->name('admin.chatbot.proactive');

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
        'park_status' => $settings->park_status ?? 'open',
        'close_description' => $settings->close_description ?? null,
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

Route::get('/api/amenities/availability', function (Request $request) use ($occupiedAmenityIdsForContinuousRange) {
    $startDate = $request->query('start_date', now()->toDateString());
    $endDate = $request->query('end_date', $startDate);
    $startSlot = $request->query('start_slot', 'Daytime');
    $endSlot = $request->query('end_slot', $startSlot);

    $occupiedIds = $occupiedAmenityIdsForContinuousRange($startDate, $endDate, $startSlot, $endSlot);
    $occupiedIdsStr = array_map('strval', $occupiedIds);

    $allAmenities = Amenity::where('status', true)
        ->orderBy('amenities_name')
        ->get();

    return response()->json([
        'occupied_ids' => $occupiedIds,
        'amenities' => $allAmenities->map(function ($amenity) use ($occupiedIdsStr) {
            return [
                'id' => $amenity->id,
                'amenities_name' => $amenity->amenities_name,
                'description' => $amenity->description,
                'daytime_price' => (float) ($amenity->daytime_price ?? 0),
                'nighttime_price' => (float) ($amenity->nighttime_price ?? 0),
                'daytime_aircon_price' => $amenity->daytime_aircon_price !== null ? (float) $amenity->daytime_aircon_price : null,
                'nighttime_aircon_price' => $amenity->nighttime_aircon_price !== null ? (float) $amenity->nighttime_aircon_price : null,
                'minimum_capacity' => $amenity->minimum_capacity,
                'maximum_capacity' => $amenity->maximum_capacity,
                'is_available' => ! in_array((string) $amenity->id, $occupiedIdsStr, true),
            ];
        }),
    ]);
})->name('api.amenities.availability');

Route::get('/amenities', function () use ($getReservationAmenityTimeline) {
    $amenities = Amenity::where('status', true)
        ->orderBy('amenities_name')
        ->get();

    $today = now()->toDateString();

    // Fetch reservations relevant to today's occupancy
    // Include: Checked In (active stays on site)
    // Include: Pending or Confirmed (reserved stays that overlap with today)
    // Exclude: Cancelled, Checked Out
    $reservations = \App\Models\Reservation::query()
        ->whereNotIn('status', ['Cancelled', 'Checked Out', 'cancelled', 'checked out', 'checked_out', 'checked-out'])
        ->where(function ($query) use ($today) {
            $query->whereIn('status', ['Checked In', 'checked in', 'checked_in', 'Active', 'active'])
                  ->orWhere(function ($q) use ($today) {
                      $q->whereIn('status', ['Pending', 'Confirmed', 'pending', 'confirmed'])
                        ->where(function ($dateQ) use ($today) {
                            $dateQ->where(function ($dSub) use ($today) {
                                $dSub->whereDate('reservation_date', '<=', $today)
                                     ->where(function ($endQ) use ($today) {
                                         $endQ->whereNull('end_date')
                                              ->whereDate('reservation_date', '>=', \Illuminate\Support\Carbon::parse($today)->subDays(2)->toDateString())
                                              ->orWhereDate('end_date', '>=', $today);
                                     });
                            })
                            ->orWhereHas('reservationAmenities', function ($raQ) use ($today) {
                                $raQ->where(function ($sq) {
                                    $sq->whereNull('status')
                                       ->orWhere('status', '!=', 'Completed');
                                })
                                ->whereNotNull('start_date')
                                ->whereDate('start_date', '<=', $today)
                                ->where(function ($sub) use ($today) {
                                    $sub->whereNull('end_date')
                                        ->whereDate('start_date', '>=', \Illuminate\Support\Carbon::parse($today)->subDays(2)->toDateString())
                                        ->orWhereDate('end_date', '>=', $today);
                                });
                            });
                        });
                  });
        })
        ->whereNotIn('status', ['Cancelled', 'Checked Out'])
        ->with(['reservationAmenities' => function ($query) {
            $query->with('amenity');
        }, 'reservationGuests'])
        ->get();

    // Build occupancy data for each amenity
    $occupancyData = [];
    foreach ($amenities as $amenity) {
        $occupancyData[$amenity->id] = [
            'occupied' => [],
            'reserved' => [],
        ];

        foreach ($reservations as $reservation) {
            $uniqueAmenitiesCount = $reservation->reservationAmenities->pluck('amenity_id')->unique()->count();
            $isSharedGroup = $uniqueAmenitiesCount > 1;

            foreach ($reservation->reservationAmenities as $ra) {
                if ($ra->status === 'Completed') continue;
                if ($ra->amenity_id === $amenity->id) {
                    $timeline = $getReservationAmenityTimeline($ra, $reservation);

                    // Filter timeline for TODAY's slots only
                    $todaySlots = [];
                    foreach ($timeline as [$d, $s]) {
                        if ($d === $today) {
                            $todaySlots[] = $s;
                        }
                    }

                    if (empty($todaySlots)) {
                        continue;
                    }

                    $hasDay = in_array('Daytime', $todaySlots);
                    $hasNight = in_array('Nighttime', $todaySlots);

                    if ($hasDay && $hasNight) {
                        $timeSlot = 'DayToNight';
                        $timeSlotLabel = 'Day & Night';
                    } elseif ($hasDay) {
                        $timeSlot = 'Daytime';
                        $timeSlotLabel = 'Daytime';
                    } else {
                        $timeSlot = 'Nighttime';
                        $timeSlotLabel = 'Nighttime';
                    }

                    if (str_contains((string) $ra->pricing_type, 'Continuous Stay')) {
                        $timeSlotLabel = "Continuous Stay ({$timeSlotLabel})";
                    }

                    $entry = [
                        'reservation_id' => $reservation->id,
                        'time_slot' => $timeSlot,
                        'time_slot_label' => $timeSlotLabel,
                        'today_slots' => array_map('strtolower', $todaySlots),
                        'status' => $reservation->status,
                        // Headcount of guests (main + companions) still inside
                        'guest_count' => $reservation->reservationGuests->whereNull('checked_out_at')->count(),
                        'is_shared_group' => $isSharedGroup,
                        'total_amenities_count' => $uniqueAmenitiesCount,
                    ];

                    if ($reservation->status === 'Checked In') {
                        $occupancyData[$amenity->id]['occupied'][] = $entry;
                    } elseif (in_array($reservation->status, ['Pending', 'Confirmed'])) {
                        $occupancyData[$amenity->id]['reserved'][] = $entry;
                    }
                }
            }
        }
    }

    // Aggregate occupancy stats for the KPI strip
    $occupiedCount = 0;
    $reservedCount = 0;
    $availableCount = 0;
    $occupiedReservations = 0;
    foreach ($occupancyData as $data) {
        if (! empty($data['occupied'])) {
            $occupiedCount++;
            $occupiedReservations += count($data['occupied']);
        }
        if (! empty($data['reserved'])) {
            $reservedCount++;
        }
        if (empty($data['occupied']) && empty($data['reserved'])) {
            $availableCount++;
        }
    }
    $totalAmenities = $amenities->count();
    $inUseCount = $occupiedCount + $reservedCount;
    $occupancyRate = $totalAmenities > 0 ? (int) round($inUseCount / $totalAmenities * 100) : 0;

    // Calculate guest demographic counts for active checked-in guests
    $checkedInGuests = \App\Models\ReservationGuest::whereNull('checked_out_at')
        ->whereHas('reservation', fn($q) => $q->where('status', 'Checked In'))
        ->with(['customer', 'reservation.entranceFee'])
        ->get();

    $totalGuestsInside = $checkedInGuests->count();

    if ($totalGuestsInside === 0) {
        $checkedInReservations = $reservations->where('status', 'Checked In');
        $totalGuestsInside = (int) $checkedInReservations->sum('number_of_guests');
        $adultCount = (int) $checkedInReservations->sum(fn($r) => $r->entranceFee?->adult_count ?? (int) round($r->number_of_guests * 0.7));
        $childCount = (int) $checkedInReservations->sum(fn($r) => $r->entranceFee?->child_count ?? (int) round($r->number_of_guests * 0.3));
        $femaleCount = (int) round($totalGuestsInside * 0.5);
        $maleCount = (int) ($totalGuestsInside - $femaleCount);
    } else {
        $femaleCount = $checkedInGuests->filter(function($g) {
            $gender = strtolower($g->customer?->gender ?? '');
            return in_array($gender, ['female', 'f', 'woman', 'girl']);
        })->count();

        $maleCount = $checkedInGuests->filter(function($g) {
            $gender = strtolower($g->customer?->gender ?? '');
            return in_array($gender, ['male', 'm', 'man', 'boy']);
        })->count();

        $adultCount = $checkedInGuests->filter(fn($g) => ($g->customer?->age ?? 18) >= 18)->count();
        $childCount = $checkedInGuests->filter(fn($g) => ($g->customer?->age ?? 18) < 18)->count();

        // If gender wasn't explicitly populated on customer profiles, distribute logically
        if ($femaleCount === 0 && $maleCount === 0 && $totalGuestsInside > 0) {
            $femaleCount = (int) round($totalGuestsInside * 0.5);
            $maleCount = (int) ($totalGuestsInside - $femaleCount);
        }
    }

    // Visitors: checked-in guests (main + companions, still inside) whose reservation availed no amenity at all
    $visitorCount = $reservations
        ->filter(fn ($res) => $res->reservationAmenities->isEmpty())
        ->sum(fn ($res) => $res->reservationGuests->whereNull('checked_out_at')->count());

    $parkSettings = \App\Models\ParkSetting::first();

    return view('amenities', compact(
        'amenities',
        'occupancyData',
        'totalAmenities',
        'occupiedCount',
        'reservedCount',
        'availableCount',
        'occupiedReservations',
        'inUseCount',
        'occupancyRate',
        'visitorCount',
        'totalGuestsInside',
        'femaleCount',
        'maleCount',
        'adultCount',
        'childCount',
        'parkSettings'
    ));
})->name('amenities');

Route::get('/feedback', function () {
    $parkSettings = \App\Models\ParkSetting::first();
    $feedbacks = Feedback::visible()->topRated()->get();

    return view('feedback', compact('parkSettings', 'feedbacks'));
})->name('feedback');

Route::post('/feedback', function (Request $request) {
    $isAnonymous = filter_var($request->input('is_anonymous'), FILTER_VALIDATE_BOOLEAN);

    $validated = $request->validate([
        'full_name' => [$isAnonymous ? 'nullable' : 'required', 'string', 'max:255'],
        'description' => ['required', 'string', 'max:2000'],
        'stars' => ['required', 'integer', 'min:1', 'max:5'],
    ]);

    $fullName = $isAnonymous
        ? Feedback::ANONYMOUS_NAME
        : trim($validated['full_name'] ?? '');

    $feedback = Feedback::create([
        'full_name' => $fullName,
        'is_anonymous' => $isAnonymous,
        'description' => $validated['description'],
        'stars' => (int) $validated['stars'],
        'is_shown' => true,
    ]);

    if ($request->expectsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Thank you for your feedback!',
            'feedback' => [
                'id' => $feedback->id,
                'full_name' => $feedback->display_name,
                'initials' => $feedback->initials,
                'description' => $feedback->description,
                'stars' => $feedback->stars,
                'created_at' => $feedback->created_at->format('M j, Y'),
            ],
        ]);
    }

    return redirect()->route('feedback')->with('success', 'Thank you for your feedback!');
})->name('feedback.store');




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
        'date' => $forecast['date'] ?? null,
        'condition' => $forecast['condition'] ?? null,
        'icon' => $forecast['icon'] ?? null,
        'max_temp_c' => $forecast['max_temp_c'] ?? null,
        'min_temp_c' => $forecast['min_temp_c'] ?? null,
        'chance_of_rain' => $forecast['chance_of_rain'] ?? null,
    ]);
})->name('reservation.weather-preview');

Route::get('/reservation/availability', function (Request $request) use ($occupiedAmenityIdsForContinuousRange) {
    $startDate = $request->query('start_date') ?: $request->query('date');
    $endDate = $request->query('end_date') ?: $startDate;
    $startSlot = $request->query('start_slot') ?: $request->query('slot', 'Daytime');
    $endSlot = $request->query('end_slot') ?: $startSlot;

    if (! $startDate) {
        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_slot' => $startSlot,
            'end_slot' => $endSlot,
            'occupied_amenity_ids' => [],
        ]);
    }

    $today = now()->toDateString();
    $settings = \App\Models\ParkSetting::first();
    $daytimeStart = $settings ? strtotime((string) ($settings->daytime_start ?? '06:00')) : strtotime('06:00');
    $daytimeEnd = $settings ? strtotime((string) ($settings->daytime_end ?? '18:00')) : strtotime('18:00');
    $nowSeconds = strtotime(now()->format('H:i'));
    $isTodayNighttime = !($nowSeconds >= $daytimeStart && $nowSeconds < $daytimeEnd);

    if ($startDate === $today && $isTodayNighttime && in_array($startSlot, ['Daytime', 'DayToNight'], true)) {
        $allAmenityIds = Amenity::where('status', true)->pluck('id')->all();
        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_slot' => $startSlot,
            'end_slot' => $endSlot,
            'date' => $startDate,
            'slot' => $startSlot,
            'occupied_amenity_ids' => $allAmenityIds,
            'slot_passed' => true,
        ]);
    }

    $occupiedAmenityIds = $occupiedAmenityIdsForContinuousRange($startDate, $endDate, $startSlot, $endSlot);

    return response()->json([
        'start_date' => $startDate,
        'end_date' => $endDate,
        'start_slot' => $startSlot,
        'end_slot' => $endSlot,
        'date' => $startDate,
        'slot' => $startSlot,
        'occupied_amenity_ids' => $occupiedAmenityIds,
    ]);
})->name('reservation.availability');

Route::get('/reservation/availability/calendar', function (Request $request) use ($isAmenityRangeTaken) {
    $amenityId = $request->query('amenity_id');
    $amenityIdsRaw = $request->query('amenity_ids');
    $slot = $request->query('slot', 'Daytime');
    $month = $request->query('month');
    $year = $request->query('year');

    $amenityIds = [];
    if (!empty($amenityIdsRaw)) {
        $amenityIds = is_array($amenityIdsRaw) ? $amenityIdsRaw : explode(',', (string) $amenityIdsRaw);
    } elseif ($amenityId) {
        $amenityIds = [$amenityId];
    }

    $amenityIds = array_values(array_filter(array_map('trim', $amenityIds)));

    if (empty($amenityIds)) {
        return response()->json([
            'amenity_id' => $amenityId,
            'amenity_ids' => [],
            'slot' => $slot,
            'availability' => [],
        ]);
    }

    // Use today's date in the application's timezone to avoid offset issues
    if ($month !== null && $year !== null) {
        // If month and year are provided, start from the first day of that month
        $startDate = \Carbon\Carbon::createFromDate($year, $month + 1, 1)->startOfDay();
        $numDays = $startDate->daysInMonth;
    } else {
        // Default to today and show 30 days
        $startDate = \Carbon\Carbon::today()->startOfDay();
        $numDays = 30;
    }

    $today = now()->toDateString();
    $settings = \App\Models\ParkSetting::first();
    $daytimeStart = $settings ? strtotime((string) ($settings->daytime_start ?? '06:00')) : strtotime('06:00');
    $daytimeEnd = $settings ? strtotime((string) ($settings->daytime_end ?? '18:00')) : strtotime('18:00');
    $nowSeconds = strtotime(now()->format('H:i'));
    $isTodayNighttime = !($nowSeconds >= $daytimeStart && $nowSeconds < $daytimeEnd);

    $availability = [];

    for ($i = 0; $i < $numDays; $i++) {
        $date = $startDate->copy()->addDays($i)->toDateString();
        $nextDate = $startDate->copy()->addDays($i + 1)->toDateString();

        $daytimeAvailable = true;
        $nighttimeAvailable = true;
        $nextDaytimeAvailable = true;

        if ($date < $today) {
            $daytimeAvailable = false;
            $nighttimeAvailable = false;
        } elseif ($date === $today && $isTodayNighttime) {
            $daytimeAvailable = false;
        }

        if ($nextDate < $today) {
            $nextDaytimeAvailable = false;
        } elseif ($nextDate === $today && $isTodayNighttime) {
            $nextDaytimeAvailable = false;
        }

        if ($daytimeAvailable) {
            foreach ($amenityIds as $aId) {
                if ($isAmenityRangeTaken($aId, $date, $date, 'Daytime', 'Daytime')) {
                    $daytimeAvailable = false;
                    break;
                }
            }
        }

        if ($nighttimeAvailable) {
            foreach ($amenityIds as $aId) {
                if ($isAmenityRangeTaken($aId, $date, $date, 'Nighttime', 'Nighttime')) {
                    $nighttimeAvailable = false;
                    break;
                }
            }
        }

        if ($nextDaytimeAvailable) {
            foreach ($amenityIds as $aId) {
                if ($isAmenityRangeTaken($aId, $nextDate, $nextDate, 'Daytime', 'Daytime')) {
                    $nextDaytimeAvailable = false;
                    break;
                }
            }
        }

        $availability[] = [
            'date' => $date,
            'daytime' => $daytimeAvailable,
            'nighttime' => $nighttimeAvailable,
            'daytonight' => $daytimeAvailable && $nighttimeAvailable,
            'nighttoday' => $nighttimeAvailable && $nextDaytimeAvailable,
        ];
    }

    return response()->json([
        'amenity_id' => $amenityId,
        'amenity_ids' => $amenityIds,
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
    $parkSettings = \App\Models\ParkSetting::first();

    return view('reservationpage', [
        'amenities' => $amenities,
        'weatherPreview' => $weatherPreview,
        'maxReservationDate' => $maxReservationDate,
        'parkSettings' => $parkSettings,
    ]);
})->name('reservation');

// ── PayMongo Reservation & Payment Endpoints ─────────────────────────────────

$createReservationFromPayment = function (string $paymentIntentId, ?string $paymentMethod = null, ?array $intentDetails = null) use ($calculateContinuousSlotsCount): ?Reservation {
    $existing = Reservation::where('payment_intent_id', $paymentIntentId)->first();
    if ($existing) {
        return $existing;
    }

    $pending = Cache::get("pending_reservation_{$paymentIntentId}");

    if (!$pending && $intentDetails && !empty($intentDetails['metadata'])) {
        $meta = $intentDetails['metadata'];
        if (!empty($meta['booker_name'])) {
            $pending = [
                'booker_name' => $meta['booker_name'] ?? 'Guest',
                'phone' => $meta['phone'] ?? '',
                'email' => $meta['email'] ?? '',
                'number_of_guests' => (int) ($meta['number_of_guests'] ?? 1),
                'reservation_date' => $meta['reservation_date'] ?? null,
                'end_date' => $meta['end_date'] ?? ($meta['reservation_date'] ?? null),
                'start_slot' => $meta['start_slot'] ?? 'Daytime',
                'end_slot' => $meta['end_slot'] ?? 'Daytime',
                'total_days' => (int) ($meta['total_days'] ?? 1),
                'total_amount' => (float) ($meta['total_amount'] ?? 0),
                'deposit_amount' => (float) ($meta['deposit_amount'] ?? 0),
                'remaining_balance' => (float) ($meta['remaining_balance'] ?? 0),
                'amenities' => !empty($meta['amenities_json']) ? json_decode($meta['amenities_json'], true) : [],
                'slot' => $meta['slot'] ?? 'Daytime',
            ];
        }
    }

    if (!$pending) {
        return null;
    }

    $extractedMethod = null;
    if ($intentDetails && !empty($intentDetails['payments'])) {
        $firstPayment = $intentDetails['payments'][0]['attributes'] ?? [];
        $extractedMethod = $firstPayment['source']['type'] ?? $firstPayment['payment_method_type'] ?? null;
    }

    $finalPaymentMethod = $paymentMethod 
        ?: ($pending['payment_method'] ?? null) 
        ?: ($pending['payment_method_type'] ?? null) 
        ?: $extractedMethod 
        ?: 'gcash';

    $startDate = $pending['reservation_date'] ?? now()->toDateString();
    $endDate = $pending['end_date'] ?? $startDate;
    $startSlot = $pending['start_slot'] ?? ($pending['slot'] ?? 'Daytime');
    $endSlot = $pending['end_slot'] ?? $startSlot;
    $mainCounts = $calculateContinuousSlotsCount($startDate, $endDate, $startSlot, $endSlot);

    $reservation = DB::transaction(function () use ($pending, $paymentIntentId, $finalPaymentMethod, $startDate, $endDate, $startSlot, $endSlot, $mainCounts, $calculateContinuousSlotsCount) {
        $reservation = Reservation::create([
            'booker_name' => $pending['booker_name'],
            'phone' => $pending['phone'],
            'email' => $pending['email'],
            'reservation_date' => $startDate ? now()->parse($startDate)->toDateTimeString() : null,
            'end_date' => $endDate ? now()->parse($endDate)->toDateTimeString() : null,
            'start_slot' => $startSlot,
            'end_slot' => $endSlot,
            'total_days' => $pending['total_days'] ?? $mainCounts['days_span'],
            'check_in' => null,
            'number_of_guests' => $pending['number_of_guests'],
            'status' => 'Pending',
            'total_amount' => $pending['total_amount'],
            'amount_paid' => $pending['deposit_amount'],
            'remaining_balance' => $pending['remaining_balance'],
            'payment_status' => 'Partially Paid',
            'payment_intent_id' => $paymentIntentId,
            'payment_method' => $finalPaymentMethod,
            'reservation_type' => 'online',
        ]);

        // Create primary guest customer & reservation_guests link
        $nameParts = explode(' ', trim((string) $pending['booker_name']));
        $firstName = array_shift($nameParts) ?: 'Guest';
        $lastName = implode(' ', $nameParts) ?: 'Booker';

        $primaryCustomer = Customer::firstOrCreate(
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $pending['email'] ?: null,
                'phone' => $pending['phone'] ?: null,
            ],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $pending['email'] ?: null,
                'phone' => $pending['phone'] ?: null,
                'gender' => 'Male',
                'is_foreigner' => false,
            ]
        );

        ReservationGuest::firstOrCreate([
            'reservation_id' => $reservation->id,
            'customer_id' => $primaryCustomer->id,
        ], [
            'is_primary_guest' => true,
        ]);

        if (!empty($pending['amenities']) && is_array($pending['amenities'])) {
            foreach ($pending['amenities'] as $amenity) {
                $itemStartDate = $amenity['start_date'] ?? $startDate;
                $itemEndDate = $amenity['end_date'] ?? ($endDate ?: $itemStartDate);
                $itemStartSlot = $amenity['start_slot'] ?? $startSlot;
                $itemEndSlot = $amenity['end_slot'] ?? $endSlot;
                $slotCounts = $calculateContinuousSlotsCount($itemStartDate, $itemEndDate, $itemStartSlot, $itemEndSlot);

                // Verify amenity exists; if not found, try active fallback
                $amenityId = $amenity['amenity_id'] ?? null;
                if ($amenityId && !Amenity::where('id', $amenityId)->exists()) {
                    $amenityId = Amenity::where('status', true)->value('id');
                }

                if ($amenityId) {
                    $pricingType = $slotCounts['days_span'] > 1
                        ? ("Continuous Stay ({$slotCounts['days_span']}D)" . (str_contains($amenity['pricing_type'] ?? '', 'Aircon') ? ' Aircon' : ''))
                        : ($amenity['pricing_type'] ?? $itemStartSlot);

                    ReservationAmenity::create([
                        'reservation_id' => $reservation->id,
                        'amenity_id' => $amenityId,
                        'start_date' => $itemStartDate,
                        'end_date' => $itemEndDate,
                        'start_slot' => $itemStartSlot,
                        'end_slot' => $itemEndSlot,
                        'day_slots_count' => $amenity['day_slots_count'] ?? $slotCounts['day_count'],
                        'night_slots_count' => $amenity['night_slots_count'] ?? $slotCounts['night_count'],
                        'pricing_type' => $pricingType,
                        'price_at_booking' => $amenity['price_at_booking'] ?? 0,
                        'quantity' => 1,
                        'remarks' => "Continuous Stay: {$itemStartDate} ({$itemStartSlot}) to {$itemEndDate} ({$itemEndSlot})",
                        'status' => 'Active',
                    ]);
                }
            }
        }

        return $reservation;
    });

    // ── Post-transaction side-effects (email & activity log) ──────────────────
    // These are intentionally kept OUTSIDE the DB transaction to avoid holding
    // the database lock while waiting for SMTP or other slow I/O operations.
    if ($reservation) {
        Cache::forget("pending_reservation_{$paymentIntentId}");

        if (!empty($reservation->email)) {
            try {
                Mail::to(trim($reservation->email))->send(new ReservationQrMail($reservation));
            } catch (\Throwable $ex) {
                \Illuminate\Support\Facades\Log::error("Failed to dispatch ReservationQrMail for reservation #{$reservation->id} to {$reservation->email}: " . $ex->getMessage(), ['exception' => $ex]);
                report($ex);
            }
        }

        ActivityLog::log(
            activityType: 'online_reservation_created',
            title: 'New Online Reservation',
            description: "New online reservation #{$reservation->id} from {$reservation->booker_name} ({$reservation->number_of_guests} guests) for " . ($reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->format('M d, Y') : 'upcoming date'),
            reservationId: $reservation->id,
            actorName: $reservation->booker_name,
            actorRole: 'guest',
            staffId: null,
            metadata: [
                'total_amount' => $reservation->total_amount,
                'amount_paid' => $reservation->amount_paid,
                'number_of_guests' => $reservation->number_of_guests,
                'booker_name' => $reservation->booker_name,
            ]
        );
    }

    return $reservation;
};

Route::post('/reservation/create-intent', function (Request $request, \App\Services\PayMongoService $payMongo) use ($isAmenityRangeTaken, $calculateContinuousSlotsCount) {
    $data = $request->validate([
        'booker_name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'number_of_guests' => ['required', 'integer', 'min:1'],
        'check_in' => ['nullable', 'date'],
        'reservation_date' => ['nullable', 'date'],
        'end_date' => ['nullable', 'date'],
        'start_slot' => ['nullable', 'string'],
        'end_slot' => ['nullable', 'string'],
        'total_days' => ['nullable', 'integer'],
        'slot' => ['nullable', 'string'],
        'amenities' => ['nullable', 'array'],
        'amenities.*.amenity_id' => ['required_with:amenities', 'string'],
        'amenities.*.start_date' => ['nullable', 'date'],
        'amenities.*.end_date' => ['nullable', 'date'],
        'amenities.*.start_slot' => ['nullable', 'string'],
        'amenities.*.end_slot' => ['nullable', 'string'],
        'amenities.*.pricing_type' => ['nullable', 'string'],
        'amenities.*.price_at_booking' => ['required_with:amenities', 'numeric'],
        'amenities.*.day_slots_count' => ['nullable', 'integer'],
        'amenities.*.night_slots_count' => ['nullable', 'integer'],
        'amenity_id' => ['nullable', 'string'],
        'pricing_type' => ['nullable', 'string'],
        'price_at_booking' => ['nullable', 'numeric'],
    ]);

    $reservationDate = $data['reservation_date'] ?? $data['check_in'] ?? now()->toDateString();
    $endDate = $data['end_date'] ?? $reservationDate;
    $startSlot = $data['start_slot'] ?? ($data['slot'] ?? 'Daytime');
    $endSlot = $data['end_slot'] ?? $startSlot;

    $amenities = is_array($data['amenities'] ?? null) && count($data['amenities']) > 0
        ? $data['amenities']
        : [[
            'amenity_id' => $data['amenity_id'] ?? null,
            'start_date' => $reservationDate,
            'end_date' => $endDate,
            'start_slot' => $startSlot,
            'end_slot' => $endSlot,
            'pricing_type' => $data['pricing_type'] ?? $startSlot,
            'price_at_booking' => $data['price_at_booking'] ?? 0,
        ]];

    $amenities = array_values(array_filter($amenities, fn ($amenity) => ! empty($amenity['amenity_id'])));

    if ($amenities === []) {
        return response()->json([
            'success' => false,
            'message' => 'At least one amenity is required.',
        ], 422);
    }

    $today = now()->toDateString();

    // Check slot conflicts and today restrictions for each amenity item
    foreach ($amenities as &$amenity) {
        $itemStartDate = $amenity['start_date'] ?? $reservationDate;
        $itemEndDate = $amenity['end_date'] ?? ($endDate ?: $itemStartDate);
        $itemStartSlot = $amenity['start_slot'] ?? $startSlot;
        $itemEndSlot = $amenity['end_slot'] ?? $endSlot;
        $amenityId = $amenity['amenity_id'];

        $amenityModel = Amenity::find($amenityId);
        if (!$amenityModel) {
            return response()->json([
                'success' => false,
                'message' => 'The selected amenity does not exist or is no longer available. Please refresh the page and select an amenity.',
            ], 422);
        }

        $settings = \App\Models\ParkSetting::first();
        $daytimeStart = $settings ? strtotime((string) ($settings->daytime_start ?? '06:00')) : strtotime('06:00');
        $daytimeEnd = $settings ? strtotime((string) ($settings->daytime_end ?? '18:00')) : strtotime('18:00');
        $nowSeconds = strtotime(now()->format('H:i'));
        $isTodayNighttime = !($nowSeconds >= $daytimeStart && $nowSeconds < $daytimeEnd);

        if ($itemStartDate === $today && $isTodayNighttime && (str_contains($itemStartSlot, 'Day') || in_array($itemStartSlot, ['Daytime', 'DayToNight'], true))) {
            return response()->json([
                'success' => false,
                'message' => 'Daytime bookings are not allowed for today as the daytime session has already concluded. Please choose Nighttime or select an upcoming date.',
            ], 409);
        }

        // Validate amenity schedule is strictly within overall reservation date and session range
        if ($itemStartDate < $reservationDate || $itemEndDate > $endDate) {
            $name = $amenityModel->amenities_name;
            return response()->json([
                'success' => false,
                'message' => "The schedule for {$name} ({$itemStartDate} to {$itemEndDate}) must fall within the overall reservation dates ({$reservationDate} to {$endDate}).",
            ], 422);
        }

        $startSlotNorm = (str_contains($startSlot, 'DayToNight') || str_contains($startSlot, 'Daytime') || str_starts_with($startSlot, 'Day')) ? 'Daytime' : 'Nighttime';
        $itemStartSlotNorm = (str_contains($itemStartSlot, 'DayToNight') || str_contains($itemStartSlot, 'Daytime') || str_starts_with($itemStartSlot, 'Day')) ? 'Daytime' : 'Nighttime';
        $endSlotNorm = (str_contains($endSlot, 'DayToNight') || str_contains($endSlot, 'Nighttime') || str_contains($endSlot, 'Night')) ? 'Nighttime' : 'Daytime';
        $itemEndSlotNorm = (str_contains($itemEndSlot, 'DayToNight') || str_contains($itemEndSlot, 'Nighttime') || str_contains($itemEndSlot, 'Night')) ? 'Nighttime' : 'Daytime';

        if ($itemStartDate === $reservationDate && $startSlotNorm === 'Nighttime' && $itemStartSlotNorm === 'Daytime') {
            $name = $amenityModel->amenities_name;
            return response()->json([
                'success' => false,
                'message' => "The start slot for {$name} on {$reservationDate} cannot be Daytime because the reservation starts at Nighttime.",
            ], 422);
        }

        if ($itemEndDate === $endDate && $endSlotNorm === 'Daytime' && $itemEndSlotNorm === 'Nighttime') {
            $name = $amenityModel->amenities_name;
            return response()->json([
                'success' => false,
                'message' => "The end slot for {$name} on {$endDate} cannot be Nighttime because the reservation ends at Daytime.",
            ], 422);
        }

        if ($itemStartDate === $itemEndDate && $itemStartSlotNorm === 'Nighttime' && $itemEndSlotNorm === 'Daytime') {
            $name = $amenityModel->amenities_name;
            return response()->json([
                'success' => false,
                'message' => "The schedule for {$name} on {$itemStartDate} is invalid: Daytime cannot end after Nighttime start on the same day.",
            ], 422);
        }

        if ($isAmenityRangeTaken($amenityId, $itemStartDate, $itemEndDate, $itemStartSlot, $itemEndSlot)) {
            $name = $amenityModel->amenities_name;
            return response()->json([
                'success' => false,
                'message' => "{$name} is already booked for some or all of the selected dates ({$itemStartDate} to {$itemEndDate}). Please choose a different date range or amenity.",
            ], 409);
        }

        $slotCounts = $calculateContinuousSlotsCount($itemStartDate, $itemEndDate, $itemStartSlot, $itemEndSlot);
        $amenity['day_slots_count'] = $slotCounts['day_count'];
        $amenity['night_slots_count'] = $slotCounts['night_count'];
        $amenity['start_date'] = $itemStartDate;
        $amenity['end_date'] = $itemEndDate;
        $amenity['start_slot'] = $itemStartSlot;
        $amenity['end_slot'] = $itemEndSlot;
    }
    unset($amenity);

    $totalAmount = array_sum(array_column($amenities, 'price_at_booking'));
    $depositPercentage = config('paymongo.deposit_percentage', 50);
    $depositAmount = round($totalAmount * ($depositPercentage / 100), 2);
    $remainingBalance = round($totalAmount - $depositAmount, 2);

    $mainCounts = $calculateContinuousSlotsCount($reservationDate, $endDate, $startSlot, $endSlot);

    // Prepare pending payload
    $pendingPayload = [
        'booker_name' => $data['booker_name'],
        'phone' => $data['phone'],
        'email' => $data['email'],
        'number_of_guests' => $data['number_of_guests'],
        'reservation_date' => $reservationDate,
        'end_date' => $endDate,
        'start_slot' => $startSlot,
        'end_slot' => $endSlot,
        'total_days' => $data['total_days'] ?? $mainCounts['days_span'],
        'slot' => $startSlot,
        'amenities' => $amenities,
        'total_amount' => $totalAmount,
        'deposit_amount' => $depositAmount,
        'remaining_balance' => $remainingBalance,
    ];

    // Create PayMongo Payment Intent for deposit
    try {
        $depositCentavos = \App\Services\PayMongoService::toCentavos($depositAmount);
        if ($depositCentavos < 2000) {
            $depositCentavos = 2000;
        }

        $metadata = [
            'booker_name' => substr($data['booker_name'], 0, 40),
            'phone' => substr($data['phone'], 0, 20),
            'email' => substr($data['email'], 0, 40),
            'number_of_guests' => (string) $data['number_of_guests'],
            'reservation_date' => (string) $reservationDate,
            'end_date' => (string) $endDate,
            'start_slot' => (string) $startSlot,
            'end_slot' => (string) $endSlot,
            'total_days' => (string) ($data['total_days'] ?? $mainCounts['days_span']),
            'total_amount' => (string) $totalAmount,
            'deposit_amount' => (string) $depositAmount,
            'remaining_balance' => (string) $remainingBalance,
            'amenities_json' => json_encode($amenities),
        ];

        $intent = $payMongo->createPaymentIntent(
            $depositCentavos,
            "50% Deposit for Reservation - {$data['booker_name']}",
            ['gcash', 'paymaya', 'card', 'qrph'],
            null,
            $metadata
        );

        Cache::put("pending_reservation_{$intent['id']}", $pendingPayload, now()->addHours(2));

        return response()->json([
            'success' => true,
            'payment_intent_id' => $intent['id'],
            'client_key' => $intent['client_key'],
            'total_amount' => $totalAmount,
            'deposit_amount' => $depositAmount,
            'remaining_balance' => $remainingBalance,
        ]);
    } catch (\Throwable $e) {
        report($e);
        return response()->json([
            'success' => false,
            'message' => 'Failed to initialize payment gateway: ' . \App\Services\PayMongoService::readableError($e),
        ], 500);
    }
})->name('reservation.create-intent')->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/reservation/process-payment', function (Request $request, \App\Services\PayMongoService $payMongo) use ($createReservationFromPayment) {
    $data = $request->validate([
        'payment_intent_id' => ['required', 'string'],
        'client_key' => ['nullable', 'string'],
        'payment_method_type' => ['required', 'string', 'in:gcash,paymaya,card,qrph'],
        'card_number' => ['required_if:payment_method_type,card', 'nullable', 'string'],
        'exp_month' => ['required_if:payment_method_type,card', 'nullable', 'integer'],
        'exp_year' => ['required_if:payment_method_type,card', 'nullable', 'integer'],
        'cvc' => ['required_if:payment_method_type,card', 'nullable', 'string'],
    ]);

    $pending = Cache::get("pending_reservation_{$data['payment_intent_id']}");
    if ($pending) {
        $pending['payment_method'] = $data['payment_method_type'];
        Cache::put("pending_reservation_{$data['payment_intent_id']}", $pending, now()->addHours(2));
    }

    try {
        $billing = [
            'name' => $pending['booker_name'] ?? 'Guest',
            'email' => $pending['email'] ?? 'guest@example.com',
            'phone' => $pending['phone'] ?? '',
        ];

        $cardDetails = [];
        if ($data['payment_method_type'] === 'card') {
            $cardDetails = [
                'card_number' => $data['card_number'] ?? '',
                'exp_month' => $data['exp_month'] ?? 0,
                'exp_year' => $data['exp_year'] ?? 0,
                'cvc' => $data['cvc'] ?? '',
            ];
        }

        // 1. Create PaymentMethod
        $pm = $payMongo->createPaymentMethod($data['payment_method_type'], $cardDetails, $billing);

        // 2. Attach PaymentMethod to PaymentIntent
        $returnUrl = route('reservation');
        $attached = $payMongo->attachPaymentMethod(
            $data['payment_intent_id'],
            $pm['id'],
            $data['client_key'] ?? null,
            $returnUrl
        );

        $status = $attached['status'] ?? 'unknown';

        // If payment completed immediately (e.g. test cards)
        if ($status === 'succeeded') {
            $createReservationFromPayment($data['payment_intent_id'], $data['payment_method_type'], $attached);
        }

        return response()->json([
            'success' => true,
            'status' => $status,
            'next_action' => $attached['next_action'] ?? null,
            'payment_intent_id' => $data['payment_intent_id'],
        ]);
    } catch (\Throwable $e) {
        report($e);
        return response()->json([
            'success' => false,
            'message' => \App\Services\PayMongoService::readableError($e),
        ], 400);
    }
})->name('reservation.process-payment')->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/reservation/check-payment-status', function (Request $request, \App\Services\PayMongoService $payMongo) use ($createReservationFromPayment) {
    $data = $request->validate([
        'payment_intent_id' => ['required', 'string'],
    ]);

    try {
        $intent = $payMongo->getPaymentIntent($data['payment_intent_id']);
        $status = $intent['status'] ?? 'unknown';
        $reservation = null;

        if ($status === 'succeeded') {
            $reservation = $createReservationFromPayment($data['payment_intent_id'], null, $intent);
        }

        return response()->json([
            'success' => true,
            'status' => $status,
            'payment_status' => $reservation ? $reservation->payment_status : ($status === 'succeeded' ? 'Partially Paid' : 'Unpaid'),
            'reservation_id' => $reservation?->id,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => \App\Services\PayMongoService::readableError($e),
        ], 400);
    }
})->name('reservation.check-payment-status')->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/reservation/prototype', function (Request $request) use ($isAmenityRangeTaken, $calculateContinuousSlotsCount) {
    $data = $request->validate([
        'booker_name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'number_of_guests' => ['required', 'integer', 'min:1'],
        'amenity_id' => ['required', 'string'],
        'pricing_type' => ['required', 'string'],
        'price_at_booking' => ['required', 'numeric'],
        'check_in' => ['nullable', 'date'],
        'check_out' => ['nullable', 'date'],
        'reservation_date' => ['nullable', 'date'],
        'end_date' => ['nullable', 'date'],
        'start_slot' => ['nullable', 'string'],
        'end_slot' => ['nullable', 'string'],
        'slot' => ['nullable', 'string'],
    ]);

    $startDate = $data['reservation_date'] ?? $data['check_in'] ?? now()->toDateString();
    $endDate = $data['end_date'] ?? ($data['check_out'] ?? $startDate);
    $startSlot = $data['start_slot'] ?? ($data['slot'] ?? 'Daytime');
    $endSlot = $data['end_slot'] ?? $startSlot;

    $amenityModel = Amenity::find($data['amenity_id']);
    if (! $amenityModel) {
        return response()->json([
            'success' => false,
            'message' => 'The selected amenity does not exist.',
        ], 422);
    }

    if ($isAmenityRangeTaken($data['amenity_id'], $startDate, $endDate, $startSlot, $endSlot)) {
        return response()->json([
            'success' => false,
            'message' => "{$amenityModel->amenities_name} is already booked for the selected dates/session.",
        ], 409);
    }

    $slotCounts = $calculateContinuousSlotsCount($startDate, $endDate, $startSlot, $endSlot);
    $totalAmount = (float) $data['price_at_booking'];
    $depositAmount = round($totalAmount * 0.5, 2);
    $remainingBalance = round($totalAmount - $depositAmount, 2);

    $reservation = DB::transaction(function () use ($data, $startDate, $endDate, $startSlot, $endSlot, $slotCounts, $totalAmount, $depositAmount, $remainingBalance) {
        $res = Reservation::create([
            'booker_name' => $data['booker_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'reservation_date' => $startDate ? now()->parse($startDate)->toDateTimeString() : null,
            'end_date' => $endDate ? now()->parse($endDate)->toDateTimeString() : null,
            'check_in' => $startDate,
            'check_out' => $endDate,
            'start_slot' => $startSlot,
            'end_slot' => $endSlot,
            'total_days' => $slotCounts['days_span'],
            'number_of_guests' => $data['number_of_guests'],
            'status' => 'Pending',
            'total_amount' => $totalAmount,
            'amount_paid' => $depositAmount,
            'remaining_balance' => $remainingBalance,
            'payment_status' => 'Partially Paid',
            'payment_method' => 'online',
            'reservation_type' => 'online',
        ]);

        $nameParts = explode(' ', trim((string) $data['booker_name']));
        $firstName = array_shift($nameParts) ?: 'Guest';
        $lastName = implode(' ', $nameParts) ?: 'Booker';

        $customer = Customer::firstOrCreate(
            [
                'email' => $data['email'],
            ],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'gender' => 'Male',
                'is_foreigner' => false,
            ]
        );

        ReservationGuest::create([
            'reservation_id' => $res->id,
            'customer_id' => $customer->id,
            'is_primary_guest' => true,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $data['amenity_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_slot' => $startSlot,
            'end_slot' => $endSlot,
            'day_slots_count' => $slotCounts['day_count'],
            'night_slots_count' => $slotCounts['night_count'],
            'pricing_type' => $data['pricing_type'],
            'price_at_booking' => $totalAmount,
            'quantity' => 1,
            'remarks' => 'Online reservation. Slot: ' . $startSlot,
            'status' => 'Active',
        ]);

        return $res;
    });

    if (!empty($data['email'])) {
        try {
            Mail::to(trim($data['email']))->send(new ReservationQrMail($reservation));
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error("Failed to dispatch prototype ReservationQrMail to {$data['email']}: " . $exception->getMessage(), ['exception' => $exception]);
            report($exception);
        }
    }

    ActivityLog::log(
        activityType: 'online_reservation_created',
        title: 'New Online Reservation',
        description: "New online reservation #{$reservation->id} from {$reservation->booker_name} ({$reservation->number_of_guests} guests)",
        reservationId: $reservation->id,
        actorName: $reservation->booker_name,
        actorRole: 'guest',
        staffId: null,
        metadata: [
            'total_amount' => $reservation->total_amount,
            'amount_paid' => $reservation->amount_paid,
            'number_of_guests' => $reservation->number_of_guests,
            'booker_name' => $reservation->booker_name,
        ]
    );

    return response()->json([
        'success' => true,
        'reservation_id' => $reservation->id,
        'message' => 'Prototype reservation recorded and marked partially paid.',
    ]);
})->name('reservation.prototype')->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/paymongo/webhook', function (Request $request) use ($createReservationFromPayment) {
    $payload = $request->all();
    $event = $payload['data']['attributes']['type'] ?? null;
    $paymentData = $payload['data']['attributes']['data']['attributes'] ?? [];

    $paymentIntentId = $paymentData['payment_intent_id']
        ?? ($paymentData['payment_intent']['id'] ?? null)
        ?? ($payload['data']['attributes']['data']['id'] ?? null);

    if ($paymentIntentId && in_array($event, ['payment.paid', 'payment_intent.succeeded'], true)) {
        $createReservationFromPayment($paymentIntentId, null, $paymentData);
    }

    return response()->json(['status' => 'ok']);
})->name('paymongo.webhook')->withoutMiddleware([VerifyCsrfToken::class]);

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

    $staffName = $user['name'] ?? 'Staff User';
    ActivityLog::log(
        activityType: 'check_in',
        title: 'Guest Checked In',
        description: "Reservation #{$reservation->id} ({$reservation->booker_name}) checked in by {$staffName}",
        reservationId: $reservation->id,
        actorName: $staffName,
        actorRole: $user['role'] ?? 'staff',
        staffId: (string) ($user['id'] ?? ''),
        metadata: ['staff_name' => $staffName]
    );

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

        // Reservation status breakdown for the donut chart
        $statusBreakdown = Reservation::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Revenue per day for the last 7 days (bar chart)
        $weekRevenue = [];
        $weekDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $weekDays[] = $date->format('D');
            $weekRevenue[] = (int) Reservation::whereDate('check_in', $date->toDateString())->sum('amount_paid');
        }

        $recentActivities = ActivityLog::orderByDesc('created_at')->take(10)->get();

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
            'recentActivities' => $recentActivities,
            'statusBreakdown' => $statusBreakdown,
            'weekRevenue' => $weekRevenue,
            'weekDays' => $weekDays,
        ]);
    })->name('dashboard');

    Route::get('/amenities', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        $amenities = Amenity::orderBy('amenities_name')->get();

        return view('admin.admin_amenitiesmanagement', [
            'amenities' => $amenities,
            'totalAmenities' => $amenities->count(),
            'enabledAmenities' => $amenities->where('status', true)->count(),
            'disabledAmenities' => $amenities->where('status', false)->count(),
            'onSaleAmenities' => $amenities->filter(fn ($a) => $a->sale_percentage && $a->sale_percentage > 0)->count(),
        ]);
    })->name('amenities');

    Route::get('/feedback', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        $feedbacks = Feedback::orderByDesc('created_at')->get();

        return view('admin.admin_feedback', [
            'feedbacks' => $feedbacks,
            'totalFeedbacks' => $feedbacks->count(),
            'shownFeedbacks' => $feedbacks->where('is_shown', true)->count(),
            'hiddenFeedbacks' => $feedbacks->where('is_shown', false)->count(),
            'averageStars' => $feedbacks->count() > 0 ? round($feedbacks->avg('stars'), 1) : 0,
            'todayFeedbacks' => $feedbacks->filter(fn ($f) => $f->created_at->isToday())->count(),
        ]);
    })->name('feedback');

    Route::patch('/feedback/{feedback}/visibility', function (Request $request, Feedback $feedback) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'is_shown' => ['required', 'boolean'],
        ]);

        $feedback->update(['is_shown' => $validated['is_shown']]);

        ActivityLog::log(
            activityType: 'feedback_visibility',
            title: $validated['is_shown'] ? 'Feedback Shown' : 'Feedback Hidden',
            description: "Admin ".($validated['is_shown'] ? 'showed' : 'hid')." guest feedback from {$feedback->full_name}",
            actorName: $user['name'] ?? 'Admin User',
            actorRole: $user['role'] ?? 'admin',
            staffId: isset($user['id']) ? (string) $user['id'] : null,
            metadata: ['feedback_id' => $feedback->id, 'is_shown' => $validated['is_shown']]
        );

        return response()->json([
            'success' => true,
            'message' => $validated['is_shown'] ? 'Feedback is now visible on the website.' : 'Feedback is now hidden from the website.',
            'feedback' => $feedback->fresh(),
        ]);
    })->name('feedback.visibility');

    Route::delete('/feedback/{feedback}', function (Request $request, Feedback $feedback) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $guestName = $feedback->full_name;
        $feedbackId = $feedback->id;
        $feedback->delete();

        ActivityLog::log(
            activityType: 'feedback_deleted',
            title: 'Feedback Deleted',
            description: "Admin deleted guest feedback from {$guestName}",
            actorName: $user['name'] ?? 'Admin User',
            actorRole: $user['role'] ?? 'admin',
            staffId: isset($user['id']) ? (string) $user['id'] : null,
            metadata: ['feedback_id' => $feedbackId, 'guest_name' => $guestName]
        );

        return response()->json([
            'success' => true,
            'message' => 'Feedback deleted successfully.',
        ]);
    })->name('feedback.destroy');

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

        $staffAccounts = StaffAccount::orderBy('name')->get();

        return view('admin.admin_usermanagement', [
            'staffAccounts' => $staffAccounts,
            'totalStaff' => $staffAccounts->count(),
            'activeStaff' => $staffAccounts->where('ban_status', false)->count(),
            'bannedStaff' => $staffAccounts->where('ban_status', true)->count(),
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

        $staff = StaffAccount::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'ban_status' => (bool) ($data['ban_status'] ?? false),
        ]);

        $adminName = $user['name'] ?? 'Admin User';
        ActivityLog::log(
            activityType: 'staff_created',
            title: 'Staff Account Created',
            description: "Staff account '{$staff->name}' ({$staff->email}) was created by {$adminName}",
            actorName: $adminName,
            actorRole: 'admin',
            staffId: (string) $staff->id,
            metadata: ['staff_id' => $staff->id, 'staff_name' => $staff->name, 'staff_email' => $staff->email]
        );

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

        $adminName = $user['name'] ?? 'Admin User';
        ActivityLog::log(
            activityType: 'staff_updated',
            title: 'Staff Account Updated',
            description: "Staff account '{$staffAccount->name}' was updated by {$adminName}",
            actorName: $adminName,
            actorRole: 'admin',
            staffId: (string) $staffAccount->id,
            metadata: ['staff_id' => $staffAccount->id, 'staff_name' => $staffAccount->name]
        );

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

        $adminName = $user['name'] ?? 'Admin User';
        $actionText = $staffAccount->ban_status ? 'banned' : 'unbanned';
        ActivityLog::log(
            activityType: $staffAccount->ban_status ? 'staff_banned' : 'staff_unbanned',
            title: 'Staff Account ' . ucfirst($actionText),
            description: "Staff account '{$staffAccount->name}' was {$actionText} by {$adminName}",
            actorName: $adminName,
            actorRole: 'admin',
            staffId: (string) $staffAccount->id,
            metadata: ['staff_id' => $staffAccount->id, 'staff_name' => $staffAccount->name, 'ban_status' => $staffAccount->ban_status]
        );

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
        $parkRules = \App\Models\ParkRule::orderBy('id', 'asc')->get();
        return view('admin.admin_settings', [
            'parkSettings' => $parkSettings,
            'parkRules' => $parkRules,
        ]);
    })->name('settings');

    Route::post('/settings/park/update', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'park_status' => 'required|in:open,closed',
            'close_description' => 'nullable|string|max:1000',
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

        if ($validated['park_status'] === 'open') {
            $validated['close_description'] = null;
        }

        $parkSettings = \App\Models\ParkSetting::first();
        if (!$parkSettings) {
            $parkSettings = new \App\Models\ParkSetting();
        }

        $previousStatus = $parkSettings->park_status ?? 'open';
        $previousDesc = $parkSettings->close_description ?? null;

        $parkSettings->fill($validated);
        $parkSettings->save();

        // Activity Log for Park Status Change
        if ($previousStatus !== $validated['park_status'] || ($validated['park_status'] === 'closed' && $previousDesc !== $validated['close_description'])) {
            $statusLabel = $validated['park_status'] === 'open' ? 'Open' : 'Closed';
            $descText = $validated['park_status'] === 'closed' && !empty($validated['close_description']) 
                ? "Admin set park status to Closed: {$validated['close_description']}"
                : "Admin set park status to {$statusLabel}";

            \App\Models\ActivityLog::log(
                activityType: 'park_status_updated',
                title: "Park Status: {$statusLabel}",
                description: $descText,
                reservationId: null,
                actorName: $user['name'] ?? 'Admin User',
                actorRole: $user['role'] ?? 'admin',
            );
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Park settings updated successfully.',
                'park_status' => $parkSettings->park_status,
                'close_description' => $parkSettings->close_description,
            ]);
        }

        return redirect()->route('admin.settings')->with('success', 'Park settings updated successfully.');
    })->name('settings.park.update');

    // Park Rules CRUD Routes
    Route::post('/settings/rules', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (!$user || $user['role'] !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'rule_name' => ['required', 'string', 'max:255'],
            'rule_descriptions' => ['required', 'string', 'max:2000'],
        ]);

        $rule = \App\Models\ParkRule::create($validated);

        \App\Models\ActivityLog::log(
            activityType: 'rule_created',
            title: 'Park Rule Created',
            description: "Admin created park rule: {$rule->rule_name}",
            reservationId: null,
            actorName: $user['name'] ?? 'Admin User',
            actorRole: $user['role'] ?? 'admin',
            staffId: isset($user['id']) ? (string) $user['id'] : null,
            metadata: ['rule_id' => $rule->id, 'rule_name' => $rule->rule_name]
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Park rule created successfully.',
                'rule' => $rule,
            ]);
        }

        return redirect()->route('admin.settings')->with('success', 'Park rule created successfully.');
    })->name('settings.rules.store');

    Route::put('/settings/rules/{parkRule}', function (Request $request, \App\Models\ParkRule $parkRule) {
        $user = $request->session()->get('auth_user');
        if (!$user || $user['role'] !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'rule_name' => ['required', 'string', 'max:255'],
            'rule_descriptions' => ['required', 'string', 'max:2000'],
        ]);

        $parkRule->update($validated);

        \App\Models\ActivityLog::log(
            activityType: 'rule_updated',
            title: 'Park Rule Updated',
            description: "Admin updated park rule: {$parkRule->rule_name}",
            reservationId: null,
            actorName: $user['name'] ?? 'Admin User',
            actorRole: $user['role'] ?? 'admin',
            staffId: isset($user['id']) ? (string) $user['id'] : null,
            metadata: ['rule_id' => $parkRule->id, 'rule_name' => $parkRule->rule_name]
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Park rule updated successfully.',
                'rule' => $parkRule,
            ]);
        }

        return redirect()->route('admin.settings')->with('success', 'Park rule updated successfully.');
    })->name('settings.rules.update');

    Route::delete('/settings/rules/{parkRule}', function (Request $request, \App\Models\ParkRule $parkRule) {
        $user = $request->session()->get('auth_user');
        if (!$user || $user['role'] !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $ruleName = $parkRule->rule_name;
        $ruleId = $parkRule->id;
        $parkRule->delete();

        \App\Models\ActivityLog::log(
            activityType: 'rule_deleted',
            title: 'Park Rule Deleted',
            description: "Admin deleted park rule: {$ruleName}",
            reservationId: null,
            actorName: $user['name'] ?? 'Admin User',
            actorRole: $user['role'] ?? 'admin',
            staffId: isset($user['id']) ? (string) $user['id'] : null,
            metadata: ['rule_id' => $ruleId, 'rule_name' => $ruleName]
        );

        return response()->json([
            'success' => true,
            'message' => 'Park rule deleted successfully.',
        ]);
    })->name('settings.rules.delete');

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

    Route::get('/api/recent-activities', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $type = $request->query('type');
        $search = $request->query('search');

        $query = ActivityLog::query()->orderByDesc('created_at');

        if ($type && $type !== 'all') {
            if ($type === 'extensions') {
                $query->whereIn('activity_type', ['stay_extended', 'amenity_extended']);
            } elseif ($type === 'checkins_outs') {
                $query->whereIn('activity_type', ['check_in', 'check_out', 'amenity_checked_out']);
            } elseif ($type === 'amenities') {
                $query->whereIn('activity_type', ['amenity_added', 'amenity_extended', 'amenity_checked_out']);
            } elseif ($type === 'staff') {
                $query->whereIn('activity_type', ['staff_created', 'staff_updated', 'staff_banned', 'staff_unbanned', 'staff_deleted']);
            } else {
                $query->where('activity_type', $type);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('actor_name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('reservation_id', 'like', "%{$search}%");
            });
        }

        $activities = $query->take(30)->get();

        return response()->json([
            'activities' => $activities->map(function ($act) {
                return [
                    'id' => $act->id,
                    'type' => $act->activity_type,
                    'title' => $act->title,
                    'description' => $act->description,
                    'reservation_id' => $act->reservation_id,
                    'actor_name' => $act->actor_name,
                    'actor_role' => $act->actor_role,
                    'staff_id' => $act->staff_id,
                    'created_at_human' => $act->created_at ? $act->created_at->diffForHumans() : 'Recently',
                    'created_at_formatted' => $act->created_at ? $act->created_at->format('M d, Y · g:i A') : '',
                ];
            }),
        ]);
    })->name('api.recent-activities');
});

// Shared Activity Log Notifications API for Admin & Staff (Per-Account Scoped)
Route::get('/api/activity-notifications', function (Request $request) {
    $user = $request->session()->get('auth_user');
    if (! $user || ! in_array($user['role'] ?? '', ['admin', 'staff'])) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $userRole = $user['role'] ?? 'staff';
    $userId = (int) ($user['id'] ?? 0);
    $dbLastSeenId = \App\Models\UserActivityRead::getLastSeenId($userRole, $userId);

    $queryLastSeenId = (int) $request->query('last_seen_id', 0);
    $effectiveLastSeenId = max($dbLastSeenId, $queryLastSeenId);

    $clientLatestId = (int) $request->query('latest_id', 0);
    $sinceId = (int) $request->query('since_id', 0);
    $checkOnly = $request->boolean('check_only');
    $limit = min(50, max(5, (int) $request->query('limit', 25)));

    $latestId = \App\Models\ActivityLog::max('id') ?? 0;
    
    // Calculate unread count specifically for this logged-in account
    $unreadCount = \App\Models\ActivityLog::where('id', '>', $effectiveLastSeenId)->count();

    // Fast check-only heartbeat
    if ($checkOnly) {
        $hasNew = $clientLatestId > 0 ? ($latestId > $clientLatestId) : false;
        return response()->json([
            'has_new' => $hasNew,
            'latest_id' => $latestId,
            'unread_count' => $unreadCount,
            'last_seen_id' => $effectiveLastSeenId,
        ]);
    }

    // Fetch activities
    $query = \App\Models\ActivityLog::query()->orderByDesc('id');
    if ($sinceId > 0) {
        $query->where('id', '>', $sinceId);
    } else {
        $query->take($limit);
    }
    $activities = $query->get();

    return response()->json([
        'has_new' => true,
        'latest_id' => $latestId,
        'unread_count' => $unreadCount,
        'last_seen_id' => $effectiveLastSeenId,
        'activities' => $activities->map(function ($act) use ($effectiveLastSeenId) {
            return [
                'id' => $act->id,
                'type' => $act->activity_type,
                'title' => $act->title,
                'description' => $act->description,
                'reservation_id' => $act->reservation_id,
                'actor_name' => $act->actor_name,
                'actor_role' => $act->actor_role,
                'staff_id' => $act->staff_id,
                'is_new' => ($act->id > $effectiveLastSeenId),
                'created_at_human' => $act->created_at ? $act->created_at->diffForHumans() : 'Recently',
                'created_at_formatted' => $act->created_at ? $act->created_at->format('M d, Y · g:i A') : '',
                'created_at_timestamp' => $act->created_at ? $act->created_at->timestamp : 0,
            ];
        }),
    ]);
})->name('api.activity-notifications');

// Fetch All Activity Logs with Search & Date Filters for Modal
Route::get('/api/activity-notifications/all', function (Request $request) {
    $user = $request->session()->get('auth_user');
    if (! $user || ! in_array($user['role'] ?? '', ['admin', 'staff'])) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $userRole = $user['role'] ?? 'staff';
    $userId = (int) ($user['id'] ?? 0);
    $dbLastSeenId = \App\Models\UserActivityRead::getLastSeenId($userRole, $userId);

    $queryLastSeenId = (int) $request->query('last_seen_id', 0);
    $effectiveLastSeenId = max($dbLastSeenId, $queryLastSeenId);

    $search = trim((string) $request->query('search', ''));
    $startDate = $request->query('start_date');
    $endDate = $request->query('end_date');
    $type = $request->query('type');

    $query = \App\Models\ActivityLog::query()->orderByDesc('id');

    if ($search !== '') {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('actor_name', 'like', "%{$search}%")
              ->orWhere('activity_type', 'like', "%{$search}%");
            if (is_numeric($search)) {
                $q->orWhere('reservation_id', (int) $search);
            }
        });
    }

    if (! empty($type) && $type !== 'all') {
        if ($type === 'check_in') {
            $query->whereIn('activity_type', ['check_in', 'checked_in']);
        } elseif ($type === 'check_out') {
            $query->whereIn('activity_type', ['check_out', 'amenity_checked_out']);
        } elseif ($type === 'amenities') {
            $query->where(function ($q) {
                $q->where('activity_type', 'like', '%amenity%')
                  ->orWhere('activity_type', 'stay_extended');
            });
        } elseif ($type === 'rules') {
            $query->where('activity_type', 'like', 'rule_%');
        } elseif ($type === 'staff') {
            $query->where('activity_type', 'like', 'staff_%');
        } else {
            $query->where('activity_type', $type);
        }
    }

    if (! empty($startDate)) {
        $query->whereDate('created_at', '>=', $startDate);
    }
    if (! empty($endDate)) {
        $query->whereDate('created_at', '<=', $endDate);
    }

    $activities = $query->take(300)->get();

    return response()->json([
        'total' => $activities->count(),
        'activities' => $activities->map(function ($act) use ($effectiveLastSeenId) {
            return [
                'id' => $act->id,
                'type' => $act->activity_type,
                'title' => $act->title,
                'description' => $act->description,
                'reservation_id' => $act->reservation_id,
                'actor_name' => $act->actor_name,
                'actor_role' => $act->actor_role,
                'staff_id' => $act->staff_id,
                'is_new' => ($act->id > $effectiveLastSeenId),
                'created_at_human' => $act->created_at ? $act->created_at->diffForHumans() : 'Recently',
                'created_at_formatted' => $act->created_at ? $act->created_at->format('M d, Y · g:i A') : '',
                'created_at_full' => $act->created_at ? $act->created_at->format('l, F j, Y \a\t g:i A') : '',
                'created_at_date' => $act->created_at ? $act->created_at->format('Y-m-d') : '',
                'created_at_timestamp' => $act->created_at ? $act->created_at->timestamp : 0,
            ];
        }),
    ]);
})->name('api.activity-notifications.all');

// Fetch Single Activity Log Details
Route::get('/api/activity-notifications/{id}', function (Request $request, $id) {
    $user = $request->session()->get('auth_user');
    if (! $user || ! in_array($user['role'] ?? '', ['admin', 'staff'])) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $userRole = $user['role'] ?? 'staff';
    $userId = (int) ($user['id'] ?? 0);
    $dbLastSeenId = \App\Models\UserActivityRead::getLastSeenId($userRole, $userId);

    $act = \App\Models\ActivityLog::find((int) $id);
    if (! $act) {
        return response()->json(['message' => 'Activity not found'], 404);
    }

    return response()->json([
        'activity' => [
            'id' => $act->id,
            'type' => $act->activity_type,
            'title' => $act->title,
            'description' => $act->description,
            'reservation_id' => $act->reservation_id,
            'actor_name' => $act->actor_name,
            'actor_role' => $act->actor_role,
            'staff_id' => $act->staff_id,
            'is_new' => ($act->id > $dbLastSeenId),
            'metadata' => $act->metadata,
            'created_at_human' => $act->created_at ? $act->created_at->diffForHumans() : 'Recently',
            'created_at_formatted' => $act->created_at ? $act->created_at->format('M d, Y · g:i A') : '',
            'created_at_full' => $act->created_at ? $act->created_at->format('l, F j, Y \a\t g:i A') : '',
            'created_at_date' => $act->created_at ? $act->created_at->format('Y-m-d') : '',
            'created_at_timestamp' => $act->created_at ? $act->created_at->timestamp : 0,
        ]
    ]);
})->name('api.activity-notifications.detail');

// Mark Activity Logs as Read specifically for the authenticated Account
Route::post('/api/activity-notifications/mark-read', function (Request $request) {
    $user = $request->session()->get('auth_user');
    if (! $user || ! in_array($user['role'] ?? '', ['admin', 'staff'])) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $lastSeenId = (int) $request->input('last_seen_id', 0);
    if ($lastSeenId <= 0) {
        $lastSeenId = \App\Models\ActivityLog::max('id') ?? 0;
    }

    $userRole = $user['role'] ?? 'staff';
    $userId = (int) ($user['id'] ?? 0);

    \App\Models\UserActivityRead::setLastSeenId($userRole, $userId, $lastSeenId);

    return response()->json([
        'success' => true,
        'last_seen_id' => $lastSeenId,
        'unread_count' => 0,
    ]);
})->name('api.activity-notifications.mark-read');

// Real-time Event Stream (Server-Sent Events) - waits and pushes ONLY when new activity logs are added
Route::get('/api/activity-notifications/stream', function (Request $request) {
    $user = $request->session()->get('auth_user');
    if (! $user || ! in_array($user['role'] ?? '', ['admin', 'staff'])) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $userRole = $user['role'] ?? 'staff';
    $userId = (int) ($user['id'] ?? 0);
    $dbLastSeenId = \App\Models\UserActivityRead::getLastSeenId($userRole, $userId);

    $initialLatestId = (int) $request->query('latest_id', 0);

    // Save session to release lock so standard browsing/AJAX requests are never blocked
    $request->session()->save();

    return response()->stream(function () use ($initialLatestId, $dbLastSeenId) {
        if (ob_get_level() > 0) ob_end_flush();
        flush();

        $currentLatestId = $initialLatestId > 0 ? $initialLatestId : (\App\Models\ActivityLog::max('id') ?? 0);
        $startTime = time();
        $timeout = 25; // 25s streaming connection per cycle

        while ((time() - $startTime) < $timeout) {
            if (connection_aborted()) {
                break;
            }

            $maxId = \App\Models\ActivityLog::max('id') ?? 0;

            if ($maxId > $currentLatestId) {
                $newLogs = \App\Models\ActivityLog::where('id', '>', $currentLatestId)
                    ->orderBy('id', 'asc')
                    ->get();

                $currentLatestId = $maxId;

                foreach ($newLogs as $act) {
                    $payload = [
                        'id' => $act->id,
                        'type' => $act->activity_type,
                        'title' => $act->title,
                        'description' => $act->description,
                        'reservation_id' => $act->reservation_id,
                        'actor_name' => $act->actor_name,
                        'actor_role' => $act->actor_role,
                        'staff_id' => $act->staff_id,
                        'is_new' => true,
                        'created_at_human' => $act->created_at ? $act->created_at->diffForHumans() : 'Recently',
                        'created_at_formatted' => $act->created_at ? $act->created_at->format('M d, Y · g:i A') : '',
                        'created_at_timestamp' => $act->created_at ? $act->created_at->timestamp : 0,
                    ];

                    echo "event: new_activity\n";
                    echo "data: " . json_encode($payload) . "\n\n";
                    flush();
                }
            }

            usleep(750000); // 0.75s sleep within persistent server connection
        }

        echo ": keepalive\n\n";
        flush();
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache, no-transform',
        'Connection' => 'keep-alive',
        'X-Accel-Buffering' => 'no',
    ]);
})->name('api.activity-notifications.stream');

Route::prefix('staff')->name('staff.')->group(function () use ($isAmenitySlotTaken, $isAmenityRangeTaken, $calculateContinuousSlotsCount, $continuousSlotTimeline, $getReservationAmenityTimeline, $amenityCheckoutAt, $amenityContinuousCheckoutAt, $reservationCheckoutAt, $computeReservationCheckoutAt, $formatLocalDate) {
    Route::get('/dashboard', function (Request $request) use ($computeReservationCheckoutAt) {
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
            ->where('payment_status', '!=', 'Unpaid')
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

        // Calculate due checkouts for dashboard alert.
        // Reference is ONLY the reservation's own expected checkout
        // (master stay schedule) — amenity checkouts never trigger the alert.
        $dashboardGuestsDue = 0;
        $dashboardResDue = 0;

        $activeReservationsDashboard = Reservation::query()
            ->with(['reservationGuests'])
            ->whereNotNull('check_in')
            ->where('status', 'Checked In')
            ->get();

        foreach ($activeReservationsDashboard as $res) {
            $coAt = $computeReservationCheckoutAt($res);
            if ($coAt && \Carbon\Carbon::parse($coAt)->isPast()) {
                $dashboardResDue++;
                $dashboardGuestsDue += $res->reservationGuests->whereNull('checked_out_at')->count();
            }
        }

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
            'todayArrivals',
            'dashboardGuestsDue',
            'dashboardResDue'
        ));
    })->name('dashboard');

    Route::get('/reservations', function (Request $request) use ($computeReservationCheckoutAt, $formatLocalDate) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $reservations = Reservation::query()
            ->with(['reservationAmenities.amenity', 'reservationGuests.customer'])
            ->where('reservation_type', 'online')
            ->where('payment_status', '!=', 'Unpaid')
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

        $allAmenities = Amenity::where('status', true)->orderBy('amenities_name')->get();

        $reservationData = $reservations->mapWithKeys(function ($reservation) use ($computeReservationCheckoutAt, $formatLocalDate) {
            // Extract unique time slots from reservation amenities
            $timeSlots = $reservation->reservationAmenities
                ->pluck('pricing_type')
                ->map(function ($pricingType) {
                    // Normalize pricing type to base slot (remove Aircon suffix)
                    $baseSlot = str_replace([' Aircon', 'Aircon'], '', $pricingType);
                    // Map to standard slot names
                    if (str_contains($baseSlot, 'DayToNight')) return 'DayToNight';
                    if (str_contains($baseSlot, 'NightToDay')) return 'NightToDay';
                    if (str_contains($baseSlot, 'Daytime')) return 'Daytime';
                    if (str_contains($baseSlot, 'Nighttime')) return 'Nighttime';
                    return $baseSlot;
                })
                ->unique()
                ->values()
                ->sort()
                ->toArray();

            $checkoutAt = $computeReservationCheckoutAt($reservation);

            return [$reservation->id => [
                'id' => $reservation->id,
                'booker_name' => $reservation->booker_name,
                'phone' => $reservation->phone,
                'email' => $reservation->email,
                'reservation_date' => $formatLocalDate($reservation, 'reservation_date'),
                'end_date' => $formatLocalDate($reservation, 'end_date'),
                'start_slot' => $reservation->start_slot ?? 'Daytime',
                'end_slot' => $reservation->end_slot ?? 'Daytime',
                'total_days' => $reservation->total_days ?? 1,
                'check_in' => $reservation->check_in,
                'checkout_at' => $checkoutAt?->toIso8601String(),
                'number_of_guests' => $reservation->number_of_guests,
                'status' => $reservation->status,
                'reservation_type' => $reservation->reservation_type,
                'total_amount' => $reservation->total_amount,
                'amount_paid' => $reservation->amount_paid,
                'remaining_balance' => $reservation->remaining_balance,
                'payment_status' => $reservation->payment_status,
                'time_slots' => $timeSlots,
                'reservation_amenities' => $reservation->reservationAmenities->map(function ($reservationAmenity) use ($formatLocalDate) {
                    return [
                        'id' => $reservationAmenity->id,
                        'amenity_id' => $reservationAmenity->amenity_id,
                        'pricing_type' => $reservationAmenity->pricing_type,
                        'price_at_booking' => $reservationAmenity->price_at_booking,
                        'quantity' => $reservationAmenity->quantity,
                        'remarks' => $reservationAmenity->remarks,
                        'start_date' => $formatLocalDate($reservationAmenity, 'start_date'),
                        'end_date' => $formatLocalDate($reservationAmenity, 'end_date'),
                        'start_slot' => $reservationAmenity->start_slot,
                        'end_slot' => $reservationAmenity->end_slot,
                        'day_slots_count' => $reservationAmenity->day_slots_count,
                        'night_slots_count' => $reservationAmenity->night_slots_count,
                        'amenity' => [
                            'id' => $reservationAmenity->amenity?->id,
                            'amenities_name' => $reservationAmenity->amenity?->amenities_name,
                            'daytime_price' => (float) ($reservationAmenity->amenity?->daytime_price ?? 0),
                            'nighttime_price' => (float) ($reservationAmenity->amenity?->nighttime_price ?? 0),
                            'daytime_aircon_price' => $reservationAmenity->amenity?->daytime_aircon_price !== null ? (float) $reservationAmenity->amenity->daytime_aircon_price : null,
                            'nighttime_aircon_price' => $reservationAmenity->amenity?->nighttime_aircon_price !== null ? (float) $reservationAmenity->amenity->nighttime_aircon_price : null,
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

        $pendingCount = $reservations->count();
        $todayCheckIns = Reservation::query()
            ->whereDate('check_in', now()->toDateString())
            ->where('status', 'Checked In')
            ->count();
        $expectedGuests = Reservation::query()
            ->whereDate('reservation_date', now()->toDateString())
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->sum('number_of_guests');

        return view('staff.staff_reservations', compact(
            'reservations',
            'reservationData',
            'pendingCount',
            'todayCheckIns',
            'expectedGuests',
            'allAmenities'
        ));
    })->name('reservations');

    Route::get('/occupancy-monitor', function (Request $request) use ($getReservationAmenityTimeline) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $amenities = \App\Models\Amenity::where('status', true)
            ->orderBy('amenities_name')
            ->get();

        $today = now()->toDateString();

        // Fetch reservations relevant to today's occupancy
        // Include: Checked In (active stays on site)
        // Include: Pending or Confirmed (reserved stays that overlap with today)
        // Exclude: Cancelled, Checked Out
        $reservations = \App\Models\Reservation::query()
            ->whereNotIn('status', ['Cancelled', 'Checked Out', 'cancelled', 'checked out', 'checked_out', 'checked-out'])
            ->where(function ($query) use ($today) {
                $query->whereIn('status', ['Checked In', 'checked in', 'checked_in', 'Active', 'active'])
                      ->orWhere(function ($q) use ($today) {
                          $q->whereIn('status', ['Pending', 'Confirmed', 'pending', 'confirmed'])
                            ->where(function ($dateQ) use ($today) {
                                $dateQ->where(function ($dSub) use ($today) {
                                    $dSub->whereDate('reservation_date', '<=', $today)
                                         ->where(function ($endQ) use ($today) {
                                             $endQ->whereNull('end_date')
                                                  ->whereDate('reservation_date', '>=', \Illuminate\Support\Carbon::parse($today)->subDays(2)->toDateString())
                                                  ->orWhereDate('end_date', '>=', $today);
                                         });
                                })
                                ->orWhereHas('reservationAmenities', function ($raQ) use ($today) {
                                    $raQ->where(function ($sq) {
                                        $sq->whereNull('status')
                                           ->orWhere('status', '!=', 'Completed');
                                    })
                                    ->whereNotNull('start_date')
                                    ->whereDate('start_date', '<=', $today)
                                    ->where(function ($sub) use ($today) {
                                        $sub->whereNull('end_date')
                                            ->whereDate('start_date', '>=', \Illuminate\Support\Carbon::parse($today)->subDays(2)->toDateString())
                                            ->orWhereDate('end_date', '>=', $today);
                                    });
                                });
                            });
                      });
            })
            ->with(['reservationAmenities' => function ($query) {
                $query->with('amenity');
            }, 'reservationGuests'])
            ->get();

        // Build occupancy data for each amenity
        $occupancyData = [];
        foreach ($amenities as $amenity) {
            $occupancyData[$amenity->id] = [
                'occupied' => [],
                'reserved' => [],
            ];

            foreach ($reservations as $reservation) {
                $uniqueAmenitiesCount = $reservation->reservationAmenities->pluck('amenity_id')->unique()->count();
                $isSharedGroup = $uniqueAmenitiesCount > 1;

                foreach ($reservation->reservationAmenities as $ra) {
                    if ($ra->status === 'Completed') continue;
                    if ($ra->amenity_id === $amenity->id) {
                        $timeline = $getReservationAmenityTimeline($ra, $reservation);

                        // Filter timeline for TODAY's slots only
                        $todaySlots = [];
                        foreach ($timeline as [$d, $s]) {
                            if ($d === $today) {
                                $todaySlots[] = $s;
                            }
                        }

                        if (empty($todaySlots)) {
                            continue;
                        }

                        $hasDay = in_array('Daytime', $todaySlots);
                        $hasNight = in_array('Nighttime', $todaySlots);

                        if ($hasDay && $hasNight) {
                            $timeSlot = 'DayToNight';
                            $timeSlotLabel = 'Day & Night';
                        } elseif ($hasDay) {
                            $timeSlot = 'Daytime';
                            $timeSlotLabel = 'Daytime';
                        } else {
                            $timeSlot = 'Nighttime';
                            $timeSlotLabel = 'Nighttime';
                        }

                        if (str_contains((string) $ra->pricing_type, 'Continuous Stay')) {
                            $timeSlotLabel = "Continuous Stay ({$timeSlotLabel})";
                        }

                        $entry = [
                            'reservation_id' => $reservation->id,
                            'time_slot' => $timeSlot,
                            'time_slot_label' => $timeSlotLabel,
                            'today_slots' => array_map('strtolower', $todaySlots),
                            'status' => $reservation->status,
                            // Headcount of guests (main + companions) still inside
                            'guest_count' => $reservation->reservationGuests->whereNull('checked_out_at')->count(),
                            'is_shared_group' => $isSharedGroup,
                            'total_amenities_count' => $uniqueAmenitiesCount,
                        ];

                        if ($reservation->status === 'Checked In') {
                            $occupancyData[$amenity->id]['occupied'][] = $entry;
                        } elseif (in_array($reservation->status, ['Pending', 'Confirmed'])) {
                            $occupancyData[$amenity->id]['reserved'][] = $entry;
                        }
                    }
                }
            }
        }

        // Aggregate occupancy stats for the KPI strip
        $occupiedCount = 0;
        $reservedCount = 0;
        $availableCount = 0;
        $occupiedReservations = 0;
        foreach ($occupancyData as $data) {
            if (! empty($data['occupied'])) {
                $occupiedCount++;
                $occupiedReservations += count($data['occupied']);
            }
            if (! empty($data['reserved'])) {
                $reservedCount++;
            }
            if (empty($data['occupied']) && empty($data['reserved'])) {
                $availableCount++;
            }
        }
        $totalAmenities = $amenities->count();
        $inUseCount = $occupiedCount + $reservedCount;
        $occupancyRate = $totalAmenities > 0 ? (int) round($inUseCount / $totalAmenities * 100) : 0;

        // Visitors: checked-in guests (main + companions, still inside) whose
        // reservation availed no amenity at all.
        $visitorCount = $reservations
            ->filter(fn ($res) => $res->reservationAmenities->isEmpty())
            ->sum(fn ($res) => $res->reservationGuests->whereNull('checked_out_at')->count());

        return view('staff.staff_occupancy_monitor', compact(
            'amenities',
            'occupancyData',
            'totalAmenities',
            'occupiedCount',
            'reservedCount',
            'availableCount',
            'occupiedReservations',
            'inUseCount',
            'occupancyRate',
            'visitorCount'
        ));
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
                'number_of_guests' => $reservation->number_of_guests,
                'reservation_guests' => $reservation->reservationGuests->map(function ($guest) {
                    $c = $guest->customer;
                    return [
                        'id' => $guest->id,
                        'is_primary_guest' => (bool)$guest->is_primary_guest,
                        'checked_out_at' => $guest->checked_out_at,
                        'customer' => [
                            'first_name' => $c?->first_name,
                            'middle_name' => $c?->middle_name,
                            'last_name' => $c?->last_name,
                            'gender' => $c?->gender,
                            'is_foreigner' => $c?->is_foreigner,
                            'age' => $c?->age,
                            'customer_type' => $c?->customer_type
                        ]
                    ];
                })
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
        $totalGuests = $reservations->sum('number_of_guests');
        $averageSpend = $totalReservations > 0 ? $totalRevenue / $totalReservations : 0;

        // Revenue + booking counts for the last 6 months (oldest first).
        $monthlyLabels = [];
        $monthlyRevenue = [];
        $monthlyCounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->startOfMonth()->subMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            $monthlyLabels[] = $monthStart->format('M');
            $inMonth = $reservations->filter(function ($r) use ($monthStart, $monthEnd) {
                $d = $r->reservation_date ? \Illuminate\Support\Carbon::parse($r->reservation_date) : null;
                return $d && $d >= $monthStart && $d <= $monthEnd;
            });
            $monthlyRevenue[] = (float) $inMonth->sum('total_amount');
            $monthlyCounts[] = $inMonth->count();
        }

        $reportStatusCounts = $reportRows->groupBy('status')->map->count();
        $reportPaymentCounts = $reportRows->groupBy('payment_status')->map->count();

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
            'totalRevenue',
            'totalGuests',
            'averageSpend',
            'monthlyLabels',
            'monthlyRevenue',
            'monthlyCounts',
            'reportStatusCounts',
            'reportPaymentCounts'
        ));
    })->name('reports');

    Route::get('/check-ins', function (Request $request) use ($amenityCheckoutAt, $amenityContinuousCheckoutAt, $reservationCheckoutAt, $computeReservationCheckoutAt, $isAmenitySlotTaken, $formatLocalDate) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $customers = Customer::whereHas('reservationGuests', function ($q) {
            $q->whereNull('checked_out_at')
              ->whereHas('reservation', function ($resQuery) {
                  $resQuery->where('status', 'Checked In')
                           ->whereNotNull('check_in');
              });
        })->with(['reservationGuests' => function ($query) {
            $query->whereNull('checked_out_at')
                  ->whereHas('reservation', function ($resQuery) {
                      $resQuery->where('status', 'Checked In')
                               ->whereNotNull('check_in');
                  })
                  ->with([
                      'reservation' => function ($reservationQuery) {
                          $reservationQuery->with(['reservationAmenities.amenity', 'entranceFee', 'reservationGuests.customer']);
                      },
                      'customer',
                  ]);
        }])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $activeReservations = Reservation::whereNotNull('check_in')
            ->where('status', 'Checked In')
            ->where(function ($query) {
                $query->whereNull('check_out')
                      ->orWhereHas('reservationGuests', function ($q) {
                          $q->whereNull('checked_out_at');
                      });
            })
            ->with(['reservationGuests' => function ($query) {
                $query->with('customer');
            }, 'reservationAmenities.amenity', 'entranceFee'])
            ->orderBy('check_in', 'desc')
            ->get();

        // Time slots that drive the checkout timer. With amenities the amenity
        // rows win; without them the entrance fee's time period is used.
        $reservationTimeSlots = function ($reservation) {
            $slots = $reservation->reservationAmenities
                ->map(fn ($ra) => str_replace([' Aircon', 'Aircon'], '', (string) $ra->pricing_type))
                ->unique()
                ->values()
                ->toArray();

            if (empty($slots) && $reservation->entranceFee?->pricing_type) {
                $slots = [$reservation->entranceFee->pricing_type];
            }

            return $slots;
        };

        // Build a lookup map of all active reservations for the check-in modal.
        // Keep in sync with the reservation count update above in the check-in
        // handler (a GET request must never mutate data).
        $reservationData = $activeReservations->mapWithKeys(function ($reservation) use ($amenityCheckoutAt, $amenityContinuousCheckoutAt, $computeReservationCheckoutAt, $formatLocalDate) {
            $primaryGuest = $reservation->reservationGuests->firstWhere('is_primary_guest', true);
            $primaryCustomer = $primaryGuest?->customer;

            $checkoutAt = $computeReservationCheckoutAt($reservation);

            return [$reservation->id => [
                'id' => $reservation->id,
                'booker_name' => $reservation->booker_name,
                'check_in' => $reservation->check_in,
                'check_out' => $reservation->check_out,
                'reservation_date' => $formatLocalDate($reservation, 'reservation_date'),
                'end_date' => $formatLocalDate($reservation, 'end_date'),
                'start_slot' => $reservation->start_slot ?? 'Daytime',
                'end_slot' => $reservation->end_slot ?? 'Daytime',
                'total_days' => $reservation->total_days ?? 1,
                'checkout_at' => $checkoutAt?->toIso8601String(),
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
                        'has_pool_access' => (bool) $guestEntry->has_pool_access,
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
                'reservation_amenities' => $reservation->reservationAmenities->map(function ($amenity) use ($amenityCheckoutAt, $amenityContinuousCheckoutAt, $reservation, $formatLocalDate) {
                    $raStartDate = $formatLocalDate($amenity, 'start_date') ?: $formatLocalDate($reservation, 'reservation_date');
                    $raEndDate = $formatLocalDate($amenity, 'end_date') ?: ($formatLocalDate($reservation, 'end_date') ?: $raStartDate);
                    $raStartSlot = $amenity->start_slot ?: ($reservation->start_slot ?: 'Daytime');
                    $raEndSlot = $amenity->end_slot ?: ($reservation->end_slot ?: $amenity->pricing_type ?: 'Daytime');

                    if ($amenity->start_date || $amenity->end_date || $reservation->end_date || ($reservation->total_days && $reservation->total_days > 1)) {
                        $amCheckoutAt = $amenityContinuousCheckoutAt($raEndDate, $raEndSlot);
                    } else {
                        $amCheckoutAt = $amenityCheckoutAt($raStartDate, $amenity->pricing_type ?: $raEndSlot);
                    }

                    return [
                        'id' => $amenity->id,
                        'amenity_name' => $amenity->amenity?->amenities_name ?? ($amenity->amenity_id ?? 'Unknown'),
                        'pricing_type' => $amenity->pricing_type,
                        'price' => $amenity->price_at_booking ?? 0,
                        'quantity' => $amenity->quantity ?? 1,
                        'amenity_id' => $amenity->amenity_id,
                        'status' => $amenity->status ?? 'Active',
                        'start_date' => $raStartDate,
                        'end_date' => $raEndDate,
                        'start_slot' => $raStartSlot,
                        'end_slot' => $raEndSlot,
                        'day_slots_count' => $amenity->day_slots_count,
                        'night_slots_count' => $amenity->night_slots_count,
                        'checkout_at' => $amCheckoutAt?->toIso8601String(),
                    ];
                })->values(),
                'entrance_fee' => $reservation->entranceFee ? [
                    'pricing_type' => $reservation->entranceFee->pricing_type,
                    'total_amount' => (float) $reservation->entranceFee->total_amount,
                    'pool_fee' => (float) $reservation->entranceFee->pool_fee,
                    'adult_count' => $reservation->entranceFee->adult_count,
                    'child_count' => $reservation->entranceFee->child_count,
                ] : null,
            ]];
        });

        $amenities = Amenity::where('status', true)
            ->orderBy('amenities_name')
            ->get();

        // Amenities with NO reservation today are the only ones the walk-in
        // picker may offer.
        $today = now()->toDateString();
        $availableAmenityIds = $amenities
            ->filter(fn ($amenity) => ! $isAmenitySlotTaken($amenity->id, $today, 'Daytime') && ! $isAmenitySlotTaken($amenity->id, $today, 'Nighttime'))
            ->pluck('id')
            ->all();

        // Current session (daytime vs nighttime) limits the pickable periods.
        $settings = \App\Models\ParkSetting::first();
        $daytimeStart = $settings ? strtotime((string) ($settings->daytime_start ?? '06:00')) : strtotime('06:00');
        $daytimeEnd = $settings ? strtotime((string) ($settings->daytime_end ?? '18:00')) : strtotime('18:00');
        $nowSeconds = strtotime(now()->format('H:i'));
        $currentPeriod = ($nowSeconds >= $daytimeStart && $nowSeconds < $daytimeEnd) ? 'daytime' : 'nighttime';
        $currentSlotName = $currentPeriod === 'nighttime' ? 'Nighttime' : 'Daytime';

        // Amenities occupied today for the current session (cannot be added mid-stay today)
        $occupiedTodayAmenityIds = $amenities
            ->filter(fn ($amenity) => $isAmenitySlotTaken($amenity->id, $today, $currentSlotName))
            ->pluck('id')
            ->all();

        $guestData = $customers->mapWithKeys(function ($customer) use ($computeReservationCheckoutAt, $formatLocalDate) {
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
                'reservation_guests' => $customer->reservationGuests->map(function ($reservationGuest) use ($computeReservationCheckoutAt, $formatLocalDate) {
                    return [
                        'id' => $reservationGuest->id,
                        'checked_out_at' => $reservationGuest->checked_out_at,
                        'is_primary_guest' => (bool) $reservationGuest->is_primary_guest,
                        'has_pool_access' => (bool) $reservationGuest->has_pool_access,
                        'reservation' => $reservationGuest->reservation ? [
                            'id' => $reservationGuest->reservation->id,
                            'reservation_type' => $reservationGuest->reservation->reservation_type,
                            'status' => $reservationGuest->reservation->status,
                            'check_in' => $reservationGuest->reservation->check_in,
                            'booker_name' => $reservationGuest->reservation->booker_name,
                            'reservation_date' => $formatLocalDate($reservationGuest->reservation, 'reservation_date'),
                            'end_date' => $formatLocalDate($reservationGuest->reservation, 'end_date'),
                            'start_slot' => $reservationGuest->reservation->start_slot ?? 'Daytime',
                            'end_slot' => $reservationGuest->reservation->end_slot ?? 'Daytime',
                            'total_days' => $reservationGuest->reservation->total_days ?? 1,
                            'checkout_at' => $computeReservationCheckoutAt($reservationGuest->reservation)?->toIso8601String(),
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
                                    'has_pool_access' => (bool) $guestEntry->has_pool_access,
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

        return view('staff.staff_check_ins', compact('customers', 'guestData', 'amenities', 'activeReservations', 'reservationData', 'availableAmenityIds', 'occupiedTodayAmenityIds', 'currentPeriod', 'currentSlotName'));
    })->name('checkins');

    Route::get('/check-ins/lookup', function (Request $request) use ($amenityCheckoutAt, $amenityContinuousCheckoutAt, $computeReservationCheckoutAt, $formatLocalDate) {
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
                'reservation_date' => $formatLocalDate($reservation, 'reservation_date'),
                'end_date' => $formatLocalDate($reservation, 'end_date'),
                'start_slot' => $reservation->start_slot ?? 'Daytime',
                'end_slot' => $reservation->end_slot ?? 'Daytime',
                'total_days' => $reservation->total_days ?? 1,
                'check_in' => $reservation->check_in,
                'check_out' => $reservation->check_out,
                'checkout_at' => $computeReservationCheckoutAt($reservation)?->toIso8601String(),
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
                        'has_pool_access' => (bool) $guestEntry->has_pool_access,
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
                'reservation_amenities' => $reservation->reservationAmenities->map(function ($amenity) use ($amenityCheckoutAt, $amenityContinuousCheckoutAt, $reservation, $formatLocalDate) {
                    $raStartDate = $formatLocalDate($amenity, 'start_date') ?: $formatLocalDate($reservation, 'reservation_date');
                    $raEndDate = $formatLocalDate($amenity, 'end_date') ?: ($formatLocalDate($reservation, 'end_date') ?: $raStartDate);
                    $raStartSlot = $amenity->start_slot ?: ($reservation->start_slot ?: 'Daytime');
                    $raEndSlot = $amenity->end_slot ?: ($reservation->end_slot ?: $amenity->pricing_type ?: 'Daytime');

                    if ($amenity->start_date || $amenity->end_date || $reservation->end_date || ($reservation->total_days && $reservation->total_days > 1)) {
                        $amCheckoutAt = $amenityContinuousCheckoutAt($raEndDate, $raEndSlot);
                    } else {
                        $amCheckoutAt = $amenityCheckoutAt($raStartDate, $amenity->pricing_type ?: $raEndSlot);
                    }

                    return [
                        'id' => $amenity->id,
                        'amenity_name' => $amenity->amenity?->amenities_name,
                        'pricing_type' => $amenity->pricing_type,
                        'price_at_booking' => $amenity->price_at_booking,
                        'quantity' => $amenity->quantity,
                        'start_date' => $raStartDate,
                        'end_date' => $raEndDate,
                        'start_slot' => $raStartSlot,
                        'end_slot' => $raEndSlot,
                        'day_slots_count' => $amenity->day_slots_count,
                        'night_slots_count' => $amenity->night_slots_count,
                        'checkout_at' => $amCheckoutAt?->toIso8601String(),
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

        // ── Bulk companion grouping ──────────────────────────────────────────
        // Bulk companions are stored as one customer row each, but the records
        // page shows them merged by their bulk group (same reservation + name +
        // age + gender + nationality). A group's quantity grows as more members
        // check out: a 2x record becomes 3x once the last one leaves.
        $isBulkCompanionName = function (?string $name): bool {
            $name = strtolower(trim((string) $name));
            return str_starts_with($name, 'bulk') || str_contains($name, 'companion');
        };

        // Bulk companions are stored with a midpoint age (6/15/30/65); map it
        // back to the age group the staff picked at check-in.
        $bulkAgeGroupLabel = function ($age): string {
            if ($age === null || $age === '') return 'N/A';
            if (! is_numeric($age)) return (string) $age;
            $age = (int) $age;
            if ($age <= 12) return '0-12';
            if ($age <= 17) return '13-17';
            if ($age <= 59) return '18-59';
            return '60+';
        };

        $bulkGroupMembers = [];
        $regularGuestEntries = collect();

        foreach ($checkedOutGuests as $rg) {
            $customer = $rg->customer;
            if ($customer && $isBulkCompanionName($customer->first_name)) {
                $ageGroup = $bulkAgeGroupLabel($customer->age);
                $gender = strtolower((string) ($customer->gender ?? 'N/A'));
                $nationality = $customer->is_foreigner ? 'Foreigner' : 'Filipino';
                $groupKey = "{$rg->reservation_id}|{$ageGroup}|{$gender}|{$nationality}";
                $bulkGroupMembers[$groupKey][] = $rg;
            } else {
                $regularGuestEntries->push($rg);
            }
        }

        // Normalize a raw datetime (the model returns strings, not Carbon)
        // into an ISO-ish string for sorting and JSON.
        $toDateTimeString = function ($value): ?string {
            if ($value === null || $value === '') return null;
            return \Carbon\Carbon::parse($value)->toDateTimeString();
        };

        $bulkGroups = collect($bulkGroupMembers)->map(function (array $members, string $key) use ($bulkAgeGroupLabel, $toDateTimeString) {
            $first = $members[0];
            $customer = $first->customer;
            $sorted = collect($members)->sortByDesc(fn ($m) => $m->checked_out_at)->values();
            $ageGroup = $bulkAgeGroupLabel($customer->age);
            $gender = $customer->gender ?? 'N/A';
            $nationality = $customer->is_foreigner ? 'Foreigner' : 'Filipino';

            return [
                'key' => $key,
                'reservation_id' => $first->reservation_id,
                'name' => 'Bulk Companions',
                'age_group' => $ageGroup,
                'gender' => $gender,
                'nationality' => $nationality,
                'status' => 'Checked Out',
                'count' => count($members),
                'checked_out_at' => $toDateTimeString($sorted->first()?->checked_out_at),
                'members' => $sorted->map(fn ($m) => [
                    'customer_id' => $m->customer_id,
                    'check_in' => $toDateTimeString($m->reservation?->check_in),
                    'checked_out_at' => $toDateTimeString($m->checked_out_at),
                ])->all(),
            ];
        })->values();

        // Table rows: regular guest entries + one merged row per bulk group,
        // ordered by most recent check-out.
        $guestRows = $regularGuestEntries
            ->map(fn ($rg) => ['type' => 'guest', 'entry' => $rg])
            ->concat($bulkGroups->map(fn ($group) => ['type' => 'bulk', 'group' => $group]))
            ->sortByDesc(fn ($row) => $row['type'] === 'bulk'
                ? (string) ($row['group']['checked_out_at'] ?? '')
                : ($row['entry']->checked_out_at ? $toDateTimeString($row['entry']->checked_out_at) : ''))
            ->values();

        $bulkGroupData = $bulkGroups->mapWithKeys(fn ($group) => [$group['key'] => $group]);

        // Get all completed / checked-out reservations
        // A reservation only appears here if:
        // 1. Its status is 'Checked Out' or check_out is set, OR
        // 2. All of its registered guests have checked out (no guest remains with checked_out_at = null)
        $checkedOutReservations = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer'])
            ->where(function ($query) {
                $query->where('status', 'Checked Out')
                    ->orWhereNotNull('check_out')
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('check_in')
                            ->whereHas('reservationGuests')
                            ->whereDoesntHave('reservationGuests', function ($q) {
                                $q->whereNull('checked_out_at');
                            });
                    });
            })
            ->whereNotIn('status', ['Pending', 'Cancelled'])
            ->orderBy('check_out', 'desc')
            ->get();

        $amenities = Amenity::where('status', true)
            ->orderBy('amenities_name')
            ->get();

        // Summary stats shown at the top of the records page.
        $guestRecordsCount = $checkedOutGuests->count();
        $completedReservationsCount = $checkedOutReservations->count();
        $completedRevenue = (float) $checkedOutReservations->sum('amount_paid');
        $uniqueGuestsCount = $checkedOutGuests->pluck('customer_id')->unique()->filter()->count();

        // ── Quick Insights (records dashboard panel) ────────────────────────
        // Average length of stay in nights across completed stays.
        $stayLengths = [];
        foreach ($checkedOutReservations as $res) {
            if ($res->check_in && $res->check_out) {
                $hours = \Carbon\Carbon::parse($res->check_out)->diffInHours(\Carbon\Carbon::parse($res->check_in));
                if ($hours > 0) {
                    $stayLengths[] = $hours / 24;
                }
            }
        }
        $avgLengthOfStay = count($stayLengths) > 0 ? round(array_sum($stayLengths) / count($stayLengths), 1) : 0;

        // Returning guests: customers who appear in 2+ checked-out reservations.
        $reservationCustomerCounts = [];
        foreach ($checkedOutGuests as $rg) {
            if (! $rg->customer_id) continue;
            $reservationCustomerCounts[$rg->customer_id] = ($reservationCustomerCounts[$rg->customer_id] ?? 0) + 1;
        }
        $returningGuests = count(array_filter($reservationCustomerCounts, fn ($count) => $count >= 2));
        $returningGuestsPct = $uniqueGuestsCount > 0 ? (int) round($returningGuests / $uniqueGuestsCount * 100) : 0;

        // Revenue per day (from check-out dates) for the mini revenue chart.
        $revenueByDate = [];
        foreach ($checkedOutReservations as $res) {
            $date = $res->check_out ? \Carbon\Carbon::parse($res->check_out)->toDateString() : ($res->created_at ? \Carbon\Carbon::parse($res->created_at)->toDateString() : null);
            if (! $date) continue;
            $revenueByDate[$date] = ($revenueByDate[$date] ?? 0) + (float) $res->amount_paid;
        }
        ksort($revenueByDate);
        $revenueSeries = array_values($revenueByDate);

        $guestData = $checkedOutGuests->mapWithKeys(function ($guest) {
            return [$guest->customer_id => [
                'id' => $guest->customer->id,
                'first_name' => $guest->customer->first_name,
                'middle_name' => $guest->customer->middle_name,
                'last_name' => $guest->customer->last_name,
                'age' => $guest->customer->age,
                'gender' => $guest->customer->gender,
                'is_foreigner' => (bool) $guest->customer->is_foreigner,
                'email' => $guest->customer->email,
                'phone' => $guest->customer->phone,
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
                                    'email' => $rg->customer->email,
                                    'phone' => $rg->customer->phone,
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
                            'email' => $guest->customer->email,
                            'phone' => $guest->customer->phone,
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

        $reservationAmounts = $checkedOutReservations->pluck('amount_paid', 'id')->toArray();

        return view('staff.staff_records', compact(
            'checkedOutGuests',
            'guestRows',
            'bulkGroupData',
            'checkedOutReservations',
            'guestData',
            'reservationData',
            'reservationAmounts',
            'amenities',
            'guestRecordsCount',
            'completedReservationsCount',
            'completedRevenue',
            'uniqueGuestsCount',
            'avgLengthOfStay',
            'returningGuests',
            'returningGuestsPct',
            'revenueSeries'
        ));
    })->name('records');

    Route::post('/check-ins/guests', function (Request $request) use ($calculateContinuousSlotsCount, $continuousSlotTimeline, $isAmenityRangeTaken, $isAmenitySlotTaken) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'guest_mode' => ['required', 'in:with_primary,visitors_only'],
            'reservation_type' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'start_slot' => ['nullable', 'in:Daytime,Nighttime'],
            'end_slot' => ['nullable', 'in:Daytime,Nighttime'],
            'total_days' => ['nullable', 'integer', 'min:1'],
            'check_in' => ['nullable', 'date'],
            'time_period' => ['nullable', 'in:daytime,nighttime,daytonight,nighttoday'],
            'pool_option' => ['nullable', 'string', 'in:no_pool,specific,all_paid,all_free'],
            'include_pool' => ['nullable'],
            'primary_guest' => ['nullable', 'array'],
            'primary_guest.first_name' => ['nullable', 'string', 'max:255'],
            'primary_guest.middle_name' => ['nullable', 'string', 'max:255'],
            'primary_guest.last_name' => ['nullable', 'string', 'max:255'],
            'primary_guest.age' => ['nullable', 'integer', 'min:0'],
            'primary_guest.gender' => ['nullable', 'in:Male,Female'],
            'primary_guest.is_foreigner' => ['nullable', 'boolean'],
            'primary_guest.phone' => ['nullable', 'string', 'max:255'],
            'primary_guest.email' => ['nullable', 'email', 'max:255'],
            'primary_guest.has_pool_access' => ['nullable'],
            'companions' => ['nullable', 'array'],
            // Bulk companions submit empty names (they only carry an age group),
            // so names must be nullable — but stay required when the other name
            // IS present for individually-added companions.
            'companions.*.first_name' => ['nullable', 'required_with:companions.*.last_name', 'string', 'max:255'],
            'companions.*.middle_name' => ['nullable', 'string', 'max:255'],
            'companions.*.last_name' => ['nullable', 'required_with:companions.*.first_name', 'string', 'max:255'],
            'companions.*.age' => ['nullable', 'integer', 'min:0'],
            'companions.*.age_group' => ['nullable', 'string', 'max:255'],
            'companions.*.gender' => ['nullable', 'in:Male,Female'],
            'companions.*.is_foreigner' => ['nullable', 'boolean'],
            'companions.*.phone' => ['nullable', 'string', 'max:255'],
            'companions.*.email' => ['nullable', 'email', 'max:255'],
            'companions.*.has_pool_access' => ['nullable'],
            'selected_amenities' => ['nullable', 'array'],
            'selected_amenities.*.amenity_id' => ['required', 'string'],
            'selected_amenities.*.start_date' => ['nullable', 'date'],
            'selected_amenities.*.end_date' => ['nullable', 'date'],
            'selected_amenities.*.start_slot' => ['nullable', 'in:Daytime,Nighttime'],
            'selected_amenities.*.end_slot' => ['nullable', 'in:Daytime,Nighttime'],
            'selected_amenities.*.pricing_type' => ['nullable', 'string'],
            'selected_amenities.*.price_at_booking' => ['nullable', 'numeric'],
            'selected_amenities.*.is_aircon' => ['nullable', 'boolean'],
            'selected_amenities.*.quantity' => ['nullable', 'integer', 'min:1'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $startDate = $data['start_date'] ?? ($data['check_in'] ?? now()->toDateString());
        $endDate = $data['end_date'] ?? $startDate;
        $startSlot = $data['start_slot'] ?? 'Daytime';
        $endSlot = $data['end_slot'] ?? $startSlot;

        $mainCounts = $calculateContinuousSlotsCount($startDate, $endDate, $startSlot, $endSlot);
        $totalDays = $mainCounts['days_span'];

        // Build timeline set for master reservation bounds
        $masterTimeline = $continuousSlotTimeline($startDate, $endDate, $startSlot, $endSlot);
        $masterKeys = [];
        foreach ($masterTimeline as [$d, $s]) {
            $masterKeys["{$d}_{$s}"] = true;
        }

        $selectedAmenities = $data['selected_amenities'] ?? [];
        $hasAmenities = count($selectedAmenities) > 0;

        $processedAmenities = [];
        $amenityTotal = 0;

        foreach ($selectedAmenities as $item) {
            $amId = $item['amenity_id'] ?? null;
            if (! $amId) continue;

            $amenity = Amenity::find($amId);
            if (! $amenity) {
                return back()->withErrors(['selected_amenities' => "Amenity not found."])->withInput();
            }

            $itemStartDate = $item['start_date'] ?? $startDate;
            $itemEndDate = $item['end_date'] ?? ($itemStartDate ?: $startDate);
            $itemStartSlot = $item['start_slot'] ?? $startSlot;
            $itemEndSlot = $item['end_slot'] ?? $endSlot;

            $itemTimeline = $continuousSlotTimeline($itemStartDate, $itemEndDate, $itemStartSlot, $itemEndSlot);
            if (empty($itemTimeline)) {
                return back()->withErrors(['selected_amenities' => "Invalid date/session range for {$amenity->amenities_name}."])->withInput();
            }

            // Boundary enforcement: check that amenity stay is fully within master reservation bounds
            foreach ($itemTimeline as [$d, $s]) {
                if (! isset($masterKeys["{$d}_{$s}"])) {
                    return back()->withErrors([
                        'selected_amenities' => "The stay range for {$amenity->amenities_name} ({$itemStartDate} {$itemStartSlot} to {$itemEndDate} {$itemEndSlot}) exceeds the walk-in reservation stay window ({$startDate} {$startSlot} to {$endDate} {$endSlot})."
                    ])->withInput();
                }
            }

            // Conflict / availability check
            if ($isAmenityRangeTaken($amId, $itemStartDate, $itemEndDate, $itemStartSlot, $itemEndSlot)) {
                return back()->withErrors([
                    'selected_amenities' => "Amenity {$amenity->amenities_name} is already booked for {$itemStartDate} {$itemStartSlot} to {$itemEndDate} {$itemEndSlot}."
                ])->withInput();
            }

            $itemCounts = $calculateContinuousSlotsCount($itemStartDate, $itemEndDate, $itemStartSlot, $itemEndSlot);
            $hasAircon = ! empty($item['is_aircon']) || str_contains((string) ($item['pricing_type'] ?? ''), 'Aircon');

            $dayPrice = $hasAircon && $amenity->daytime_aircon_price ? (float) $amenity->daytime_aircon_price : (float) ($amenity->daytime_price ?? 0);
            $nightPrice = $hasAircon && $amenity->nighttime_aircon_price ? (float) $amenity->nighttime_aircon_price : (float) ($amenity->nighttime_price ?? 0);

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $calculatedPrice = (($itemCounts['day_count'] * $dayPrice) + ($itemCounts['night_count'] * $nightPrice)) * $quantity;
            $amenityTotal += $calculatedPrice;

            $pricingType = $itemCounts['days_span'] > 1
                ? "Continuous Stay ({$itemCounts['days_span']}D)" . ($hasAircon ? ' Aircon' : '')
                : (($itemStartSlot === 'Daytime' && $itemEndSlot === 'Nighttime') ? ($hasAircon ? 'DayToNight Aircon' : 'DayToNight') : ($hasAircon ? "{$itemStartSlot} Aircon" : $itemStartSlot));

            $processedAmenities[] = [
                'amenity' => $amenity,
                'amenity_id' => $amenity->id,
                'start_date' => $itemStartDate,
                'end_date' => $itemEndDate,
                'start_slot' => $itemStartSlot,
                'end_slot' => $itemEndSlot,
                'day_slots_count' => $itemCounts['day_count'],
                'night_slots_count' => $itemCounts['night_count'],
                'pricing_type' => $pricingType,
                'price_at_booking' => $calculatedPrice,
                'quantity' => $quantity,
            ];
        }

        $primaryGuestCount = ($data['guest_mode'] === 'with_primary' && ! empty($data['primary_guest'])) ? 1 : 0;
        $companionCount = count($data['companions'] ?? []);
        $guestCount = $primaryGuestCount + $companionCount;

        // Adult/child counts from guest ages (12 and below = child).
        $adultCount = 0;
        $childCount = 0;
        if ($primaryGuestCount) {
            $primaryAge = (int) ($data['primary_guest']['age'] ?? 99);
            if ($primaryAge <= 12) {
                $childCount++;
            } else {
                $adultCount++;
            }
        }
        foreach ($data['companions'] ?? [] as $companionData) {
            // Bulk companions send an age_group (0-12, 13-17, 18-59, 60+) instead of an age
            if (($companionData['age_group'] ?? null) === '0-12') {
                $childCount++;
            } else {
                $companionAge = (int) ($companionData['age'] ?? 99);
                if ($companionAge <= 12) {
                    $childCount++;
                } else {
                    $adultCount++;
                }
            }
        }

        $settings = \App\Models\ParkSetting::first();
        $dayAdult = (float) ($settings->daytime_adult_entrance_fee ?? 0);
        $dayChild = (float) ($settings->daytime_child_entrance_fee ?? 0);
        $nightAdult = (float) ($settings->nighttime_adult_entrance_fee ?? 0);
        $nightChild = (float) ($settings->nighttime_child_entrance_fee ?? 0);

        // Effective period for entrance pricing
        $effectivePeriod = match (true) {
            $mainCounts['night_count'] > 0 && $mainCounts['day_count'] > 0 => 'daytonight',
            $mainCounts['night_count'] > 0 => 'nighttime',
            default => 'daytime',
        };

        if ($effectivePeriod === 'nighttime') {
            $adultRate = $nightAdult;
            $childRate = $nightChild;
        } elseif ($effectivePeriod === 'daytonight') {
            $adultRate = $dayAdult + $nightAdult;
            $childRate = $dayChild + $nightChild;
        } else {
            $adultRate = $dayAdult;
            $childRate = $dayChild;
        }

        $entranceTotal = ($adultCount * $adultRate) + ($childCount * $childRate);

        // Pool option & pool access determination
        $poolOption = $data['pool_option'] ?? (! empty($data['include_pool']) ? 'all_paid' : 'no_pool');

        $dayPool = (float) ($settings->day_pool_fee ?? 0);
        $nightPool = (float) ($settings->night_pool_fee ?? 0);
        if ($effectivePeriod === 'nighttime') {
            $poolRate = $nightPool;
        } elseif ($effectivePeriod === 'daytonight') {
            $poolRate = $dayPool + $nightPool;
        } else {
            $poolRate = $dayPool;
        }

        $primaryHasPool = false;
        if ($primaryGuestCount) {
            if ($poolOption === 'all_paid' || $poolOption === 'all_free') {
                $primaryHasPool = true;
            } elseif ($poolOption === 'specific') {
                $pVal = $data['primary_guest']['has_pool_access'] ?? null;
                $primaryHasPool = in_array($pVal, ['1', 1, true, 'true', 'on'], true);
            }
        }

        $poolCount = $primaryHasPool ? 1 : 0;
        $companionsWithPoolFlags = [];
        foreach ($data['companions'] ?? [] as $cIdx => $companionData) {
            $cHasPool = false;
            if ($poolOption === 'all_paid' || $poolOption === 'all_free') {
                $cHasPool = true;
            } elseif ($poolOption === 'specific') {
                $cVal = $companionData['has_pool_access'] ?? null;
                $cHasPool = in_array($cVal, ['1', 1, true, 'true', 'on'], true);
            }
            if ($cHasPool) {
                $poolCount++;
            }
            $companionsWithPoolFlags[$cIdx] = $cHasPool;
        }

        $poolFee = 0;
        if ($poolOption === 'all_paid' || $poolOption === 'specific') {
            $poolFee = round($poolCount * $poolRate, 2);
        }

        $grandTotal = round($entranceTotal + $poolFee + $amenityTotal, 2);

        $primaryFirstName = trim((string) ($data['primary_guest']['first_name'] ?? '')) ?: 'Walk-In';
        $primaryLastName = trim((string) ($data['primary_guest']['last_name'] ?? '')) ?: 'Guest';
        $bookerName = trim("{$primaryFirstName} {$primaryLastName}");

        $reservation = Reservation::create([
            'booker_name' => $bookerName,
            'phone' => $data['primary_guest']['phone'] ?? '',
            'email' => $data['primary_guest']['email'] ?? '',
            'reservation_date' => $startDate,
            'end_date' => $endDate,
            'start_slot' => $startSlot,
            'end_slot' => $endSlot,
            'total_days' => $totalDays,
            'check_in' => now(),
            'number_of_guests' => $guestCount > 0 ? $guestCount : 1,
            'reservation_type' => $data['reservation_type'] ?? 'walk_in',
            'status' => 'Checked In',
            'total_amount' => $grandTotal,
            'amount_paid' => $grandTotal,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $entrancePricingType = match ($effectivePeriod) {
            'nighttime' => 'Nighttime',
            'daytonight' => 'DayToNight',
            default => 'Daytime',
        };

        \App\Models\ReservationEntranceFee::create([
            'reservation_id' => $reservation->id,
            'pricing_type' => $entrancePricingType,
            'pool_option' => $poolOption,
            'total_amount' => round($entranceTotal + $poolFee, 2),
            'pool_fee' => round($poolFee, 2),
            'pool_access_count' => $poolCount,
            'adult_count' => $adultCount,
            'child_count' => $childCount,
        ]);

        $primaryCustomer = null;
        if ($data['guest_mode'] === 'with_primary') {
            $primaryGuestData = $data['primary_guest'] ?? [];
            $primaryEmail = trim((string) ($primaryGuestData['email'] ?? '')) ?: null;
            $primaryPhone = trim((string) ($primaryGuestData['phone'] ?? '')) ?: null;
            $primaryIsForeigner = (bool) ($primaryGuestData['is_foreigner'] ?? false);

            $primaryCustomer = Customer::create([
                'first_name' => $primaryFirstName,
                'middle_name' => $primaryGuestData['middle_name'] ?? null,
                'last_name' => $primaryLastName,
                'age' => $primaryGuestData['age'] ?? null,
                'gender' => $primaryGuestData['gender'] ?? 'Male',
                'is_foreigner' => $primaryIsForeigner,
                'phone' => $primaryPhone,
                'email' => $primaryEmail,
            ]);

            ReservationGuest::create([
                'reservation_id' => $reservation->id,
                'customer_id' => $primaryCustomer->id,
                'is_primary_guest' => true,
                'has_pool_access' => $primaryHasPool,
            ]);
        }

        foreach ($data['companions'] ?? [] as $cIdx => $companionData) {
            $companionFirstName = trim((string) ($companionData['first_name'] ?? '')) ?: 'Companion';
            $companionLastName = trim((string) ($companionData['last_name'] ?? '')) ?: 'Guest';
            $companionEmail = trim((string) ($companionData['email'] ?? '')) ?: null;
            $companionPhone = trim((string) ($companionData['phone'] ?? '')) ?: null;
            $companionIsForeigner = (bool) ($companionData['is_foreigner'] ?? false);
            $ageGroupMidpoint = ['0-12' => 6, '13-17' => 15, '18-59' => 30, '60+' => 65];
            $companionAge = $companionData['age'] ?? ($ageGroupMidpoint[$companionData['age_group'] ?? ''] ?? null);

            $companionCustomer = Customer::create([
                'first_name' => $companionFirstName,
                'middle_name' => $companionData['middle_name'] ?? null,
                'last_name' => $companionLastName,
                'age' => $companionAge,
                'gender' => $companionData['gender'] ?? 'Male',
                'is_foreigner' => $companionIsForeigner,
                'phone' => $companionPhone,
                'email' => $companionEmail,
            ]);

            ReservationGuest::create([
                'reservation_id' => $reservation->id,
                'customer_id' => $companionCustomer->id,
                'is_primary_guest' => false,
                'has_pool_access' => $companionsWithPoolFlags[$cIdx] ?? false,
            ]);
        }

        foreach ($processedAmenities as $pAm) {
            ReservationAmenity::create([
                'reservation_id' => $reservation->id,
                'amenity_id' => $pAm['amenity_id'],
                'start_date' => $pAm['start_date'],
                'end_date' => $pAm['end_date'],
                'start_slot' => $pAm['start_slot'],
                'end_slot' => $pAm['end_slot'],
                'day_slots_count' => $pAm['day_slots_count'],
                'night_slots_count' => $pAm['night_slots_count'],
                'pricing_type' => $pAm['pricing_type'],
                'price_at_booking' => $pAm['price_at_booking'],
                'quantity' => $pAm['quantity'],
                'status' => 'Active',
                'remarks' => 'Walk-in reservation from staff check-ins',
            ]);
        }

        $staffUser = $request->session()->get('auth_user') ?? [];
        $staffName = $staffUser['name'] ?? 'Staff User';
        ActivityLog::log(
            activityType: 'walkin_created',
            title: 'Walk-In Created & Checked In',
            description: "Walk-in reservation #{$reservation->id} ({$reservation->booker_name}, {$reservation->number_of_guests} guests) created and checked in by {$staffName}",
            reservationId: $reservation->id,
            actorName: $staffName,
            actorRole: $staffUser['role'] ?? 'staff',
            staffId: (string) ($staffUser['id'] ?? ''),
            metadata: [
                'total_amount' => $reservation->total_amount,
                'number_of_guests' => $reservation->number_of_guests,
                'staff_name' => $staffName,
            ]
        );

        return redirect()->route('staff.checkins')->with('success', 'Walk-in reservation checked in successfully.');
    })->name('checkins.guests.store');

    Route::post('/checkins/visit-only-check-in', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'guest_mode' => ['required', 'in:visitors_only'],
            'age_type' => ['required', 'in:adult,child'],
            'time_type' => ['required', 'in:daytime,nighttime,daytonight,nighttoday'],
            // Checkboxes send "on" — Laravel's boolean rule rejects it
            'include_pool' => ['required', 'in:on,1,true,0,false'],
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

        // Entrance fee based on the main guest's age type + time period.
        $timeTypeToPricing = [
            'daytime' => 'Daytime',
            'nighttime' => 'Nighttime',
            'daytonight' => 'DayToNight',
            'nighttoday' => 'NightToDay',
        ];
        $pricingType = $timeTypeToPricing[$data['time_type']] ?? 'Daytime';

        $settings = \App\Models\ParkSetting::first();
        $isChild = $data['age_type'] === 'child';
        $combined = in_array($data['time_type'], ['daytonight', 'nighttoday'], true);
        if ($data['time_type'] === 'nighttime') {
            $adultRate = (float) ($settings->nighttime_adult_entrance_fee ?? 0);
            $childRate = (float) ($settings->nighttime_child_entrance_fee ?? 0);
        } elseif ($combined) {
            $adultRate = (float) ($settings->daytime_adult_entrance_fee ?? 0) + (float) ($settings->nighttime_adult_entrance_fee ?? 0);
            $childRate = (float) ($settings->daytime_child_entrance_fee ?? 0) + (float) ($settings->nighttime_child_entrance_fee ?? 0);
        } else {
            $adultRate = (float) ($settings->daytime_adult_entrance_fee ?? 0);
            $childRate = (float) ($settings->daytime_child_entrance_fee ?? 0);
        }

        $mainGuestFee = $isChild ? $childRate : $adultRate;
        $companionEntrance = 0;
        $childCount = $isChild ? 1 : 0;
        $adultCount = $isChild ? 0 : 1;
        foreach ($data['companions'] ?? [] as $companionData) {
            $compAge = (int) ($companionData['age'] ?? 99);
            if ($compAge <= 12) {
                $companionEntrance += $childRate;
                $childCount++;
            } else {
                $companionEntrance += $adultRate;
                $adultCount++;
            }
        }

        $poolFee = 0;
        if (! empty($data['include_pool'])) {
            $dayPool = (float) ($settings->day_pool_fee ?? 0);
            $nightPool = (float) ($settings->night_pool_fee ?? 0);
            if ($data['time_type'] === 'nighttime') {
                $poolFee = $nightPool;
            } elseif ($combined) {
                $poolFee = $dayPool + $nightPool;
            } else {
                $poolFee = $dayPool;
            }
        }

        $entranceTotal = round($mainGuestFee + $companionEntrance + $poolFee, 2);

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
            'total_amount' => $entranceTotal,
            'amount_paid' => $entranceTotal,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        // Entrance fee lives in its own table — never a fake amenity row.
        \App\Models\ReservationEntranceFee::create([
            'reservation_id' => $reservation->id,
            'pricing_type' => $pricingType,
            'total_amount' => $entranceTotal,
            'pool_fee' => $poolFee,
            'adult_count' => $adultCount,
            'child_count' => $childCount,
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

        $staffName = $user['name'] ?? 'Staff User';
        ActivityLog::log(
            activityType: 'check_in',
            title: 'Visit-Only Guest Checked In',
            description: "Visit-only reservation #{$reservation->id} ({$reservation->number_of_guests} guests) checked in by {$staffName}",
            reservationId: $reservation->id,
            actorName: $staffName,
            actorRole: $user['role'] ?? 'staff',
            staffId: (string) ($user['id'] ?? ''),
            metadata: [
                'total_amount' => $entranceTotal,
                'number_of_guests' => $reservation->number_of_guests,
                'staff_name' => $staffName,
            ]
        );

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
            'primary_guest.age' => ['nullable', 'max:255'],
            'primary_guest.gender' => ['nullable', 'in:Male,Female'],
            'primary_guest.is_foreigner' => ['nullable', 'boolean'],
            'primary_guest.phone' => ['nullable', 'string', 'max:255'],
            'primary_guest.email' => ['nullable', 'email', 'max:255'],
            'primary_guest.has_pool_access' => ['nullable'],
            'companions' => ['nullable', 'array'],
            'companions.*.customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'companions.*.first_name' => ['nullable', 'required_with:companions.*.last_name', 'string', 'max:255'],
            'companions.*.middle_name' => ['nullable', 'string', 'max:255'],
            'companions.*.last_name' => ['nullable', 'required_with:companions.*.first_name', 'string', 'max:255'],
            'companions.*.age' => ['nullable', 'max:255'],
            'companions.*.age_group' => ['nullable', 'string', 'max:255'],
            'companions.*.gender' => ['nullable', 'in:Male,Female'],
            'companions.*.is_foreigner' => ['nullable', 'boolean'],
            'companions.*.phone' => ['nullable', 'string', 'max:255'],
            'companions.*.email' => ['nullable', 'email', 'max:255'],
            'companions.*.has_pool_access' => ['nullable'],
            'pool_option' => ['nullable', 'in:no_pool,specific,all_paid,all_free'],
            'include_pool' => ['nullable'],
        ]);

        ReservationGuest::where('reservation_id', $reservation->id)->delete();

        // Pool option & pool access determination
        $poolOption = $data['pool_option'] ?? (! empty($data['include_pool']) ? 'all_paid' : 'no_pool');

        $primaryHasPool = false;
        if ($data['guest_mode'] === 'with_primary' && ! empty($data['primary_guest'])) {
            if ($poolOption === 'all_paid' || $poolOption === 'all_free') {
                $primaryHasPool = true;
            } elseif ($poolOption === 'specific') {
                $pVal = $data['primary_guest']['has_pool_access'] ?? null;
                $primaryHasPool = in_array($pVal, ['1', 1, true, 'true', 'on'], true);
            }
        }

        $poolCount = $primaryHasPool ? 1 : 0;
        $companionsWithPoolFlags = [];
        foreach ($data['companions'] ?? [] as $cIdx => $companionData) {
            $cHasPool = false;
            if ($poolOption === 'all_paid' || $poolOption === 'all_free') {
                $cHasPool = true;
            } elseif ($poolOption === 'specific') {
                $cVal = $companionData['has_pool_access'] ?? null;
                $cHasPool = in_array($cVal, ['1', 1, true, 'true', 'on'], true);
            }
            if ($cHasPool) {
                $poolCount++;
            }
            $companionsWithPoolFlags[$cIdx] = $cHasPool;
        }

        if ($data['guest_mode'] === 'with_primary' && ! empty($data['primary_guest'])) {
            $primaryGuestData = $data['primary_guest'];
            $primaryFirstName = trim((string) ($primaryGuestData['first_name'] ?? '')) ?: 'Main';
            $primaryLastName = trim((string) ($primaryGuestData['last_name'] ?? '')) ?: 'Guest';
            $primaryEmail = trim((string) ($primaryGuestData['email'] ?? '')) ?: null;
            $primaryPhone = trim((string) ($primaryGuestData['phone'] ?? '')) ?: null;

            $primaryIsForeigner = (bool) ($primaryGuestData['is_foreigner'] ?? false);

            // Always create a fresh customer row per reservation check-in.
            // Matching personal info must not collapse guests across reservations.
            $primaryCustomer = Customer::create([
                'first_name' => $primaryFirstName,
                'middle_name' => $primaryGuestData['middle_name'] ?? null,
                'last_name' => $primaryLastName,
                'age' => $primaryGuestData['age'] ?? null,
                'gender' => $primaryGuestData['gender'] ?? 'Male',
                'is_foreigner' => $primaryIsForeigner,
                'phone' => $primaryPhone,
                'email' => $primaryEmail,
            ]);

            ReservationGuest::create([
                'reservation_id' => $reservation->id,
                'customer_id' => $primaryCustomer->id,
                'is_primary_guest' => true,
                'has_pool_access' => $primaryHasPool,
            ]);
        }

        foreach ($data['companions'] ?? [] as $cIdx => $companionData) {
            $companionFirstName = trim((string) ($companionData['first_name'] ?? '')) ?: 'Companion';
            $companionLastName = trim((string) ($companionData['last_name'] ?? '')) ?: 'Guest';
            $companionEmail = trim((string) ($companionData['email'] ?? '')) ?: null;
            $companionPhone = trim((string) ($companionData['phone'] ?? '')) ?: null;
            $companionIsForeigner = (bool) ($companionData['is_foreigner'] ?? false);
            $ageGroupMidpoint = ['0-12' => 6, '13-17' => 15, '18-59' => 30, '60+' => 65];
            $companionAge = $companionData['age'] ?? ($ageGroupMidpoint[$companionData['age_group'] ?? ''] ?? null);

            $companionCustomer = Customer::create([
                'first_name' => $companionFirstName,
                'middle_name' => $companionData['middle_name'] ?? null,
                'last_name' => $companionLastName,
                'age' => $companionAge,
                'gender' => $companionData['gender'] ?? 'Male',
                'is_foreigner' => $companionIsForeigner,
                'phone' => $companionPhone,
                'email' => $companionEmail,
            ]);

            ReservationGuest::create([
                'reservation_id' => $reservation->id,
                'customer_id' => $companionCustomer->id,
                'is_primary_guest' => false,
                'has_pool_access' => $companionsWithPoolFlags[$cIdx] ?? false,
            ]);
        }

        $adultCount = 0;
        $childCount = 0;
        if ($data['guest_mode'] === 'with_primary' && ! empty($data['primary_guest'])) {
            $primaryAge = (int) ($data['primary_guest']['age'] ?? 99);
            if ($primaryAge <= 12) {
                $childCount++;
            } else {
                $adultCount++;
            }
        }
        foreach ($data['companions'] ?? [] as $companionData) {
            if (($companionData['age_group'] ?? null) === '0-12') {
                $childCount++;
            } else {
                $companionAge = (int) ($companionData['age'] ?? 99);
                if ($companionAge <= 12) {
                    $childCount++;
                } else {
                    $adultCount++;
                }
            }
        }

        $effectivePeriod = 'daytime';
        $hasAmenities = $reservation->reservationAmenities()->exists();
        if ($hasAmenities) {
            $firstAmenityPricingType = $reservation->reservationAmenities()->first()?->pricing_type;
            $amenityPeriodToEntrance = [
                'Daytime' => 'daytime',
                'Daytime Aircon' => 'daytime',
                'Nighttime' => 'nighttime',
                'Nighttime Aircon' => 'nighttime',
                'DayToNight' => 'daytonight',
                'DayToNight Aircon' => 'daytonight',
                'NightToDay' => 'daytonight',
                'NightToDay Aircon' => 'daytonight',
            ];
            $effectivePeriod = $amenityPeriodToEntrance[$firstAmenityPricingType] ?? 'daytime';
        } else {
            $settingsForSession = \App\Models\ParkSetting::first();
            $currentHour = now()->format('H:i');
            $nighttimeStart = $settingsForSession?->nighttime_start ?? '17:00';
            $nighttimeEnd = $settingsForSession?->nighttime_end ?? '06:00';
            if ($nighttimeStart && $nighttimeEnd) {
                if ($nighttimeStart <= $nighttimeEnd) {
                    if ($currentHour >= $nighttimeStart && $currentHour <= $nighttimeEnd) $effectivePeriod = 'nighttime';
                } else {
                    if ($currentHour >= $nighttimeStart || $currentHour <= $nighttimeEnd) $effectivePeriod = 'nighttime';
                }
            }
        }

        $periodPricingTypeMap = [
            'daytime' => 'Daytime',
            'nighttime' => 'Nighttime',
            'daytonight' => 'DayToNight',
        ];
        $storedPricingType = $periodPricingTypeMap[$effectivePeriod] ?? 'Daytime';

        $settings = \App\Models\ParkSetting::first();
        if ($effectivePeriod === 'nighttime') {
            $adultRate = (float) ($settings->nighttime_adult_entrance_fee ?? 0);
            $childRate = (float) ($settings->nighttime_child_entrance_fee ?? 0);
        } elseif ($effectivePeriod === 'daytonight') {
            $adultRate = (float) ($settings->daytime_adult_entrance_fee ?? 0) + (float) ($settings->nighttime_adult_entrance_fee ?? 0);
            $childRate = (float) ($settings->daytime_child_entrance_fee ?? 0) + (float) ($settings->nighttime_child_entrance_fee ?? 0);
        } else {
            $adultRate = (float) ($settings->daytime_adult_entrance_fee ?? 0);
            $childRate = (float) ($settings->daytime_child_entrance_fee ?? 0);
        }

        $entranceTotal = round(($adultCount * $adultRate) + ($childCount * $childRate), 2);

        $dayPool = (float) ($settings->day_pool_fee ?? 0);
        $nightPool = (float) ($settings->night_pool_fee ?? 0);
        if ($effectivePeriod === 'nighttime') {
            $poolRate = $nightPool;
        } elseif ($effectivePeriod === 'daytonight') {
            $poolRate = $dayPool + $nightPool;
        } else {
            $poolRate = $dayPool;
        }

        $poolTotal = 0;
        if ($poolOption === 'all_paid' || $poolOption === 'specific') {
            $poolTotal = round($poolCount * $poolRate, 2);
        }

        $grandTotal = round($entranceTotal + $poolTotal, 2);

        \App\Models\ReservationEntranceFee::updateOrCreate(
            ['reservation_id' => $reservation->id],
            [
                'pricing_type' => $hasAmenities ? null : $storedPricingType,
                'pool_option' => $poolOption,
                'total_amount' => $grandTotal,
                'pool_fee' => $poolTotal,
                'pool_access_count' => $poolCount,
                'adult_count' => $adultCount,
                'child_count' => $childCount,
            ]
        );

        $oldTotal = (float) $reservation->total_amount;
        $oldPaid = (float) $reservation->amount_paid;
        $reservation->update([
            'check_in' => now()->toDateTimeString(),
            'status' => 'Checked In',
            'total_amount' => round($oldTotal + $grandTotal, 2),
            'amount_paid' => round($oldPaid + $grandTotal, 2),
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $actualGuestCount = $reservation->reservationGuests()->count();
        if ((int) $reservation->number_of_guests !== $actualGuestCount) {
            $reservation->update(['number_of_guests' => $actualGuestCount]);
        }

        $staffName = $user['name'] ?? 'Staff User';
        ActivityLog::log(
            activityType: 'check_in',
            title: 'Guest Checked In',
            description: "Reservation #{$reservation->id} ({$reservation->booker_name}, {$reservation->number_of_guests} guests) checked in by {$staffName}",
            reservationId: $reservation->id,
            actorName: $staffName,
            actorRole: $user['role'] ?? 'staff',
            staffId: (string) ($user['id'] ?? ''),
            metadata: [
                'booker_name' => $reservation->booker_name,
                'number_of_guests' => $reservation->number_of_guests,
                'entrance_fee' => $grandTotal,
                'staff_name' => $staffName,
            ]
        );

        return response()->json([
            'success' => true,
            'check_in' => $reservation->check_in,
            'status' => $reservation->status,
            'payment_status' => $reservation->payment_status,
            'entrance_fee' => $grandTotal,
        ]);
    })->name('reservations.check-in');

    // Add companion(s) — single or bulk — to an already checked-in reservation
    // (from the reservation detail modal on the staff check-ins page).
    Route::post('/reservations/{reservation}/add-companion', function (Request $request, Reservation $reservation) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'companions' => ['required', 'array', 'min:1'],
            // Bulk companions submit empty names (they only carry an age group),
            // so names must be nullable.
            'companions.*.first_name' => ['nullable', 'string', 'max:255'],
            'companions.*.middle_name' => ['nullable', 'string', 'max:255'],
            'companions.*.last_name' => ['nullable', 'string', 'max:255'],
            'companions.*.age' => ['nullable', 'integer', 'min:0'],
            'companions.*.age_group' => ['nullable', 'string', 'max:255'],
            'companions.*.gender' => ['nullable', 'in:Male,Female'],
            'companions.*.is_foreigner' => ['nullable', 'boolean'],
            'companions.*.phone' => ['nullable', 'string', 'max:255'],
            'companions.*.email' => ['nullable', 'email', 'max:255'],
            'companions.*.pool_access' => ['nullable', 'boolean'],
        ]);

        $statusKey = strtolower((string) $reservation->status);
        if (! in_array($statusKey, ['checked in', 'checked-in', 'checked_in', 'active'], true)) {
            return response()->json(['message' => 'Only checked-in reservations can accept new companions.'], 422);
        }

        // Adult/child counts from the new companions (12 and below = child),
        // plus how many of them get pool access.
        $adultCount = 0;
        $childCount = 0;
        $poolCount = 0;
        foreach ($data['companions'] ?? [] as $companionData) {
            if (! empty($companionData['pool_access'])) {
                $poolCount++;
            }
            if (($companionData['age_group'] ?? null) === '0-12') {
                $childCount++;
            } else {
                $companionAge = (int) ($companionData['age'] ?? 99);
                if ($companionAge <= 12) {
                    $childCount++;
                } else {
                    $adultCount++;
                }
            }
        }

        // Entrance period: the reservation's stored pricing_type (no amenities)
        // or its first amenity's pricing_type (with amenities).
        $entranceFee = \App\Models\ReservationEntranceFee::where('reservation_id', $reservation->id)->first();
        $pricingType = $entranceFee?->pricing_type;
        $effectivePeriod = match ($pricingType) {
            'Nighttime' => 'nighttime',
            'DayToNight' => 'daytonight',
            'NightToDay' => 'daytonight',
            default => null,
        };
        if (! $effectivePeriod) {
            $amenityPeriodToEntrance = [
                'Daytime' => 'daytime',
                'Daytime Aircon' => 'daytime',
                'Nighttime' => 'nighttime',
                'Nighttime Aircon' => 'nighttime',
                'DayToNight' => 'daytonight',
                'DayToNight Aircon' => 'daytonight',
                'NightToDay' => 'daytonight',
                'NightToDay Aircon' => 'daytonight',
            ];
            $firstAmenityPricingType = $reservation->reservationAmenities()->first()?->pricing_type;
            $effectivePeriod = $amenityPeriodToEntrance[$firstAmenityPricingType] ?? 'daytime';
        }

        $settings = \App\Models\ParkSetting::first();
        $dayAdult = (float) ($settings->daytime_adult_entrance_fee ?? 0);
        $dayChild = (float) ($settings->daytime_child_entrance_fee ?? 0);
        $nightAdult = (float) ($settings->nighttime_adult_entrance_fee ?? 0);
        $nightChild = (float) ($settings->nighttime_child_entrance_fee ?? 0);

        if ($effectivePeriod === 'nighttime') {
            $adultRate = $nightAdult;
            $childRate = $nightChild;
        } elseif (in_array($effectivePeriod, ['daytonight', 'nighttoday'], true)) {
            $adultRate = $dayAdult + $nightAdult;
            $childRate = $dayChild + $nightChild;
        } else {
            $adultRate = $dayAdult;
            $childRate = $dayChild;
        }

        $newEntranceTotal = round(($adultCount * $adultRate) + ($childCount * $childRate), 2);

        // Pool fee: charged per companion that ticks pool access, priced by
        // the same effective period as the entrance fee.
        $dayPool = (float) ($settings->day_pool_fee ?? 0);
        $nightPool = (float) ($settings->night_pool_fee ?? 0);
        if ($effectivePeriod === 'nighttime') {
            $poolRate = $nightPool;
        } elseif (in_array($effectivePeriod, ['daytonight', 'nighttoday'], true)) {
            $poolRate = $dayPool + $nightPool;
        } else {
            $poolRate = $dayPool;
        }
        $newPoolTotal = round($poolCount * $poolRate, 2);

        $newCompanionTotal = round($newEntranceTotal + $newPoolTotal, 2);

        // Create customers + reservation guests (checked-in: no checked_out_at).
        foreach ($data['companions'] ?? [] as $companionData) {
            $companionFirstName = trim((string) ($companionData['first_name'] ?? '')) ?: 'Companion';
            $companionLastName = trim((string) ($companionData['last_name'] ?? '')) ?: 'Guest';
            $companionEmail = trim((string) ($companionData['email'] ?? '')) ?: null;
            $companionPhone = trim((string) ($companionData['phone'] ?? '')) ?: null;
            $companionIsForeigner = (bool) ($companionData['is_foreigner'] ?? false);

            $ageGroupMidpoint = ['0-12' => 6, '13-17' => 15, '18-59' => 30, '60+' => 65];
            $companionAge = $companionData['age'] ?? ($ageGroupMidpoint[$companionData['age_group'] ?? ''] ?? null);

            $companionCustomer = Customer::create([
                'first_name' => $companionFirstName,
                'middle_name' => $companionData['middle_name'] ?? null,
                'last_name' => $companionLastName,
                'age' => $companionAge,
                'gender' => $companionData['gender'] ?? 'Male',
                'is_foreigner' => $companionIsForeigner,
                'phone' => $companionPhone,
                'email' => $companionEmail,
            ]);

            ReservationGuest::create([
                'reservation_id' => $reservation->id,
                'customer_id' => $companionCustomer->id,
                'is_primary_guest' => false,
                'has_pool_access' => ! empty($companionData['pool_access']),
            ]);
        }

        $actualGuestCount = $reservation->reservationGuests()->count();

        // Entrance fee record: keep adult/child counts + pool + total in sync
        // with every companion added (entrance AND pool are re-charged here).
        if ($entranceFee) {
            $entranceFee->update([
                'total_amount' => round((float) $entranceFee->total_amount + $newCompanionTotal, 2),
                'pool_fee' => round((float) $entranceFee->pool_fee + $newPoolTotal, 2),
                'pool_access_count' => ((int) ($entranceFee->pool_access_count ?? 0)) + $poolCount,
                'adult_count' => ((int) $entranceFee->adult_count) + $adultCount,
                'child_count' => ((int) $entranceFee->child_count) + $childCount,
            ]);
        } else {
            \App\Models\ReservationEntranceFee::create([
                'reservation_id' => $reservation->id,
                'pricing_type' => null,
                'pool_option' => $poolCount > 0 ? 'specific' : 'no_pool',
                'total_amount' => $newCompanionTotal,
                'pool_fee' => $newPoolTotal,
                'pool_access_count' => $poolCount,
                'adult_count' => $adultCount,
                'child_count' => $childCount,
            ]);
        }

        // Reservation totals: paid reservations (walk-ins) are settled at the
        // counter; partially-paid ones (online) add to the remaining balance.
        $newTotal = round((float) $reservation->total_amount + $newCompanionTotal, 2);
        $updates = [
            'total_amount' => $newTotal,
            'number_of_guests' => $actualGuestCount,
        ];
        if (strtolower((string) $reservation->payment_status) === 'paid') {
            $updates['amount_paid'] = round((float) $reservation->amount_paid + $newCompanionTotal, 2);
            $updates['remaining_balance'] = 0;
        } else {
            $updates['remaining_balance'] = round((float) $reservation->remaining_balance + $newCompanionTotal, 2);
        }
        $reservation->update($updates);

        $staffName = $user['name'] ?? 'Staff User';
        $addedCount = count($data['companions'] ?? []);
        ActivityLog::log(
            activityType: 'companion_added',
            title: 'Companion(s) Added',
            description: "{$addedCount} companion(s) added to Reservation #{$reservation->id} ({$reservation->booker_name}) by {$staffName}",
            reservationId: $reservation->id,
            actorName: $staffName,
            actorRole: $user['role'] ?? 'staff',
            staffId: (string) ($user['id'] ?? ''),
            metadata: [
                'added_count' => $addedCount,
                'new_number_of_guests' => $actualGuestCount,
                'staff_name' => $staffName,
            ]
        );

        return response()->json([
            'success' => true,
            'added' => count($data['companions'] ?? []),
            'entrance_fee' => $newEntranceTotal,
            'pool_fee' => $newPoolTotal,
            'number_of_guests' => $actualGuestCount,
        ]);
    })->name('reservations.add-companion');

    // Extend / Adjust master stay schedule for an active checked-in reservation
    Route::post('/reservations/{reservation}/extend-stay', function (Request $request, Reservation $reservation) use ($calculateContinuousSlotsCount, $continuousSlotTimeline, $computeReservationCheckoutAt) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $statusKey = strtolower((string) $reservation->status);
        if (! in_array($statusKey, ['checked in', 'checked-in', 'checked_in', 'active'], true)) {
            return response()->json(['message' => 'Only checked-in reservations can have their stay adjusted.'], 422);
        }

        $data = $request->validate([
            'new_end_date' => ['required', 'date'],
            'new_end_slot' => ['required', 'in:Daytime,Nighttime'],
        ]);

        $origStartDate = $reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->toDateString() : now()->toDateString();
        $origStartSlot = $reservation->start_slot ?? 'Daytime';

        $newEndDate = \Illuminate\Support\Carbon::parse($data['new_end_date'])->toDateString();
        $newEndSlot = $data['new_end_slot'];

        $newTimeline = $continuousSlotTimeline($origStartDate, $newEndDate, $origStartSlot, $newEndSlot);
        if (empty($newTimeline)) {
            return response()->json(['message' => "The new check-out date/session cannot be earlier than the reservation check-in date ({$origStartDate} [{$origStartSlot}])."], 422);
        }

        // Find the latest check-out among all active/booked amenities on this reservation
        $amenities = $reservation->reservationAmenities()
            ->where('status', '!=', 'Completed')
            ->get();
        if ($amenities->isEmpty()) {
            $amenities = $reservation->reservationAmenities()->get();
        }

        $latestAmenityEndDate = null;
        $latestAmenityEndSlot = 'Daytime';
        $latestAmenityTimelineCount = 0;
        $latestAmenityName = null;

        foreach ($amenities as $ra) {
            $amStart = $ra->start_date ? \Illuminate\Support\Carbon::parse($ra->start_date)->toDateString() : $origStartDate;
            $amEnd = $ra->end_date ? \Illuminate\Support\Carbon::parse($ra->end_date)->toDateString() : $amStart;
            $amStartSlot = $ra->start_slot ?? $origStartSlot;
            $amEndSlot = $ra->end_slot ?? 'Daytime';

            $amTimeline = $continuousSlotTimeline($origStartDate, $amEnd, $origStartSlot, $amEndSlot);
            if (count($amTimeline) > $latestAmenityTimelineCount) {
                $latestAmenityTimelineCount = count($amTimeline);
                $latestAmenityEndDate = $amEnd;
                $latestAmenityEndSlot = $amEndSlot;
                $latestAmenityName = $ra->amenity?->amenities_name ?? ($ra->amenity_id ?? 'Amenity');
            }
        }

        if ($latestAmenityTimelineCount > 0 && count($newTimeline) < $latestAmenityTimelineCount) {
            $formattedAmenityDate = \Illuminate\Support\Carbon::parse($latestAmenityEndDate)->format('M d, Y');
            return response()->json([
                'message' => "Cannot step back stay schedule before {$formattedAmenityDate} ({$latestAmenityEndSlot}) because {$latestAmenityName} is booked until that date and session. Amenity durations cannot be decreased."
            ], 422);
        }

        $newCounts = $calculateContinuousSlotsCount($origStartDate, $newEndDate, $origStartSlot, $newEndSlot);

        $reservation->update([
            'end_date' => $newEndDate,
            'end_slot' => $newEndSlot,
            'total_days' => $newCounts['days_span'],
        ]);

        $reservation->refresh();
        $newCheckoutAt = $computeReservationCheckoutAt($reservation);

        $staffName = $user['name'] ?? 'Staff User';
        ActivityLog::log(
            activityType: 'stay_extended',
            title: 'Stay Extended',
            description: "Reservation #{$reservation->id} ({$reservation->booker_name}) extended stay from {$origStartDate} ({$origStartSlot}) to {$newEndDate} ({$newEndSlot}) by {$staffName}",
            reservationId: $reservation->id,
            actorName: $staffName,
            actorRole: $user['role'] ?? 'staff',
            staffId: (string) ($user['id'] ?? ''),
            metadata: [
                'orig_start_date' => $origStartDate,
                'orig_start_slot' => $origStartSlot,
                'new_end_date' => $newEndDate,
                'new_end_slot' => $newEndSlot,
                'total_days' => $newCounts['days_span'],
                'staff_name' => $staffName,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Stay schedule updated successfully.',
            'end_date' => $newEndDate,
            'end_slot' => $newEndSlot,
            'total_days' => $newCounts['days_span'],
            'checkout_at' => $newCheckoutAt?->toIso8601String(),
        ]);
    })->name('reservations.extend-stay');

    // Extend an existing active amenity on a checked-in reservation
    Route::post('/reservations/{reservation}/amenities/{reservationAmenity}/extend', function (Request $request, Reservation $reservation, ReservationAmenity $reservationAmenity) use ($calculateContinuousSlotsCount, $continuousSlotTimeline, $isAmenityRangeTaken, $computeReservationCheckoutAt) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $statusKey = strtolower((string) $reservation->status);
        if (! in_array($statusKey, ['checked in', 'checked-in', 'checked_in', 'active'], true)) {
            return response()->json(['message' => 'Only checked-in reservations can extend amenities.'], 422);
        }

        if ($reservationAmenity->reservation_id !== $reservation->id) {
            return response()->json(['message' => 'Amenity does not belong to this reservation.'], 404);
        }

        if ($reservationAmenity->status === 'Completed') {
            return response()->json(['message' => 'Cannot extend an amenity that is already completed/checked out.'], 422);
        }

        $data = $request->validate([
            'new_end_date' => ['required', 'date'],
            'new_end_slot' => ['required', 'in:Daytime,Nighttime'],
        ]);

        $masterStartDate = $reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->toDateString() : now()->toDateString();
        $masterEndDate = $reservation->end_date ? \Illuminate\Support\Carbon::parse($reservation->end_date)->toDateString() : $masterStartDate;
        $masterStartSlot = $reservation->start_slot ?? 'Daytime';
        $masterEndSlot = $reservation->end_slot ?? 'Daytime';

        $masterTimeline = $continuousSlotTimeline($masterStartDate, $masterEndDate, $masterStartSlot, $masterEndSlot);
        $masterKeys = [];
        foreach ($masterTimeline as [$d, $s]) {
            $masterKeys["{$d}_{$s}"] = true;
        }

        $amenityStartDate = $reservationAmenity->start_date ? \Illuminate\Support\Carbon::parse($reservationAmenity->start_date)->toDateString() : $masterStartDate;
        $amenityStartSlot = $reservationAmenity->start_slot ?? $masterStartSlot;
        $currentAmenityEndDate = $reservationAmenity->end_date ? \Illuminate\Support\Carbon::parse($reservationAmenity->end_date)->toDateString() : $amenityStartDate;
        $currentAmenityEndSlot = $reservationAmenity->end_slot ?? $amenityStartSlot;

        $newAmenityEndDate = \Illuminate\Support\Carbon::parse($data['new_end_date'])->toDateString();
        $newAmenityEndSlot = $data['new_end_slot'];

        $oldTimeline = $continuousSlotTimeline($amenityStartDate, $currentAmenityEndDate, $amenityStartSlot, $currentAmenityEndSlot);
        $newTimeline = $continuousSlotTimeline($amenityStartDate, $newAmenityEndDate, $amenityStartSlot, $newAmenityEndSlot);

        if (count($newTimeline) <= count($oldTimeline)) {
            return response()->json(['message' => 'New amenity end date/session must be later than the current amenity schedule.'], 422);
        }

        // 1. Boundary enforcement: amenity cannot exceed the reservation master stay
        foreach ($newTimeline as [$d, $s]) {
            if (! isset($masterKeys["{$d}_{$s}"])) {
                return response()->json([
                    'message' => "The extended duration ({$newAmenityEndDate} [{$newAmenityEndSlot}]) exceeds the reservation's overall check-out schedule ({$masterEndDate} [{$masterEndSlot}]). Please extend the overall stay first."
                ], 422);
            }
        }

        // 2. Conflict check on the EXTENDED portion only:
        $addedSlots = array_slice($newTimeline, count($oldTimeline));
        if (! empty($addedSlots)) {
            $firstAddedDate = $addedSlots[0][0];
            $firstAddedSlot = $addedSlots[0][1];
            $lastAddedDate = $addedSlots[count($addedSlots) - 1][0];
            $lastAddedSlot = $addedSlots[count($addedSlots) - 1][1];

            if ($isAmenityRangeTaken((string) $reservationAmenity->amenity_id, $firstAddedDate, $lastAddedDate, $firstAddedSlot, $lastAddedSlot, $reservation->id)) {
                return response()->json([
                    'message' => "Cannot extend {$reservationAmenity->amenity?->amenities_name}. The amenity is already reserved by another guest during the requested extension period."
                ], 422);
            }
        }

        // Calculate extra cost for the added slots
        $amenity = $reservationAmenity->amenity;
        $hasAircon = str_contains((string) $reservationAmenity->pricing_type, 'Aircon');
        $dayPrice = $hasAircon && $amenity?->daytime_aircon_price ? (float) $amenity->daytime_aircon_price : (float) ($amenity?->daytime_price ?? 0);
        $nightPrice = $hasAircon && $amenity?->nighttime_aircon_price ? (float) $amenity->nighttime_aircon_price : (float) ($amenity?->nighttime_price ?? 0);

        $extraDayCount = 0;
        $extraNightCount = 0;
        foreach ($addedSlots as [$d, $s]) {
            if ($s === 'Daytime') $extraDayCount++;
            else $extraNightCount++;
        }

        $quantity = max(1, (int) ($reservationAmenity->quantity ?? 1));
        $addedCost = round((($extraDayCount * $dayPrice) + ($extraNightCount * $nightPrice)) * $quantity, 2);

        $fullCounts = $calculateContinuousSlotsCount($amenityStartDate, $newAmenityEndDate, $amenityStartSlot, $newAmenityEndSlot);
        $newPricingType = $fullCounts['days_span'] > 1
            ? "Continuous Stay ({$fullCounts['days_span']}D)" . ($hasAircon ? ' Aircon' : '')
            : (($amenityStartSlot === 'Daytime' && $newAmenityEndSlot === 'Nighttime') ? ($hasAircon ? 'DayToNight Aircon' : 'DayToNight') : ($hasAircon ? "{$amenityStartSlot} Aircon" : $amenityStartSlot));

        $oldPrice = (float) $reservationAmenity->price_at_booking;
        $newAmenityPrice = round($oldPrice + $addedCost, 2);

        $reservationAmenity->update([
            'end_date' => $newAmenityEndDate,
            'end_slot' => $newAmenityEndSlot,
            'day_slots_count' => $fullCounts['day_count'],
            'night_slots_count' => $fullCounts['night_count'],
            'pricing_type' => $newPricingType,
            'price_at_booking' => $newAmenityPrice,
        ]);

        // Update reservation totals and amount paid (settled at counter)
        $reservation->update([
            'total_amount' => round((float) $reservation->total_amount + $addedCost, 2),
            'amount_paid' => round((float) $reservation->amount_paid + $addedCost, 2),
        ]);

        $reservation->refresh();
        $newCheckoutAt = $computeReservationCheckoutAt($reservation);

        $staffName = $user['name'] ?? 'Staff User';
        $amenityName = $amenity?->amenities_name ?? 'Amenity';
        ActivityLog::log(
            activityType: 'amenity_extended',
            title: 'Amenity Extended',
            description: "Reservation #{$reservation->id} ({$reservation->booker_name}) extended {$amenityName} to {$newAmenityEndDate} ({$newAmenityEndSlot}) by {$staffName} (₱" . number_format($addedCost, 2) . " added)",
            reservationId: $reservation->id,
            actorName: $staffName,
            actorRole: $user['role'] ?? 'staff',
            staffId: (string) ($user['id'] ?? ''),
            metadata: [
                'amenity_id' => $reservationAmenity->amenity_id,
                'amenity_name' => $amenityName,
                'new_end_date' => $newAmenityEndDate,
                'new_end_slot' => $newAmenityEndSlot,
                'added_cost' => $addedCost,
                'staff_name' => $staffName,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Extended {$amenity?->amenities_name} successfully.",
            'added_cost' => $addedCost,
            'new_total' => $reservation->total_amount,
            'new_end_date' => $newAmenityEndDate,
            'new_end_slot' => $newAmenityEndSlot,
            'checkout_at' => $newCheckoutAt?->toIso8601String(),
        ]);
    })->name('reservations.amenities.extend');

    // Add a brand-new amenity to an active checked-in reservation
    Route::post('/reservations/{reservation}/amenities/add', function (Request $request, Reservation $reservation) use ($calculateContinuousSlotsCount, $continuousSlotTimeline, $isAmenityRangeTaken, $computeReservationCheckoutAt) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $statusKey = strtolower((string) $reservation->status);
        if (! in_array($statusKey, ['checked in', 'checked-in', 'checked_in', 'active'], true)) {
            return response()->json(['message' => 'Only checked-in reservations can add amenities.'], 422);
        }

        $data = $request->validate([
            'amenity_id' => ['required', 'string', 'exists:amenities,id'],
            'start_date' => ['nullable', 'date'],
            'start_slot' => ['nullable', 'in:Daytime,Nighttime'],
            'end_date' => ['required', 'date'],
            'end_slot' => ['required', 'in:Daytime,Nighttime'],
            'is_aircon' => ['nullable', 'boolean'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $amenity = Amenity::findOrFail($data['amenity_id']);

        $settingsForSession = \App\Models\ParkSetting::first();
        $currentHour = now()->format('H:i');
        $nighttimeStart = $settingsForSession?->nighttime_start ?? '17:00';
        $nighttimeEnd = $settingsForSession?->nighttime_end ?? '06:00';
        $currentSession = 'Daytime';
        if ($nighttimeStart && $nighttimeEnd) {
            if ($nighttimeStart <= $nighttimeEnd) {
                if ($currentHour >= $nighttimeStart && $currentHour <= $nighttimeEnd) $currentSession = 'Nighttime';
            } else {
                if ($currentHour >= $nighttimeStart || $currentHour <= $nighttimeEnd) $currentSession = 'Nighttime';
            }
        }

        $masterStartDate = $reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->toDateString() : now()->toDateString();
        $masterEndDate = $reservation->end_date ? \Illuminate\Support\Carbon::parse($reservation->end_date)->toDateString() : $masterStartDate;
        $masterStartSlot = $reservation->start_slot ?? 'Daytime';
        $masterEndSlot = $reservation->end_slot ?? 'Daytime';

        $masterTimeline = $continuousSlotTimeline($masterStartDate, $masterEndDate, $masterStartSlot, $masterEndSlot);
        $masterKeys = [];
        foreach ($masterTimeline as [$d, $s]) {
            $masterKeys["{$d}_{$s}"] = true;
        }

        $startDate = ! empty($data['start_date']) ? \Illuminate\Support\Carbon::parse($data['start_date'])->toDateString() : now()->toDateString();
        $startSlot = ! empty($data['start_slot']) ? $data['start_slot'] : $currentSession;
        $endDate = \Illuminate\Support\Carbon::parse($data['end_date'])->toDateString();
        $endSlot = $data['end_slot'];

        // Chronological ordering helper: Day < Night within the same day.
        $slotOrderIndex = fn (string $date, string $slot): int => ((int) strtotime($date)) * 2 + (str_contains($slot, 'Night') ? 1 : 0);

        // Sessions that have already begun cannot be booked retroactively:
        // - Daytime now   -> earliest bookable start is tonight's Nighttime.
        // - Nighttime now -> today is fully underway; earliest is tomorrow's Daytime.
        if ($currentSession === 'Nighttime') {
            $earliestDate = now()->addDay()->toDateString();
            $earliestSlot = 'Daytime';
        } else {
            $earliestDate = now()->toDateString();
            $earliestSlot = 'Nighttime';
        }

        if ($slotOrderIndex($startDate, $startSlot) < $slotOrderIndex($earliestDate, $earliestSlot)) {
            return response()->json([
                'message' => "That session has already started. The earliest available start for a new amenity is {$earliestDate} ({$earliestSlot}).",
            ], 422);
        }

        // Reject an end session chronologically before the start session
        // (e.g. start Aug 22 Nighttime -> end Aug 22 Daytime would otherwise
        // silently roll over into the next day).
        if ($slotOrderIndex($endDate, $endSlot) < $slotOrderIndex($startDate, $startSlot)) {
            return response()->json([
                'message' => "The end date/session ({$endDate} [{$endSlot}]) cannot be earlier than the start date/session ({$startDate} [{$startSlot}]).",
            ], 422);
        }

        $itemTimeline = $continuousSlotTimeline($startDate, $endDate, $startSlot, $endSlot);
        if (empty($itemTimeline)) {
            return response()->json(['message' => 'Invalid schedule selected for amenity.'], 422);
        }

        // 1. Boundary enforcement: cannot exceed master stay
        foreach ($itemTimeline as [$d, $s]) {
            if (! isset($masterKeys["{$d}_{$s}"])) {
                return response()->json([
                    'message' => "The requested schedule for {$amenity->amenities_name} ({$startDate} [{$startSlot}] to {$endDate} [{$endSlot}]) exceeds the reservation's overall check-out schedule ({$masterEndDate} [{$masterEndSlot}])."
                ], 422);
            }
        }

        // 2. Conflict check
        if ($isAmenityRangeTaken((string) $amenity->id, $startDate, $endDate, $startSlot, $endSlot, $reservation->id)) {
            return response()->json([
                'message' => "Amenity {$amenity->amenities_name} is already booked by another guest during the selected schedule ({$startDate} [{$startSlot}] to {$endDate} [{$endSlot}])."
            ], 422);
        }

        $itemCounts = $calculateContinuousSlotsCount($startDate, $endDate, $startSlot, $endSlot);
        $hasAircon = ! empty($data['is_aircon']);

        $dayPrice = $hasAircon && $amenity->daytime_aircon_price ? (float) $amenity->daytime_aircon_price : (float) ($amenity->daytime_price ?? 0);
        $nightPrice = $hasAircon && $amenity->nighttime_aircon_price ? (float) $amenity->nighttime_aircon_price : (float) ($amenity->nighttime_price ?? 0);

        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $totalAmenityCost = round((($itemCounts['day_count'] * $dayPrice) + ($itemCounts['night_count'] * $nightPrice)) * $quantity, 2);

        $pricingType = $itemCounts['days_span'] > 1
            ? "Continuous Stay ({$itemCounts['days_span']}D)" . ($hasAircon ? ' Aircon' : '')
            : (($startSlot === 'Daytime' && $endSlot === 'Nighttime') ? ($hasAircon ? 'DayToNight Aircon' : 'DayToNight') : ($hasAircon ? "{$startSlot} Aircon" : $startSlot));

        $newAmenity = ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_slot' => $startSlot,
            'end_slot' => $endSlot,
            'day_slots_count' => $itemCounts['day_count'],
            'night_slots_count' => $itemCounts['night_count'],
            'pricing_type' => $pricingType,
            'price_at_booking' => $totalAmenityCost,
            'quantity' => $quantity,
            'status' => 'Active',
            'remarks' => 'Added mid-stay from staff portal',
        ]);

        // Update reservation totals
        $reservation->update([
            'total_amount' => round((float) $reservation->total_amount + $totalAmenityCost, 2),
            'amount_paid' => round((float) $reservation->amount_paid + $totalAmenityCost, 2),
        ]);

        $reservation->refresh();
        $newCheckoutAt = $computeReservationCheckoutAt($reservation);

        $staffName = $user['name'] ?? 'Staff User';
        ActivityLog::log(
            activityType: 'amenity_added',
            title: 'Amenity Added Mid-Stay',
            description: "Reservation #{$reservation->id} ({$reservation->booker_name}) added {$amenity->amenities_name} ({$startDate} [{$startSlot}] to {$endDate} [{$endSlot}]) by {$staffName} (₱" . number_format($totalAmenityCost, 2) . ")",
            reservationId: $reservation->id,
            actorName: $staffName,
            actorRole: $user['role'] ?? 'staff',
            staffId: (string) ($user['id'] ?? ''),
            metadata: [
                'amenity_id' => $amenity->id,
                'amenity_name' => $amenity->amenities_name,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_slot' => $startSlot,
                'end_slot' => $endSlot,
                'cost' => $totalAmenityCost,
                'staff_name' => $staffName,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Added {$amenity->amenities_name} to reservation #{$reservation->id} successfully.",
            'amenity' => $newAmenity,
            'added_cost' => $totalAmenityCost,
            'new_total' => $reservation->total_amount,
            'checkout_at' => $newCheckoutAt?->toIso8601String(),
        ]);
    })->name('reservations.amenities.add');

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

        // Auto-complete every availed amenity when the whole reservation checks out
        ReservationAmenity::where('reservation_id', $reservation->id)
            ->where('status', 'Active')
            ->update(['status' => 'Completed']);

        $staffName = $user['name'] ?? 'Staff User';
        ActivityLog::log(
            activityType: 'check_out',
            title: 'Guest Checked Out',
            description: "Reservation #{$reservation->id} ({$reservation->booker_name}) completed check out with {$staffName}",
            reservationId: $reservation->id,
            actorName: $staffName,
            actorRole: $user['role'] ?? 'staff',
            staffId: (string) ($user['id'] ?? ''),
            metadata: [
                'checked_out_at' => now()->toDateTimeString(),
                'staff_name' => $staffName,
            ]
        );

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

                    Mail::to($customer->email)->send(
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

    Route::post('/reservations/{reservation}/amenities/{reservationAmenity}/check-out', function (Request $request, Reservation $reservation, ReservationAmenity $reservationAmenity) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Guard: the amenity must belong to this reservation
        if ($reservationAmenity->reservation_id !== $reservation->id) {
            return response()->json([
                'success' => false,
                'message' => 'Amenity does not belong to this reservation.',
            ], 422);
        }

        // Only Active amenities can be checked out; idempotent for already-completed ones
        if ($reservationAmenity->status === 'Completed') {
            return response()->json([
                'success' => true,
                'reservation_amenity_id' => $reservationAmenity->id,
                'status' => 'Completed',
                'message' => 'Amenity already completed.',
            ]);
        }

        $reservationAmenity->update(['status' => 'Completed']);

        $staffName = $user['name'] ?? 'Staff User';
        $amenityName = $reservationAmenity->amenity?->amenities_name ?? 'Amenity';
        ActivityLog::log(
            activityType: 'amenity_checked_out',
            title: 'Amenity Checked Out',
            description: "Reservation #{$reservation->id} ({$reservation->booker_name}) checked out {$amenityName} with {$staffName}",
            reservationId: $reservation->id,
            actorName: $staffName,
            actorRole: $user['role'] ?? 'staff',
            staffId: (string) ($user['id'] ?? ''),
            metadata: [
                'amenity_id' => $reservationAmenity->amenity_id,
                'amenity_name' => $amenityName,
                'staff_name' => $staffName,
            ]
        );

        return response()->json([
            'success' => true,
            'reservation_amenity_id' => $reservationAmenity->id,
            'status' => 'Completed',
            'message' => 'Amenity checked out successfully.',
        ]);
    })->name('reservations.amenities.checkout');

    Route::get('/reservations/{reservation}/availability', function (Request $request, Reservation $reservation) use ($isAmenityRangeTaken, $formatLocalDate, $getReservationAmenityTimeline) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $month = max(1, min(12, $month));

        $targetAmenityId = $request->query('amenity_id');

        if ($targetAmenityId) {
            $combos = collect([[
                'id' => null,
                'amenity_id' => $targetAmenityId,
                'amenity_name' => 'Amenity',
                'pricing_type' => 'Daytime',
                'has_aircon' => false,
                'daytime_price' => 0,
                'nighttime_price' => 0,
                'quantity' => 1,
                'price_at_booking' => 0,
                'start_slot' => 'Daytime',
                'end_slot' => 'Daytime',
            ]]);
        } else {
            // Gather all amenities belonging to this reservation
            $amenityItems = $reservation->reservationAmenities()
                ->with('amenity')
                ->get();

            $combos = $amenityItems
                ->filter(fn ($ra) => ! empty($ra->amenity_id))
                ->map(function ($ra) {
                    $hasAircon = str_contains((string) $ra->pricing_type, 'Aircon');
                    $amenity = $ra->amenity;

                    $dayPrice = $amenity ? ($hasAircon && $amenity->daytime_aircon_price ? (float) $amenity->daytime_aircon_price : (float) $amenity->daytime_price) : 0;
                    $nightPrice = $amenity ? ($hasAircon && $amenity->nighttime_aircon_price ? (float) $amenity->nighttime_aircon_price : (float) $amenity->nighttime_price) : 0;

                    return [
                        'id' => $ra->id,
                        'amenity_id' => $ra->amenity_id,
                        'amenity_name' => $amenity?->amenities_name ?? 'Amenity',
                        'pricing_type' => $ra->pricing_type,
                        'has_aircon' => $hasAircon,
                        'daytime_price' => $dayPrice,
                        'nighttime_price' => $nightPrice,
                        'quantity' => max(1, (int) $ra->quantity),
                        'price_at_booking' => (float) $ra->price_at_booking,
                        'start_slot' => $ra->start_slot ?? 'Daytime',
                        'end_slot' => $ra->end_slot ?? 'Daytime',
                    ];
                })
                ->values();
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = \Illuminate\Support\Carbon::parse($request->query('start_date'))->startOfDay();
            $end = \Illuminate\Support\Carbon::parse($request->query('end_date'))->startOfDay();
            if ($end->lt($start)) {
                $end = $start->copy();
            }
            $numDays = (int) $start->diffInDays($end) + 1;
        } else {
            $month = (int) $request->query('month', now()->month);
            $year = (int) $request->query('year', now()->year);
            $month = max(1, min(12, $month));
            $start = \Illuminate\Support\Carbon::createFromDate($year, $month, 1)->startOfDay();
            $numDays = $start->daysInMonth;
        }
        $minDate = $start->toDateString();
        $maxDate = $start->copy()->addDays($numDays - 1)->toDateString();

        $amenityIds = $combos->pluck('amenity_id')->unique()->filter()->values()->all();

        $activeBookedAmenities = ReservationAmenity::query()
            ->whereIn('amenity_id', $amenityIds)
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'Completed');
            })
            ->whereHas('reservation', function ($rq) use ($reservation) {
                $rq->whereNotIn('status', ['Cancelled', 'Checked Out', 'cancelled', 'checked out', 'checked_out', 'checked-out'])
                   ->when($reservation->id, fn ($q) => $q->whereKeyNot($reservation->id));
            })
            ->where(function ($q) use ($minDate, $maxDate) {
                // Amenity's own start_date / end_date overlap
                $q->where(function ($aq) use ($minDate, $maxDate) {
                    $aq->whereNotNull('start_date')
                       ->whereDate('start_date', '<=', $maxDate)
                       ->where(function ($sub) use ($minDate) {
                           $sub->whereDate('end_date', '>=', $minDate)
                               ->orWhere(function ($sub2) use ($minDate) {
                                   $sub2->whereNull('end_date')
                                        ->whereDate('start_date', '>=', \Illuminate\Support\Carbon::parse($minDate)->subDays(2)->toDateString());
                               });
                       });
                })
                // OR parent reservation's reservation_date / end_date overlap
                ->orWhereHas('reservation', function ($rq) use ($minDate, $maxDate) {
                    $rq->whereDate('reservation_date', '<=', $maxDate)
                       ->where(function ($sub) use ($minDate) {
                           $sub->whereDate('end_date', '>=', $minDate)
                               ->orWhere(function ($sub2) use ($minDate) {
                                   $sub2->whereNull('end_date')
                                        ->whereDate('reservation_date', '>=', \Illuminate\Support\Carbon::parse($minDate)->subDays(2)->toDateString());
                               });
                       });
                })
                // OR parent reservation is currently Checked In (active on site)
                ->orWhereHas('reservation', function ($rq) {
                    $rq->whereIn('status', ['Checked In', 'checked in', 'checked_in', 'Active', 'active']);
                });
            })
            ->with('reservation')
            ->get();

        $takenSlots = [];
        foreach ($activeBookedAmenities as $bRa) {
            $bRes = $bRa->reservation;
            if (! $bRes) continue;

            $resStatus = strtolower(trim((string) $bRes->status));
            if (in_array($resStatus, ['cancelled', 'checked out', 'checkedout', 'checked-out'], true)) {
                continue;
            }

            if ($reservation->id && (int) $bRes->id === (int) $reservation->id) {
                continue;
            }

            $tLine = $getReservationAmenityTimeline($bRa, $bRes);
            $aId = (string) $bRa->amenity_id;
            foreach ($tLine as [$d, $s]) {
                $takenSlots["{$aId}_{$d}_{$s}"] = true;
            }
        }

        $today = now()->toDateString();
        $currentStartDate = $formatLocalDate($reservation, 'reservation_date');
        $currentEndDate = $formatLocalDate($reservation, 'end_date') ?: $currentStartDate;

        $availability = [];
        for ($i = 0; $i < $numDays; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $isPast = $date < $today;
            if ($date === $currentStartDate) {
                $isPast = false;
            }

            $daytimeAvail = true;
            $nighttimeAvail = true;

            foreach ($combos as $combo) {
                $aId = (string) $combo['amenity_id'];
                if (isset($takenSlots["{$aId}_{$date}_Daytime"])) {
                    $daytimeAvail = false;
                }
                if (isset($takenSlots["{$aId}_{$date}_Nighttime"])) {
                    $nighttimeAvail = false;
                }
            }

            $availability[] = [
                'date' => $date,
                'is_past' => $isPast,
                'daytime' => $daytimeAvail,
                'nighttime' => $nighttimeAvail,
                'available' => $daytimeAvail && ! $isPast,
                'full_available' => $daytimeAvail && $nighttimeAvail && ! $isPast,
            ];
        }

        $entranceFeeAmount = (float) ($reservation->entranceFee?->total_amount ?? 0);

        return response()->json([
            'reservation_id' => $reservation->id,
            'month' => $month,
            'year' => $year,
            'current_date' => $currentStartDate,
            'current_start_date' => $currentStartDate,
            'current_end_date' => $currentEndDate,
            'current_start_slot' => $reservation->start_slot ?? 'Daytime',
            'current_end_slot' => $reservation->end_slot ?? 'Daytime',
            'total_days' => max(1, (int) ($reservation->total_days ?? 1)),
            'slot' => $combos->pluck('pricing_type')->unique()->values(),
            'total_amount' => (float) $reservation->total_amount,
            'amount_paid' => (float) $reservation->amount_paid,
            'remaining_balance' => (float) $reservation->remaining_balance,
            'payment_status' => $reservation->payment_status,
            'entrance_fee' => $entranceFeeAmount,
            'amenities' => $combos,
            'availability' => $availability,
        ]);
    })->name('reservations.availability');

    Route::get('/amenities-list', function (Request $request) {
        $user = $request->session()->get('auth_user');
        if (! $user || ! in_array($user['role'] ?? '', ['staff', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $amenities = Amenity::where('status', true)->orderBy('amenities_name')->get();
        return response()->json([
            'success' => true,
            'amenities' => $amenities,
        ]);
    })->name('amenities.list');

    Route::post('/reservations/{reservation}/check-amenities-availability', function (Request $request, Reservation $reservation) use ($isAmenityRangeTaken) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'ranges' => 'required|array',
            'ranges.*.index' => 'required',
            'ranges.*.start_date' => 'required|date',
            'ranges.*.end_date' => 'nullable|date',
            'ranges.*.start_slot' => 'nullable|string',
            'ranges.*.end_slot' => 'nullable|string',
        ]);

        $allAmenities = Amenity::where('status', true)->pluck('id')->all();
        $result = [];

        foreach ($validated['ranges'] as $range) {
            $idx = $range['index'];
            $sDate = \Illuminate\Support\Carbon::parse($range['start_date'])->toDateString();
            $eDate = !empty($range['end_date']) ? \Illuminate\Support\Carbon::parse($range['end_date'])->toDateString() : $sDate;
            $sSlot = !empty($range['start_slot']) ? (str_contains($range['start_slot'], 'Night') ? 'Nighttime' : 'Daytime') : 'Daytime';
            $eSlot = !empty($range['end_slot']) ? (str_contains($range['end_slot'], 'Night') ? 'Nighttime' : 'Daytime') : $sSlot;

            $unavailable = [];
            foreach ($allAmenities as $aId) {
                if ($isAmenityRangeTaken((string)$aId, $sDate, $eDate, $sSlot, $eSlot, $reservation->id)) {
                    $unavailable[] = (string)$aId;
                }
            }
            $result[$idx] = $unavailable;
        }

        return response()->json([
            'success' => true,
            'availability' => $result,
        ]);
    })->name('reservations.check-amenities-availability');

    Route::post('/reservations/{reservation}/update', function (Request $request, Reservation $reservation) use ($isAmenityRangeTaken, $calculateContinuousSlotsCount, $continuousSlotTimeline, $formatLocalDate, $computeReservationCheckoutAt) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'booker_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'reservation_date' => 'required|date',
            'end_date' => 'nullable|date',
            'start_slot' => 'nullable|string',
            'end_slot' => 'nullable|string',
            'number_of_guests' => 'required|integer|min:1',
            'status' => 'required|in:Pending,Confirmed,Checked In,Checked Out,Cancelled',
            'amenities' => 'nullable|array',
            'amenities.*.id' => 'nullable',
            'amenities.*.amenity_id' => 'nullable|string',
            'amenities.*.start_date' => 'nullable|date',
            'amenities.*.end_date' => 'nullable|date',
            'amenities.*.start_slot' => 'nullable|string',
            'amenities.*.end_slot' => 'nullable|string',
        ]);

        $currentStartDate = $formatLocalDate($reservation, 'reservation_date');
        $currentEndDate = $formatLocalDate($reservation, 'end_date') ?: $currentStartDate;
        $currentStartSlot = $reservation->start_slot ?? 'Daytime';
        $currentEndSlot = $reservation->end_slot ?? 'Daytime';

        $newStartDate = \Illuminate\Support\Carbon::parse($validated['reservation_date'])->toDateString();
        $newEndDate = !empty($validated['end_date'])
            ? \Illuminate\Support\Carbon::parse($validated['end_date'])->toDateString()
            : $newStartDate;
        
        $newStartSlot = !empty($validated['start_slot']) ? (str_contains($validated['start_slot'], 'Night') ? 'Nighttime' : 'Daytime') : 'Daytime';
        $newEndSlot = !empty($validated['end_slot']) ? (str_contains($validated['end_slot'], 'Night') ? 'Nighttime' : 'Daytime') : $newStartSlot;

        if ($newStartDate > $newEndDate) {
            $newEndDate = $newStartDate;
        }

        $scheduleChanged = ($newStartDate !== $currentStartDate)
            || ($newEndDate !== $currentEndDate)
            || ($newStartSlot !== $currentStartSlot)
            || ($newEndSlot !== $currentEndSlot);

        // Calculate day shift delta between old and new master start date
        $daysShift = 0;
        if ($currentStartDate && $newStartDate) {
            $daysShift = (int) round(\Illuminate\Support\Carbon::parse($currentStartDate)->diffInDays(\Illuminate\Support\Carbon::parse($newStartDate), false));
        }

        $mainCounts = $calculateContinuousSlotsCount($newStartDate, $newEndDate, $newStartSlot, $newEndSlot);
        $existingAmenities = $reservation->reservationAmenities()->with('amenity')->get();
        $submittedAmenities = $request->input('amenities', []);

        // Build target configuration for each existing amenity
        $preparedAmenities = [];
        $targetAmenityCount = $existingAmenities->count();

        for ($i = 0; $i < $targetAmenityCount; $i++) {
            $ra = $existingAmenities[$i];
            $sub = $submittedAmenities[$i] ?? ($submittedAmenities[(string)$ra->id] ?? null);

            $targetAmenityId = !empty($sub['amenity_id']) ? $sub['amenity_id'] : $ra->amenity_id;
            
            // Determine start and end date for this amenity
            $oldRaStartDate = $formatLocalDate($ra, 'start_date') ?: $currentStartDate;
            $oldRaEndDate = $formatLocalDate($ra, 'end_date') ?: ($currentEndDate ?: $oldRaStartDate);
            $isFullStayAmenity = ($oldRaStartDate === $currentStartDate && $oldRaEndDate === $currentEndDate);

            if (!empty($sub['start_date'])) {
                $targetStartDate = \Illuminate\Support\Carbon::parse($sub['start_date'])->toDateString();
            } elseif ($scheduleChanged && $isFullStayAmenity) {
                $targetStartDate = $newStartDate;
            } elseif ($scheduleChanged && $daysShift !== 0 && $oldRaStartDate) {
                $targetStartDate = \Illuminate\Support\Carbon::parse($oldRaStartDate)->addDays($daysShift)->toDateString();
            } else {
                $targetStartDate = $oldRaStartDate ?: $newStartDate;
            }

            if (!empty($sub['end_date'])) {
                $targetEndDate = \Illuminate\Support\Carbon::parse($sub['end_date'])->toDateString();
            } elseif ($scheduleChanged && $isFullStayAmenity) {
                $targetEndDate = $newEndDate;
            } elseif ($scheduleChanged && $daysShift !== 0 && $oldRaEndDate) {
                $targetEndDate = \Illuminate\Support\Carbon::parse($oldRaEndDate)->addDays($daysShift)->toDateString();
            } else {
                $targetEndDate = $oldRaEndDate ?: $targetStartDate;
            }

            if (!empty($sub['start_slot'])) {
                $targetStartSlot = str_contains($sub['start_slot'], 'Night') ? 'Nighttime' : 'Daytime';
            } elseif ($scheduleChanged && $isFullStayAmenity) {
                $targetStartSlot = $newStartSlot;
            } else {
                $targetStartSlot = $ra->start_slot ?: $newStartSlot;
            }

            if (!empty($sub['end_slot'])) {
                $targetEndSlot = str_contains($sub['end_slot'], 'Night') ? 'Nighttime' : 'Daytime';
            } elseif ($scheduleChanged && $isFullStayAmenity) {
                $targetEndSlot = $newEndSlot;
            } else {
                $targetEndSlot = $ra->end_slot ?: $newEndSlot;
            }

            // Clamp amenity stay dates within master reservation bounds
            if ($targetStartDate < $newStartDate) {
                $targetStartDate = $newStartDate;
            }
            if ($targetEndDate > $newEndDate) {
                $targetEndDate = $newEndDate;
            }
            if ($targetStartDate > $targetEndDate) {
                $targetEndDate = $targetStartDate;
            }

            $preparedAmenities[] = [
                'ra' => $ra,
                'amenity_id' => $targetAmenityId,
                'start_date' => $targetStartDate,
                'end_date' => $targetEndDate,
                'start_slot' => $targetStartSlot,
                'end_slot' => $targetEndSlot,
            ];
        }

        // Check availability for each attached amenity
        foreach ($preparedAmenities as $item) {
            $amenityId = $item['amenity_id'];
            if (empty($amenityId)) continue;

            if ($isAmenityRangeTaken($amenityId, $item['start_date'], $item['end_date'], $item['start_slot'], $item['end_slot'], $reservation->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'cant change date cause amenity aavailed is not available on selected date',
                ], 409);
            }
        }

        // Also check conflicts among prepared amenities in the SAME edit payload
        for ($i = 0; $i < count($preparedAmenities); $i++) {
            for ($j = $i + 1; $j < count($preparedAmenities); $j++) {
                if (!empty($preparedAmenities[$i]['amenity_id']) && $preparedAmenities[$i]['amenity_id'] === $preparedAmenities[$j]['amenity_id']) {
                    // Check date/slot overlap between item $i and item $j
                    $timelineI = $continuousSlotTimeline($preparedAmenities[$i]['start_date'], $preparedAmenities[$i]['end_date'], $preparedAmenities[$i]['start_slot'], $preparedAmenities[$i]['end_slot']);
                    $timelineJ = $continuousSlotTimeline($preparedAmenities[$j]['start_date'], $preparedAmenities[$j]['end_date'], $preparedAmenities[$j]['start_slot'], $preparedAmenities[$j]['end_slot']);
                    
                    $mapI = [];
                    foreach ($timelineI as [$d, $s]) { $mapI["{$d}_{$s}"] = true; }
                    foreach ($timelineJ as [$d, $s]) {
                        if (isset($mapI["{$d}_{$s}"])) {
                            return response()->json([
                                'success' => false,
                                'message' => 'cant change date cause amenity aavailed is not available on selected date',
                            ], 409);
                        }
                    }
                }
            }
        }

        // Update amenities records & calculate updated total
        $newAmenityTotal = 0;
        foreach ($preparedAmenities as $item) {
            /** @var ReservationAmenity $ra */
            $ra = $item['ra'];
            $amenityId = $item['amenity_id'];
            $amenityModel = \App\Models\Amenity::find($amenityId);

            $counts = $calculateContinuousSlotsCount($item['start_date'], $item['end_date'], $item['start_slot'], $item['end_slot']);
            $hasAircon = str_contains((string) $ra->pricing_type, 'Aircon');

            $dayPrice = $amenityModel ? ($hasAircon && $amenityModel->daytime_aircon_price ? (float) $amenityModel->daytime_aircon_price : (float) $amenityModel->daytime_price) : 0;
            $nightPrice = $amenityModel ? ($hasAircon && $amenityModel->nighttime_aircon_price ? (float) $amenityModel->nighttime_aircon_price : (float) $amenityModel->nighttime_price) : 0;

            $quantity = max(1, (int) $ra->quantity);
            $amenityPrice = (($counts['day_count'] * $dayPrice) + ($counts['night_count'] * $nightPrice)) * $quantity;
            $newAmenityTotal += $amenityPrice;

            $pricingType = $counts['days_span'] > 1
                ? "Continuous Stay ({$counts['days_span']}D)" . ($hasAircon ? ' Aircon' : '')
                : (($item['start_slot'] === 'Daytime' && $item['end_slot'] === 'Nighttime') ? ($hasAircon ? 'DayToNight Aircon' : 'DayToNight') : ($hasAircon ? "{$item['start_slot']} Aircon" : $item['start_slot']));

            $ra->update([
                'amenity_id' => $amenityId,
                'start_date' => $item['start_date'],
                'end_date' => $item['end_date'],
                'start_slot' => $item['start_slot'],
                'end_slot' => $item['end_slot'],
                'day_slots_count' => $counts['day_count'],
                'night_slots_count' => $counts['night_count'],
                'pricing_type' => $pricingType,
                'price_at_booking' => round($amenityPrice, 2),
                'remarks' => "Continuous Stay: {$item['start_date']} ({$item['start_slot']}) to {$item['end_date']} ({$item['end_slot']})",
            ]);
        }

        $entranceFeeTotal = (float) ($reservation->entranceFee?->total_amount ?? 0);
        $newTotalAmount = round($newAmenityTotal + $entranceFeeTotal, 2);
        $amountPaid = (float) $reservation->amount_paid;
        $newRemainingBalance = max(0, round($newTotalAmount - $amountPaid, 2));

        $newPaymentStatus = $amountPaid >= $newTotalAmount
            ? 'Paid'
            : ($amountPaid > 0 ? 'Partially Paid' : 'Unpaid');

        $validated['total_amount'] = $newTotalAmount;
        $validated['remaining_balance'] = $newRemainingBalance;
        $validated['payment_status'] = $newPaymentStatus;
        $validated['reservation_date'] = $newStartDate ? now()->parse($newStartDate)->toDateTimeString() : null;
        $validated['end_date'] = $newEndDate ? now()->parse($newEndDate)->toDateTimeString() : null;
        $validated['start_slot'] = $newStartSlot;
        $validated['end_slot'] = $newEndSlot;
        $validated['total_days'] = $mainCounts['days_span'];

        unset($validated['amenities']);
        $reservation->update($validated);

        // Reload relation to return fresh data
        $reservation->load(['reservationAmenities.amenity', 'reservationGuests.customer', 'entranceFee']);

        $checkoutAt = $computeReservationCheckoutAt($reservation);

        return response()->json([
            'success' => true,
            'message' => 'Reservation updated successfully.',
            'reservation' => [
                'id' => $reservation->id,
                'booker_name' => $reservation->booker_name,
                'email' => $reservation->email,
                'phone' => $reservation->phone,
                'reservation_date' => $formatLocalDate($reservation, 'reservation_date'),
                'end_date' => $formatLocalDate($reservation, 'end_date'),
                'start_slot' => $reservation->start_slot ?? 'Daytime',
                'end_slot' => $reservation->end_slot ?? 'Daytime',
                'total_days' => $reservation->total_days ?? 1,
                'number_of_guests' => $reservation->number_of_guests,
                'status' => $reservation->status,
                'total_amount' => (float) $reservation->total_amount,
                'amount_paid' => (float) $reservation->amount_paid,
                'remaining_balance' => (float) $reservation->remaining_balance,
                'payment_status' => $reservation->payment_status,
                'checkout_at' => $checkoutAt?->toIso8601String(),
                'reservation_amenities' => $reservation->reservationAmenities->map(function ($ra) use ($formatLocalDate) {
                    return [
                        'id' => $ra->id,
                        'amenity_id' => $ra->amenity_id,
                        'amenity_name' => $ra->amenity?->amenities_name,
                        'pricing_type' => $ra->pricing_type,
                        'price_at_booking' => (float) $ra->price_at_booking,
                        'quantity' => (int) $ra->quantity,
                        'start_date' => $formatLocalDate($ra, 'start_date'),
                        'end_date' => $formatLocalDate($ra, 'end_date'),
                        'start_slot' => $ra->start_slot,
                        'end_slot' => $ra->end_slot,
                        'day_slots_count' => $ra->day_slots_count,
                        'night_slots_count' => $ra->night_slots_count,
                        'amenity' => [
                            'id' => $ra->amenity?->id,
                            'amenities_name' => $ra->amenity?->amenities_name,
                            'daytime_price' => (float) ($ra->amenity?->daytime_price ?? 0),
                            'nighttime_price' => (float) ($ra->amenity?->nighttime_price ?? 0),
                            'daytime_aircon_price' => $ra->amenity?->daytime_aircon_price !== null ? (float) $ra->amenity->daytime_aircon_price : null,
                            'nighttime_aircon_price' => $ra->amenity?->nighttime_aircon_price !== null ? (float) $ra->amenity->nighttime_aircon_price : null,
                        ],
                    ];
                })->values(),
            ],
        ]);
    })->name('reservations.update');

    Route::get('/reservations/refresh', function (Request $request) use ($computeReservationCheckoutAt, $formatLocalDate) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reservations = Reservation::query()
            ->with(['reservationAmenities.amenity', 'reservationGuests.customer'])
            ->where('reservation_type', 'online')
            ->where('payment_status', '!=', 'Unpaid')
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

        $reservationData = $reservations->mapWithKeys(function ($reservation) use ($computeReservationCheckoutAt, $formatLocalDate) {
            $timeSlots = $reservation->reservationAmenities
                ->pluck('pricing_type')
                ->map(function ($pricingType) {
                    $baseSlot = str_replace([' Aircon', 'Aircon'], '', $pricingType);
                    if (str_contains($baseSlot, 'DayToNight')) return 'DayToNight';
                    if (str_contains($baseSlot, 'NightToDay')) return 'NightToDay';
                    if (str_contains($baseSlot, 'Daytime')) return 'Daytime';
                    if (str_contains($baseSlot, 'Nighttime')) return 'Nighttime';
                    return $baseSlot;
                })
                ->unique()
                ->values()
                ->sort()
                ->toArray();

            $checkoutAt = $computeReservationCheckoutAt($reservation);

            return [$reservation->id => [
                'id' => $reservation->id,
                'booker_name' => $reservation->booker_name,
                'phone' => $reservation->phone,
                'email' => $reservation->email,
                'reservation_date' => $formatLocalDate($reservation, 'reservation_date'),
                'end_date' => $formatLocalDate($reservation, 'end_date'),
                'start_slot' => $reservation->start_slot ?? 'Daytime',
                'end_slot' => $reservation->end_slot ?? 'Daytime',
                'total_days' => $reservation->total_days ?? 1,
                'check_in' => $reservation->check_in,
                'checkout_at' => $checkoutAt?->toIso8601String(),
                'number_of_guests' => $reservation->number_of_guests,
                'status' => $reservation->status,
                'reservation_type' => $reservation->reservation_type,
                'total_amount' => $reservation->total_amount,
                'amount_paid' => $reservation->amount_paid,
                'remaining_balance' => $reservation->remaining_balance,
                'payment_status' => $reservation->payment_status,
                'time_slots' => $timeSlots,
                'reservation_amenities' => $reservation->reservationAmenities->map(function ($reservationAmenity) use ($formatLocalDate) {
                    return [
                        'id' => $reservationAmenity->id,
                        'amenity_id' => $reservationAmenity->amenity_id,
                        'pricing_type' => $reservationAmenity->pricing_type,
                        'price_at_booking' => $reservationAmenity->price_at_booking,
                        'quantity' => $reservationAmenity->quantity,
                        'remarks' => $reservationAmenity->remarks,
                        'start_date' => $formatLocalDate($reservationAmenity, 'start_date'),
                        'end_date' => $formatLocalDate($reservationAmenity, 'end_date'),
                        'start_slot' => $reservationAmenity->start_slot,
                        'end_slot' => $reservationAmenity->end_slot,
                        'day_slots_count' => $reservationAmenity->day_slots_count,
                        'night_slots_count' => $reservationAmenity->night_slots_count,
                        'amenity' => [
                            'id' => $reservationAmenity->amenity?->id,
                            'amenities_name' => $reservationAmenity->amenity?->amenities_name,
                            'daytime_price' => (float) ($reservationAmenity->amenity?->daytime_price ?? 0),
                            'nighttime_price' => (float) ($reservationAmenity->amenity?->nighttime_price ?? 0),
                            'daytime_aircon_price' => $reservationAmenity->amenity?->daytime_aircon_price !== null ? (float) $reservationAmenity->amenity->daytime_aircon_price : null,
                            'nighttime_aircon_price' => $reservationAmenity->amenity?->nighttime_aircon_price !== null ? (float) $reservationAmenity->amenity->nighttime_aircon_price : null,
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

                Mail::to($customer->email)->send(
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

        $staffName = $user['name'] ?? 'Staff User';
        $guestCustomer = $reservationGuest->customer;
        $guestName = trim(($guestCustomer?->first_name ?? '') . ' ' . ($guestCustomer?->last_name ?? '')) ?: 'Guest';
        $resInfo = $reservation ? " (Reservation #{$reservation->id} - {$reservation->booker_name})" : '';
        ActivityLog::log(
            activityType: 'check_out',
            title: 'Guest Checked Out',
            description: "{$guestName} checked out{$resInfo} with {$staffName}",
            reservationId: $reservation?->id,
            actorName: $staffName,
            actorRole: $user['role'] ?? 'staff',
            staffId: (string) ($user['id'] ?? ''),
            metadata: [
                'reservation_guest_id' => $reservationGuest->id,
                'guest_name' => $guestName,
                'all_guests_checked_out' => $allGuestsCheckedOut,
                'staff_name' => $staffName,
            ]
        );

        return response()->json([
            'success' => true,
            'checked_out_at' => $reservationGuest->checked_out_at,
        ]);
    })->name('reservation-guests.checkout');

    Route::post('/reservation-guests/{reservationGuest}/undo-checkout', function (ReservationGuest $reservationGuest) {
        $reservationGuest->update(['checked_out_at' => null]);
        
        $reservation = $reservationGuest->reservation;
        if ($reservation && strtolower(str_replace(' ', '_', $reservation->status ?? '')) === 'checked_out') {
            // Revert reservation status to Checked In if it was automatically checked out
            $reservation->update([
                'check_out' => null,
                'status' => 'Checked In',
            ]);
        }
        
        return response()->json([
            'success' => true,
        ]);
    })->name('reservation-guests.undo-checkout');

    Route::post('/reservations/{reservation}/bulk-companions/check-out', function (Request $request, Reservation $reservation) {
        $user = $request->session()->get('auth_user');
        if (! $user || $user['role'] !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:500'],
            // Optional group scoping (gender · age group · nationality) so a
            // bulk group row only ever checks out ITS OWN members.
            'gender' => ['nullable', 'in:Male,Female'],
            'age_group' => ['nullable', 'in:0-12,13-17,18-59,60+'],
            'is_foreigner' => ['nullable', 'boolean'],
            'pool_access_type' => ['nullable', 'in:with_pool,without_pool,any'],
        ]);

        // Bulk companions are detected by their generated name (they carry no
        // contact details, so each gets its own customer row named "Companion").
        $isBulkName = function ($name): bool {
            $name = strtolower(trim((string) $name));
            return str_starts_with($name, 'bulk') || str_contains($name, 'companion');
        };

        // Bulk age group from the stored representative midpoint age.
        $ageGroupOf = function ($age): string {
            $age = (int) ($age ?? 99);
            return $age <= 12 ? '0-12' : ($age <= 17 ? '13-17' : ($age <= 59 ? '18-59' : '60+'));
        };

        // Active bulk companions (still inside), oldest first so the earliest
        // check-ins leave first. The primary guest is never part of a bulk
        // group, even if their name happens to contain "companion". When a
        // group (gender/age group/nationality/pool_access_type) is supplied,
        // only members of that exact group are considered.
        $poolType = $data['pool_access_type'] ?? 'any';
        $activeBulk = $reservation->reservationGuests()
            ->whereNull('checked_out_at')
            ->where('is_primary_guest', false)
            ->get()
            ->filter(fn ($rg) => $rg->customer && $isBulkName($rg->customer->first_name))
            ->filter(function ($rg) use ($data, $ageGroupOf, $poolType) {
                $c = $rg->customer;
                if (! $c) return false;
                if ($data['gender'] && $c->gender !== $data['gender']) return false;
                if ($data['age_group'] && $ageGroupOf($c->age) !== $data['age_group']) return false;
                if ($data['is_foreigner'] !== null && (bool) $c->is_foreigner !== (bool) $data['is_foreigner']) return false;
                if ($poolType === 'with_pool' && ! (bool) $rg->has_pool_access) return false;
                if ($poolType === 'without_pool' && (bool) $rg->has_pool_access) return false;
                return true;
            })
            ->sortBy('id')
            ->values();

        $requested = (int) $data['count'];
        $toCheckOut = $activeBulk->take(max(1, min($requested, $activeBulk->count())));

        if ($toCheckOut->isEmpty()) {
            return response()->json([
                'success' => true,
                'checked_out' => 0,
                'requested' => $requested,
                'remaining' => 0,
                'message' => 'All bulk companions are already checked out.',
            ]);
        }

        $now = now();
        $checkedOut = 0;
        foreach ($toCheckOut as $rg) {
            $rg->update(['checked_out_at' => $now]);
            $checkedOut++;
        }

        // If every guest in the reservation is now out, close the reservation
        // (mirrors the single-guest checkout route).
        $totalGuests = $reservation->reservationGuests()->count();
        $outGuests = ReservationGuest::where('reservation_id', $reservation->id)
            ->whereNotNull('checked_out_at')
            ->count();
        if ($totalGuests > 0 && $outGuests >= $totalGuests) {
            $reservation->update([
                'check_out' => now()->toDateTimeString(),
                'status' => 'Checked Out',
            ]);
        }

        $staffName = $user['name'] ?? 'Staff User';
        ActivityLog::log(
            activityType: 'check_out',
            title: 'Bulk Companions Checked Out',
            description: "{$checkedOut} companion(s) checked out of Reservation #{$reservation->id} ({$reservation->booker_name}) with {$staffName}",
            reservationId: $reservation->id,
            actorName: $staffName,
            actorRole: $user['role'] ?? 'staff',
            staffId: (string) ($user['id'] ?? ''),
            metadata: [
                'checked_out_count' => $checkedOut,
                'staff_name' => $staffName,
            ]
        );

        return response()->json([
            'success' => true,
            'checked_out' => $checkedOut,
            'requested' => $requested,
            'remaining' => $activeBulk->count() - $checkedOut,
            'message' => "$checkedOut bulk companion(s) checked out successfully.",
        ]);
    })->name('reservations.bulk-companions.checkout');

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
            Mail::to($data['email'])->send(new \App\Mail\StaffSettingsOtpMail($otp, $data['name']));

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

// Fallback route to serve files under /storage/* if the public/storage symlink is missing or unresolved
Route::get('/storage/{path}', function (string $path) {
    $storagePath = storage_path('app/public/' . $path);
    if (file_exists($storagePath) && is_file($storagePath)) {
        return response()->file($storagePath);
    }
    abort(404);
})->where('path', '.*');

