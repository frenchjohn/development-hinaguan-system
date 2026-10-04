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

        // Attachments support (e.g. PDF entry pass)
        $attachments = [];
        foreach ($email->getAttachments() as $att) {
            $attachments[] = [
                'name' => $att->getFilename() ?: 'attachment.pdf',
                'mime' => $att->getContentType() ?: 'application/pdf',
                'base64' => base64_encode($att->getBody()),
            ];
        }
        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        $response = Http::withOptions([
            'allow_redirects' => [
                'max' => 5,
                'strict' => true,
                'referer' => true,
                'protocols' => ['https'],
            ],
        ])->timeout(30)->asJson()->post($this->webhookUrl, $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Google Gmail Webhook delivery failed (HTTP ' . $response->status() . '): ' . substr($response->body(), 0, 300), $response->status());
        }

        $json = $response->json();
        if (is_array($json) && isset($json['success']) && $json['success'] === false) {
            throw new \RuntimeException('Google Gmail Webhook error: ' . ($json['error'] ?? 'Unknown script error'));
        }
    }

    public function __toString(): string
    {
        return 'gmail_api';
    }
}
