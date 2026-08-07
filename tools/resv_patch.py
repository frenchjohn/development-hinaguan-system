import sys

path = "resources/views/staff/staff_reservations.blade.php"

with open(path, "rb") as f:
    data = f.read()

text = data.decode("utf-8")

edits = [
    (
        "                                    <th>Booker</th>\n"
        "                                    <th>Reservation date</th>\n"
        "                                    <th>Time Slots</th>\n"
        "                                    <th>Guests</th>\n"
        "                                    <th>Status</th>\n"
        "                                    <th>Amount</th>\n"
        "                                </tr>",
        "                                    <th>Booker</th>\n"
        "                                    <th>Reservation date</th>\n"
        "                                    <th>Session</th>\n"
        "                                    <th>Guests</th>\n"
        "                                    <th>Status</th>\n"
        "                                    <th>Amount</th>\n"
        "                                    <th>Actions</th>\n"
        "                                </tr>",
    ),
    (
        "                                        $timeSlots = $reservationData[$reservation->id]['time_slots'] ?? [];\n"
        "                                    @endphp",
        "                                        $timeSlots = $reservationData[$reservation->id]['time_slots'] ?? [];\n"
        "                                        $initials = collect(explode(' ', trim($reservation->booker_name ?? '?')))\n"
        "                                            ->filter()\n"
        "                                            ->take(2)\n"
        "                                            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))\n"
        "                                            ->implode('') ?: '?';\n"
        "                                    @endphp",
    ),
    (
        "                                        <td>\n"
        "                                            <div class=\"guest-name\">\n"
        "                                                {{ $reservation->booker_name }}\n"
        "                                                @if ($isToday)\n"
        "                                                    <span class=\"today-reservation-badge\">TODAY</span>\n"
        "                                                @endif\n"
        "                                            </div>\n"
        "                                            <div class=\"guest-meta\">{{ $reservation->email }}</div>\n"
        "                                        </td>",
        "                                        <td>\n"
        "                                            <div class=\"resv-booker\">\n"
        "                                                <span class=\"resv-avatar\">{{ $initials }}</span>\n"
        "                                                <div class=\"resv-booker__info\">\n"
        "                                                    <div class=\"guest-name\">\n"
        "                                                        {{ $reservation->booker_name }}\n"
        "                                                        @if ($isToday)\n"
        "                                                            <span class=\"today-reservation-badge\">TODAY</span>\n"
        "                                                        @endif\n"
        "                                                    </div>\n"
        "                                                    <div class=\"guest-meta\">{{ $reservation->email }}</div>\n"
        "                                                </div>\n"
        "                                            </div>\n"
        "                                        </td>",
    ),
    (
        "                                        <td>\u20b1{{ number_format($reservation->total_amount, 2) }}</td>\n"
        "                                    </tr>",
        "                                        <td>\u20b1{{ number_format($reservation->total_amount, 2) }}</td>\n"
        "                                        <td>\n"
        "                                            <button type=\"button\" class=\"resv-row-action\" aria-label=\"View reservation details\">\n"
        "                                                <svg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M9 5l7 7-7 7\"/></svg>\n"
        "                                            </button>\n"
        "                                        </td>\n"
        "                                    </tr>",
    ),
    (
        '                                        <td colspan="6" class="guest-empty">No pending online reservations found.</td>',
        '                                        <td colspan="7" class="guest-empty">No pending online reservations found.</td>',
    ),
]

for old, new in edits:
    count = text.count(old)
    if count != 1:
        print(f"FAIL: expected exactly 1 occurrence, found {count} for: {old[:60]!r}")
        sys.exit(1)
    text = text.replace(old, new)

with open(path + ".new", "wb") as f:
    f.write(text.encode("utf-8"))

print("PATCH_OK")
