<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdminAccount;
use App\Models\ParkRule;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParkRulesSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected AdminAccount $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminAccount::create([
            'email' => 'admin_rules_test@example.com',
            'name' => 'Admin Tester',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_admin_settings_page_displays_park_rules_card_and_rules_list(): void
    {
        $rule = ParkRule::create([
            'rule_name' => 'Proper Swimwear Policy',
            'rule_descriptions' => 'Only rashguards and swim trunks are allowed in the pool.',
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
            ],
        ])->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertSee('Park Rules');
        $response->assertSee('Proper Swimwear Policy');
        $response->assertSee('Only rashguards and swim trunks are allowed in the pool.');
    }

    public function test_admin_can_create_a_new_park_rule(): void
    {
        $payload = [
            'rule_name' => 'No Glassware Policy',
            'rule_descriptions' => 'Glass bottles and glassware are strictly prohibited near the swimming pool area.',
        ];

        $response = $this->withSession([
            'auth_user' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
            ],
        ])->postJson(route('admin.settings.rules.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Park rule created successfully.',
            'rule' => [
                'rule_name' => 'No Glassware Policy',
                'rule_descriptions' => 'Glass bottles and glassware are strictly prohibited near the swimming pool area.',
            ],
        ]);

        $this->assertDatabaseHas('park_rules', [
            'rule_name' => 'No Glassware Policy',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'rule_created',
            'title' => 'Park Rule Created',
        ]);
    }

    public function test_admin_can_update_an_existing_park_rule(): void
    {
        $rule = ParkRule::create([
            'rule_name' => 'Old Rule Title',
            'rule_descriptions' => 'Old description text here.',
        ]);

        $payload = [
            'rule_name' => 'Updated Rule Title',
            'rule_descriptions' => 'Updated description text here.',
        ];

        $response = $this->withSession([
            'auth_user' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
            ],
        ])->putJson(route('admin.settings.rules.update', $rule->id), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Park rule updated successfully.',
            'rule' => [
                'id' => $rule->id,
                'rule_name' => 'Updated Rule Title',
                'rule_descriptions' => 'Updated description text here.',
            ],
        ]);

        $this->assertDatabaseHas('park_rules', [
            'id' => $rule->id,
            'rule_name' => 'Updated Rule Title',
            'rule_descriptions' => 'Updated description text here.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'rule_updated',
            'title' => 'Park Rule Updated',
        ]);
    }

    public function test_admin_can_delete_a_park_rule(): void
    {
        $rule = ParkRule::create([
            'rule_name' => 'Rule To Delete',
            'rule_descriptions' => 'This rule will be removed.',
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
            ],
        ])->deleteJson(route('admin.settings.rules.delete', $rule->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Park rule deleted successfully.',
        ]);

        $this->assertDatabaseMissing('park_rules', [
            'id' => $rule->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'rule_deleted',
            'title' => 'Park Rule Deleted',
        ]);
    }

    public function test_validation_fails_for_empty_rule_fields(): void
    {
        $response = $this->withSession([
            'auth_user' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
            ],
        ])->postJson(route('admin.settings.rules.store'), [
            'rule_name' => '',
            'rule_descriptions' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rule_name', 'rule_descriptions']);
    }

    public function test_non_admin_cannot_access_or_modify_park_rules(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Staff User',
            'email' => 'staff_unauth@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => 'staff',
            ],
        ])->postJson(route('admin.settings.rules.store'), [
            'rule_name' => 'Unauthorized Rule',
            'rule_descriptions' => 'Should fail',
        ]);

        $response->assertStatus(401);
    }
}
