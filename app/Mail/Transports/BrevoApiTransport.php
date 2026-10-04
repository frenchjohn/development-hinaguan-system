<?php

namespace App\Mail\Transports;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class BrevoApiTransport extends AbstractTransport
{
    public function __construct(private ?string $apiKey = null)
    {
        parent::__construct();
        $this->apiKey = $apiKey ?: env('BREVO_API_KEY') ?: env('MAIL_PASSWORD');
    }

    protected function doSend(SentMessage $message): void
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('BREVO_API_KEY is not configured in your environment.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $to = [];
        foreach ($email->getTo() as $address) {
            $to[] = [
                'email' => $address->getAddress(),
                'name' => $address->getName() ?: null,
            ];
        }

        $fromAddress = $email->getFrom()[0] ?? null;
        $sender = [
            'email' => $fromAddress ? $fromAddress->getAddress() : (config('mail.from.address') ?: 'parkhinaguan@gmail.com'),
            'name' => $fromAddress && $fromAddress->getName() ? $fromAddress->getName() : (config('mail.from.name') ?: 'Hinaguan Nature Park'),
        ];

        $payload = [
            'sender' => $sender,
            'to' => $to,
            'subject' => $email->getSubject() ?: 'Hinaguan Nature Park Notification',
            'htmlContent' => $email->getHtmlBody() ?: nl2br((string) $email->getTextBody()),
        ];

        if (!empty($email->getTextBody())) {
            $payload['textContent'] = $email->getTextBody();
        }

        // Attachments support
        $attachments = [];
        foreach ($email->getAttachments() as $att) {
            $attachments[] = [
                'name' => $att->getFilename() ?: 'pass.pdf',
                'content' => base64_encode($att->getBody()),
            ];
        }
        if (!empty($attachments)) {
            $payload['attachment'] = $attachments;
        }

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(25)->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Brevo API delivery failed (HTTP ' . $response->status() . '): ' . $response->body(), $response->status());
        }
    }

    public function __toString(): string
    {
        return 'brevo_api';
    }
}
