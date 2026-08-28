<?php

namespace Tests\Feature;

use App\Mail\AdminEmailChangeOtpMail;
use App\Mail\AdminSettingsOtpMail;
use App\Models\AdminAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminSettingsOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_password_otp_is_sent_via_configured_mailer(): void
    {
        Mail::fake();

        $admin = AdminAccount::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->postJson('/admin/send-password-otp', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'OTP sent to recovery email']);

        $admin->refresh();
        $this->assertNotNull($admin->password_otp);

        Mail::assertSent(AdminSettingsOtpMail::class, function (AdminSettingsOtpMail $mail) use ($admin): bool {
            return (string) $mail->otp === (string) $admin->password_otp
                && $mail->name === $admin->name
                && $mail->hasTo($admin->email);
        });

        // Verify and change password
        $verifyResponse = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->postJson('/admin/verify-password-otp', [
            'otp_code' => (string) $admin->password_otp,
            'new_password' => 'newpassword123',
        ]);

        $verifyResponse->assertOk()
            ->assertJson(['message' => 'Password changed successfully']);

        $admin->refresh();
        $this->assertNull($admin->password_otp);
        $this->assertTrue(Hash::check('newpassword123', $admin->password));
    }

    public function test_admin_email_change_otp_is_sent_via_configured_mailer(): void
    {
        Mail::fake();

        $admin = AdminAccount::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->postJson('/admin/send-email-otp', [
            'new_email' => 'newadmin@example.com',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'OTP sent to your current email']);

        $admin->refresh();
        $this->assertNotNull($admin->password_otp);

        Mail::assertSent(AdminEmailChangeOtpMail::class, function (AdminEmailChangeOtpMail $mail) use ($admin): bool {
            return (string) $mail->otp === (string) $admin->password_otp
                && $mail->name === $admin->name
                && $mail->newEmail === 'newadmin@example.com'
                && $mail->hasTo($admin->email);
        });

        // Verify and update email
        $verifyResponse = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->postJson('/admin/verify-email-otp', [
            'otp_code' => (string) $admin->password_otp,
            'new_email' => 'newadmin@example.com',
        ]);

        $verifyResponse->assertOk()
            ->assertJson(['message' => 'Email changed successfully']);

        $admin->refresh();
        $this->assertNull($admin->password_otp);
        $this->assertSame('newadmin@example.com', $admin->email);
    }
}
