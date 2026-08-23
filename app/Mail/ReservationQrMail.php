<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationQrMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        if ($this->reservation->exists) {
            $this->reservation->loadMissing('reservationGuests');
        }
    }

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'parkhinaguan@gmail.com';
        $fromName = config('mail.from.name') ?: 'Hinaguan Nature Park';

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: 'Your Hinaguan Nature Park reservation QR code',
        );
    }

    public function content(): Content
    {
        $qrPayload = 'reservation_id=' . $this->reservation->id;

        return new Content(
            view: 'emails.reservation-qr',
            with: [
                'reservation' => $this->reservation,
                'qrPayload' => $qrPayload,
                'qrImageUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrPayload),
            ],
        );
    }
}
