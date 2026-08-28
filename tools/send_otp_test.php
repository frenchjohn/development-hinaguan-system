<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

try {
    $otp = random_int(100000, 999999);
    Mail::to('frenchjohnfamador.s@gmail.com')->send(new \App\Mail\StaffSettingsOtpMail($otp, 'Test User'));
    echo "OTP send attempt complete\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/../storage/logs/send_test_error.log', (string) $e);
}
