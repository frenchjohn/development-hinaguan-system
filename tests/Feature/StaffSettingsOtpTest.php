<?php

namespace Tests\Feature;

use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffSettingsOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_profile_changes_require_otp_verification(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $staff = StaffAccount::create([
            'name' => 'Current Staff',
            'email' => 'current@example.com',
            'password' => Hash::make('oldpassword123'),
            'ban_status' => false,
        ]);

        $this->withSession([
            'auth_user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => 'staff',
            ],
        ])->post(route('staff.settings.update'), [
            'name' => 'Updated Staff',
            'email' => 'updated@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\StaffSettingsOtpMail::class, function (\App\Mail\StaffSettingsOtpMail $mail): bool {
            return (string) $mail->otp === (string) session('staff_profile_change.otp')
                && $mail->name === 'Updated Staff';
        });

        $this->assertNotNull(session('staff_profile_change'));
        $otp = session('staff_profile_change.otp');

        $response = $this->withSession(session()->all())
            ->post(route('staff.settings.verify'), [
                'code' => $otp,
            ]);

        $response->assertRedirect(route('staff.settings'));

        $staff->refresh();
        $this->assertSame('Updated Staff', $staff->name);
        $this->assertSame('updated@example.com', $staff->email);
        $this->assertTrue(Hash::check('newpassword123', $staff->password));
    }
}
