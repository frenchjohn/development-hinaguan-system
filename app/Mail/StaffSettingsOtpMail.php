<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffSettingsOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int|string $otp,
        public string $name
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'parkhinaguan@gmail.com';
        $fromName = config('mail.from.name') ?: 'Hinaguan Nature Park';

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: 'Hinaguan Nature Park — Verify your profile change',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.staff_settings_otp',
            with: [
                'otp' => $this->otp,
                'name' => $this->name,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
