<?php

namespace App\Mail\Transports;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class GmailWebhookTransport extends AbstractTransport
{
    public function __construct(private ?string $webhookUrl = null)
    {
        parent::__construct();
        $this->webhookUrl = $webhookUrl ?: env('GMAIL_WEBHOOK_URL');
    }

    protected function doSend(SentMessage $message): void
    {
        if (empty($this->webhookUrl)) {
            throw new \RuntimeException('GMAIL_WEBHOOK_URL is not configured in your environment.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $toAddresses = [];
        foreach ($email->getTo() as $to) {
            $toAddresses[] = $to->getAddress();
        }
        $toRecipient = implode(', ', $toAddresses);

        $htmlBody = $email->getHtmlBody() ?: nl2br((string) $email->getTextBody());
        $textBody = $email->getTextBody() ?: strip_tags((string) $email->getHtmlBody());

        $payload = [
            'to' => $toRecipient,
            'subject' => $email->getSubject() ?: 'Hinaguan Nature Park Notification',
            'html' => $htmlBody,
            'text' => $textBody,
            'from_name' => config('mail.from.name') ?: 'Hinaguan Nature Park',
        ];

        $response = Http::withOptions([
            'allow_redirects' => [
                'max' => 5,
                'strict' => false,
                'referer' => true,
                'protocols' => ['https'],
            ],
        ])->timeout(20)->post($this->webhookUrl, $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Google Gmail Webhook delivery failed: ' . $response->body(), $response->status());
        }
    }

    public function __toString(): string
    {
        return 'gmail_api';
    }
}
