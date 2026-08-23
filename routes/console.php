<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {email?}', function (?string $email = null) {
    $toEmail = $email ?: config('mail.from.address') ?: 'parkhinaguan@gmail.com';
    $isCached = app()->configurationIsCached();

    $mailer = config('mail.default');
    $this->info("=========================================");
    $this->info(" Hinaguan Mail Diagnostic Tool");
    $this->info("=========================================");
    $this->line("Config Cached: " . ($isCached ? "⚠️ YES (Cached - run 'php artisan config:clear' to apply changes!)" : "✓ NO (Live)"));
    $this->line("Default Mailer: {$mailer}");
    if ($mailer === 'gmail_api') {
        $url = env('GMAIL_WEBHOOK_URL') ?: config('mail.mailers.gmail_api.endpoint') ?: config('mail.mailers.gmail_api.url') ?: '';
        $this->line("Gmail Webhook:  " . ($url ? substr($url, 0, 45) . '...' : '❌ NOT SET (Add GMAIL_WEBHOOK_URL in Railway!)'));
    } else {
        $this->line("SMTP Host:      " . config('mail.mailers.smtp.host'));
        $this->line("SMTP Port:      " . config('mail.mailers.smtp.port'));
        $this->line("SMTP Encryption:" . (config('mail.mailers.smtp.encryption') ?: 'none'));
        $this->line("SMTP Username:  " . config('mail.mailers.smtp.username'));
    }
    $this->line("From Address:   " . config('mail.from.address'));
    $this->line("Queue Driver:   " . config('queue.default'));
    $this->line("Target Recipient: {$toEmail}");
    $this->line("-----------------------------------------");

    $this->comment("1. Sending raw test email via {$mailer}...");
    try {
        \Illuminate\Support\Facades\Mail::raw('This is a test email from Hinaguan Nature Park SMTP diagnostic.', function ($message) use ($toEmail) {
            $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'parkhinaguan@gmail.com';
            $fromName = config('mail.from.name') ?: 'Hinaguan Nature Park';
            $message->from($fromAddress, $fromName)
                    ->to($toEmail)
                    ->subject('Hinaguan SMTP Connectivity Test');
        });
        $this->info("✓ Success: Raw test email sent to {$toEmail}!");
    } catch (\Throwable $e) {
        $this->error("✗ Raw email delivery failed!");
        $this->error("Error: " . $e->getMessage());
        $this->error("Type: " . get_class($e));
    }

    $this->line("-----------------------------------------");
    $this->comment("2. Sending Reservation QR Code email template...");
    try {
        $dummyRes = new \App\Models\Reservation([
            'id' => 9999,
            'booker_name' => 'Railway Test Booker',
            'phone' => '09123456789',
            'email' => $toEmail,
            'reservation_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'total_amount' => 500,
            'amount_paid' => 250,
            'remaining_balance' => 250,
            'payment_status' => 'Partially Paid',
            'status' => 'Pending',
        ]);

        \Illuminate\Support\Facades\Mail::to($toEmail)->send(new \App\Mail\ReservationQrMail($dummyRes));
        $this->info("✓ Success: Reservation QR email sent to {$toEmail}!");
    } catch (\Throwable $e) {
        $this->error("✗ Reservation QR email delivery failed!");
        $this->error("Error: " . $e->getMessage());
        $this->error("Type: " . get_class($e));
    }

    $this->info("=========================================");
})->purpose('Test sending a Reservation QR confirmation email via SMTP');

