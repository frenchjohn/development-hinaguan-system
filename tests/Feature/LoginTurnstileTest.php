<?php

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoginTurnstileTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_turnstile_script_and_widget(): void
    {
        $response = $this->get('/park-portal');

        $response->assertOk();
        $response->assertSee('challenges.cloudflare.com/turnstile/v0/api.js');
        $response->assertSee('cf-turnstile');
        $response->assertSee('0x4AAAAAAEfCC0KJOL1zsgrX');
    }

    public function test_login_authenticates_admin_successfully_with_valid_captcha(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/park-portal', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'cf-turnstile-response' => 'test-valid-turnstile-token',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertEquals('admin', session('auth_user.role'));
        $this->assertEquals($admin->id, session('auth_user.id'));
    }

    public function test_login_fails_when_turnstile_verification_fails_in_strict_mode(): void
    {
        Config::set('services.cloudflare.test_turnstile_strict', true);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        AdminAccount::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->from('/park-portal')->post('/park-portal', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'cf-turnstile-response' => 'invalid-token',
        ]);

        $response->assertRedirect('/park-portal');
        $response->assertSessionHas('error', 'Security verification failed. Please verify that you are not a robot.');
        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertNull(session('auth_user'));
    }

    public function test_login_succeeds_when_turnstile_verification_succeeds_in_strict_mode(): void
    {
        Config::set('services.cloudflare.test_turnstile_strict', true);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
                'challenge_ts' => now()->toIso8601String(),
                'hostname' => 'localhost',
            ], 200),
        ]);

        $staff = StaffAccount::create([
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'password' => Hash::make('staffpass123'),
        ]);

        $response = $this->from('/park-portal')->post('/park-portal', [
            'email' => 'staff@example.com',
            'password' => 'staffpass123',
            'cf-turnstile-response' => 'valid-turnstile-token',
        ]);

        $response->assertRedirect('/staff/dashboard');
        $this->assertEquals('staff', session('auth_user.role'));
        $this->assertEquals($staff->id, session('auth_user.id'));
    }
}
