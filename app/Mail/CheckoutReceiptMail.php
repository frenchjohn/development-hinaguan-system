<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Reservation;
use App\Services\ReceiptPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CheckoutReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $receiptData = [];

    public function __construct(
        public Customer $customer,
        public ?Reservation $reservation = null,
        public array $amenities = [],
        public string $checkInDateTime = '',
        public string $checkOutDateTime = '',
        public float $totalCost = 0
    ) {
        if ($this->reservation && $this->reservation->exists) {
            $this->reservation->loadMissing([
                'reservationAmenities.amenity',
                'reservationGuests.customer',
                'entranceFee',
                'reservationCharges',
            ]);
        }

        $receiptPdfService = app(ReceiptPdfService::class);
        $this->receiptData = $receiptPdfService->buildData(
            customer: $this->customer,
            reservation: $this->reservation,
            amenities: $this->amenities,
            checkInDateTime: $this->checkInDateTime,
            checkOutDateTime: $this->checkOutDateTime,
            totalCost: $this->totalCost
        );
    }

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'parkhinaguan@gmail.com';
        $fromName = config('mail.from.name') ?: 'Hinaguan Nature Park';
        $resId = $this->reservation ? '#' . $this->reservation->id : '';

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            to: [$this->customer->email],
            subject: "Your Hinaguan Nature Park Visit Receipt {$resId}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.checkout-receipt',
            with: array_merge([
                'customer' => $this->customer,
                'reservation' => $this->reservation,
                'amenities' => $this->amenities,
                'checkInDateTime' => $this->checkInDateTime,
                'checkOutDateTime' => $this->checkOutDateTime,
                'totalCost' => $this->receiptData['totalCost'] ?? $this->totalCost,
                'mainGuestName' => $this->receiptData['mainGuestName'] ?? null,
                'guestCount' => $this->receiptData['guestCount'] ?? 1,
            ], $this->receiptData),
        );
    }

    /**
     * Attach the official PDF receipt to the email.
     */
    public function attachments(): array
    {
        try {
            $receiptPdfService = app(ReceiptPdfService::class);
            $pdfOutput = $receiptPdfService->getPdfOutput(
                customer: $this->customer,
                reservation: $this->reservation,
                amenities: $this->amenities,
                checkInDateTime: $this->checkInDateTime,
                checkOutDateTime: $this->checkOutDateTime,
                totalCost: $this->receiptData['totalCost'] ?? $this->totalCost
            );

            $idPart = $this->reservation ? "Res-{$this->reservation->id}" : "Cust-{$this->customer->id}";
            $filename = "Hinaguan-Receipt-{$idPart}.pdf";

            return [
                Attachment::fromData(fn () => $pdfOutput, $filename)
                    ->withMime('application/pdf'),
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to generate PDF receipt attachment: " . $e->getMessage(), [
                'customer_id' => $this->customer->id ?? null,
                'reservation_id' => $this->reservation->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
}
