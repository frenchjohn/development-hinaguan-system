<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reservations = Reservation::with(['reservationAmenities.amenity'])->get();

        foreach ($reservations as $res) {
            $actor = $res->reservation_type === 'walk_in' ? 'Staff Admin' : ($res->booker_name ?: 'Online Guest');
            $role = $res->reservation_type === 'walk_in' ? 'staff' : 'guest';

            ActivityLog::create([
                'activity_type' => $res->reservation_type === 'walk_in' ? 'walkin_created' : 'online_booked',
                'title' => $res->reservation_type === 'walk_in' ? 'Walk-In Reservation Created' : 'Online Reservation Booked',
                'description' => "Reservation #{$res->id} ({$res->booker_name}) booked for {$res->reservation_date} ({$res->start_slot}) by {$actor}",
                'reservation_id' => $res->id,
                'actor_name' => $actor,
                'actor_role' => $role,
                'metadata' => [
                    'total_amount' => $res->total_amount,
                    'amount_paid' => $res->amount_paid,
                    'payment_status' => $res->payment_status,
                    'reservation_type' => $res->reservation_type,
                ],
                'created_at' => $res->created_at ?: now()->subDays(2),
            ]);

            if (in_array($res->status, ['Checked In', 'Checked Out'])) {
                ActivityLog::create([
                    'activity_type' => 'check_in',
                    'title' => 'Guest Checked In',
                    'description' => "Reservation #{$res->id} ({$res->booker_name}) checked in by Staff Admin",
                    'reservation_id' => $res->id,
                    'actor_name' => 'Staff Admin',
                    'actor_role' => 'staff',
                    'created_at' => $res->check_in ?: now()->subHours(5),
                ]);
            }

            if ($res->status === 'Checked Out') {
                ActivityLog::create([
                    'activity_type' => 'check_out',
                    'title' => 'Guest Checked Out',
                    'description' => "Reservation #{$res->id} ({$res->booker_name}) checked out by Staff Admin",
                    'reservation_id' => $res->id,
                    'actor_name' => 'Staff Admin',
                    'actor_role' => 'staff',
                    'created_at' => $res->check_out ?: now()->subHour(),
                ]);
            }
        }
    }
}
