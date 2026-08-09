<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\Reservation::whereNotNull('check_in')
    ->where(function ($query) {
        $query->whereNull('check_out')
              ->orWhereHas('reservationGuests', function ($q) {
                  $q->whereNull('check_out');
              });
    })->count();
echo "ACTIVE_COUNT=" . $count;
