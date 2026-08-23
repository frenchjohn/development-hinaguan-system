<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {email?}', function (?string $email = null) {
    $toEmail = $email ?: config('mail.from.address') ?: 'parkhinaguan@gmail.com';
    $this->info("Testing mail delivery to: {$toEmail}");
    $this->line("Mailer: " . config('mail.default'));
    $this->line("Host: " . config('mail.mailers.smtp.host') . ":" . config('mail.mailers.smtp.port'));
    $this->line("Encryption: " . (config('mail.mailers.smtp.encryption') ?: 'none'));
    $this->line("Username: " . config('mail.mailers.smtp.username'));
    $this->line("From: " . config('mail.from.address'));
    $this->line("Queue connection: " . config('queue.default'));

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
        $this->info("✓ Success! Test reservation QR email sent to {$toEmail}.");
    } catch (\Throwable $e) {
        $this->error("✗ Failed to send email: " . $e->getMessage());
    }
})->purpose('Test sending a Reservation QR confirmation email via SMTP');

