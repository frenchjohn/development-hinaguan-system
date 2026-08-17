<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reports_page_is_accessible_for_admin_users(): void
    {
        $response = $this->withSession([
            'auth_user' => [
                'role' => 'admin',
            ],
        ])->get(route('admin.reports'));

        $response->assertStatus(200);
        $response->assertSee('Admin Reports & Analytics');
    }
}
