import sys

path = "routes/web.php"

with open(path, "rb") as f:
    data = f.read()

text = data.decode("utf-8")

old = (
    "        $reservationData = $reservations->mapWithKeys(function ($reservation) {\n"
    "            return [$reservation->id => [\n"
    "                'id' => $reservation->id,\n"
    "                'booker_name' => $reservation->booker_name,\n"
    "                'phone' => $reservation->phone,\n"
    "                'email' => $reservation->email,\n"
    "                'reservation_date' => $reservation->reservation_date,\n"
    "                'check_in' => $reservation->check_in,\n"
    "                'number_of_guests' => $reservation->number_of_guests,\n"
    "                'status' => $reservation->status,\n"
    "                'reservation_type' => $reservation->reservation_type,\n"
    "                'total_amount' => $reservation->total_amount,\n"
    "                'amount_paid' => $reservation->amount_paid,\n"
    "                'remaining_balance' => $reservation->remaining_balance,\n"
    "                'payment_status' => $reservation->payment_status,\n"
    "                'reservation_amenities' => $reservation->reservationAmenities->map(function ($reservationAmenity) {\n"
)

new = (
    "        $reservationData = $reservations->mapWithKeys(function ($reservation) {\n"
    "            // Extract unique time slots from reservation amenities\n"
    "            $timeSlots = $reservation->reservationAmenities\n"
    "                ->pluck('pricing_type')\n"
    "                ->map(function ($pricingType) {\n"
    "                    $baseSlot = str_replace([' Aircon', 'Aircon'], '', $pricingType);\n"
    "                    if (str_contains($baseSlot, 'Daytime')) return 'Daytime';\n"
    "                    if (str_contains($baseSlot, 'Nighttime')) return 'Nighttime';\n"
    "                    if (str_contains($baseSlot, 'DayNight')) return 'DayNight Time';\n"
    "                    return $baseSlot;\n"
    "                })\n"
    "                ->unique()\n"
    "                ->values()\n"
    "                ->sort()\n"
    "                ->toArray();\n"
    "\n"
    "            return [$reservation->id => [\n"
    "                'id' => $reservation->id,\n"
    "                'booker_name' => $reservation->booker_name,\n"
    "                'phone' => $reservation->phone,\n"
    "                'email' => $reservation->email,\n"
    "                'reservation_date' => $reservation->reservation_date,\n"
    "                'check_in' => $reservation->check_in,\n"
    "                'number_of_guests' => $reservation->number_of_guests,\n"
    "                'status' => $reservation->status,\n"
    "                'reservation_type' => $reservation->reservation_type,\n"
    "                'total_amount' => $reservation->total_amount,\n"
    "                'amount_paid' => $reservation->amount_paid,\n"
    "                'remaining_balance' => $reservation->remaining_balance,\n"
    "                'payment_status' => $reservation->payment_status,\n"
    "                'time_slots' => $timeSlots,\n"
    "                'reservation_amenities' => $reservation->reservationAmenities->map(function ($reservationAmenity) {\n"
)

count = text.count(old)
if count != 1:
    print(f"FAIL: expected exactly 1 occurrence, found {count}")
    sys.exit(1)

text = text.replace(old, new)

with open(path + ".new", "wb") as f:
    f.write(text.encode("utf-8"))

print("PATCH_OK")
