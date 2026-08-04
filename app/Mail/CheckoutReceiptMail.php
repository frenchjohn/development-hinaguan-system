<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CheckoutReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Customer $customer,
        public ?Reservation $reservation = null,
        public array $amenities = [],
        public string $checkInDateTime = '',
        public string $checkOutDateTime = '',
        public float $totalCost = 0
    ) {
        if ($this->reservation) {
            $this->reservation->loadMissing(['reservationAmenities.amenity', 'reservationGuests.customer']);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            to: [$this->customer->email],
            subject: 'Your Hinaguan Nature Park Visit Receipt',
        );
    }

    public function content(): Content
    {
        // Get main guest name
        $mainGuestName = null;
        if ($this->reservation) {
            $primaryGuest = $this->reservation->reservationGuests->where('is_primary_guest', true)->first();
            if ($primaryGuest && $primaryGuest->customer) {
                $mainGuestName = $primaryGuest->customer->first_name . ' ' . $primaryGuest->customer->last_name;
            }
        }

        return new Content(
            view: 'emails.checkout-receipt',
            with: [
                'customer' => $this->customer,
                'reservation' => $this->reservation,
                'amenities' => $this->amenities,
                'checkInDateTime' => $this->checkInDateTime,
                'checkOutDateTime' => $this->checkOutDateTime,
                'totalCost' => $this->totalCost,
                'mainGuestName' => $mainGuestName,
                'guestCount' => $this->reservation ? $this->reservation->reservationGuests->count() : 1,
            ],
        );
    }
}
