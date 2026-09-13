<?php

namespace App\Mail;

use App\Models\Reservation;
use App\Services\ReservationPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationQrMail extends Mailable
{
    use Queueable, SerializesModels;

    public $viewData = [];

    public function __construct(public Reservation $reservation)
    {
        if ($this->reservation->exists) {
            $this->reservation->loadMissing([
                'reservationGuests.customer',
                'reservationAmenities.amenity',
                'entranceFee',
            ]);
        }

        $pdfService = app(ReservationPdfService::class);
        $this->viewData = $pdfService->buildData($this->reservation);
    }

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'parkhinaguan@gmail.com';
        $fromName = config('mail.from.name') ?: 'Hinaguan Nature Park';
        $resId = $this->reservation->id ? '#' . $this->reservation->id : '';

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: "Booking Confirmation & Entry Pass {$resId} - Hinaguan Nature Park",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservation-qr',
            with: $this->viewData,
        );
    }

    /**
     * Attach the official PDF Entry Pass to the email.
     */
    public function attachments(): array
    {
        try {
            $pdfService = app(ReservationPdfService::class);
            $pdfOutput = $pdfService->getPdfOutput($this->reservation);

            return [
                Attachment::fromData(fn () => $pdfOutput, "Hinaguan-Pass-{$this->reservation->id}.pdf")
                    ->withMime('application/pdf'),
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to generate PDF pass attachment for reservation #{$this->reservation->id}: " . $e->getMessage());
            return [];
        }
    }
}
