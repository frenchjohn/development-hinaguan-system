<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminEmailChangeOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $name;
    public $newEmail;

    /**
     * Create a new message instance.
     */
    public function __construct($otp, $name, $newEmail)
    {
        $this->otp = $otp;
        $this->name = $name;
        $this->newEmail = $newEmail;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'parkhinaguan@gmail.com';
        $fromName = config('mail.from.name') ?: 'Hinaguan Nature Park';

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: 'Email Change Verification Code - Hinaguan Nature Park',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_email_change_otp',
            with: [
                'otp' => $this->otp,
                'name' => $this->name,
                'newEmail' => $this->newEmail,
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
