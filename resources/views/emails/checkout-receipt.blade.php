<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hinaguan Nature Park Visit Receipt</title>
</head>
<body style="margin:0; padding:24px; background-color:#f5f5f5; font-family:'Courier New', Courier, monospace; color:#333;">
    <div style="max-width:500px; margin:0 auto; background:#ffffff; border:1px solid #ddd; padding:32px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="text-align:center; border-bottom:2px dashed #333; padding-bottom:20px; margin-bottom:20px;">
            <h1 style="margin:0 0 8px; font-size:24px; text-transform:uppercase; letter-spacing:2px;">Hinaguan Nature Park</h1>
            <p style="margin:0; font-size:14px; color:#666;">Official Receipt</p>
            <p style="margin:8px 0 0; font-size:12px; color:#999;">{{ now()->format('F j, Y') }}</p>
        </div>

        <!-- Customer Info -->
        <div style="margin-bottom:20px;">
            <p style="margin:0 0 4px; font-size:12px; text-transform:uppercase; color:#666;">Receipt For:</p>
            <p style="margin:0; font-size:16px; font-weight:bold;">{{ $customer->first_name }} {{ $customer->last_name }}</p>
        </div>

        <!-- Visit Details -->
        <div style="background:#f9f9f9; border:1px solid #eee; padding:16px; margin-bottom:20px;">
            <p style="margin:0 0 12px; font-size:12px; text-transform:uppercase; border-bottom:1px solid #ddd; padding-bottom:8px;">Visit Details</p>
            
            @if($reservation)
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:14px;">Reservation ID:</span>
                <span style="font-size:14px;">{{ $reservation->id }}</span>
            </div>
            
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:14px;">Reservation Date:</span>
                <span style="font-size:14px;">{{ $reservation->reservation_date ? $reservation->reservation_date->format('F j, Y') : 'N/A' }}</span>
            </div>
            @endif
            
            @if($mainGuestName)
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:14px;">Main Guest:</span>
                <span style="font-size:14px;">{{ $mainGuestName }}</span>
            </div>
            @endif
            
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:14px;">Guest Count:</span>
                <span style="font-size:14px;">{{ $guestCount }}</span>
            </div>
            
            @if($reservation)
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:14px;">Type:</span>
                <span style="font-size:14px;">{{ $reservation->reservation_type === 'online' ? 'Online Reservation' : 'Walk In' }}</span>
            </div>
            @else
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:14px;">Type:</span>
                <span style="font-size:14px;">Walk In</span>
            </div>
            @endif
            
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:14px;">Check In:</span>
                <span style="font-size:14px;">{{ $checkInDateTime }}</span>
            </div>
            
            <div style="display:flex; justify-content:space-between; margin-bottom:0;">
                <span style="font-size:14px;">Check Out:</span>
                <span style="font-size:14px;">{{ $checkOutDateTime }}</span>
            </div>
        </div>

        <!-- Amenities -->
        <div style="margin-bottom:20px;">
            <p style="margin:0 0 12px; font-size:12px; text-transform:uppercase; border-bottom:1px solid #ddd; padding-bottom:8px;">Amenities Used:</p>
            
            @if($amenities && count($amenities) > 0)
                @foreach($amenities as $amenity)
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="font-size:14px; flex:1;">{{ $amenity['name'] ?? 'Amenity' }}:</span>
                    <span style="font-size:14px; margin-left:16px; white-space:nowrap;">₱{{ number_format($amenity['price'], 2) }}</span>
                </div>
                @endforeach
            @elseif($reservation && $reservation->reservationAmenities && $reservation->reservationAmenities->count() > 0)
                @foreach($reservation->reservationAmenities as $reservationAmenity)
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="font-size:14px; flex:1;">
                        {{ $reservationAmenity->amenity?->amenities_name ?? 'Amenity' }}
                        @if($reservationAmenity->quantity > 1) (x{{ $reservationAmenity->quantity }})@endif
                        :
                    </span>
                    <span style="font-size:14px; margin-left:16px; white-space:nowrap;">₱{{ number_format($reservationAmenity->price_at_booking * $reservationAmenity->quantity, 2) }}</span>
                </div>
                @endforeach
            @else
                <div style="display:flex; justify-content:space-between; margin-bottom:0;">
                    <span style="font-size:14px; color:#999; flex:1;">None:</span>
                    <span style="font-size:14px; margin-left:16px; white-space:nowrap;">₱0.00</span>
                </div>
            @endif
        </div>

        <!-- Total -->
        <div style="border-top:2px solid #333; border-bottom:2px solid #333; padding:16px 0; margin-bottom:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:16px; font-weight:bold; text-transform:uppercase;">Total Amount:</span>
                <span style="font-size:24px; font-weight:bold;">₱{{ number_format($totalCost, 2) }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align:center; border-top:1px dashed #ddd; padding-top:20px;">
            <p style="margin:0 0 8px; font-size:12px; color:#666;">Thank you for visiting Hinaguan Nature Park!</p>
            <p style="margin:0; font-size:11px; color:#999;">This receipt serves as proof of your visit.</p>
        </div>
    </div>
</body>
</html>
