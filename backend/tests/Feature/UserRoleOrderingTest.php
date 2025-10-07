<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserRoleOrderingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        
        // Create auth token
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_reorder_user_roles(): void
    {
        // Create some roles and assign them to the user
        $role1 = Role::factory()->create(['role_name' => 'Software Engineer']);
        $role2 = Role::factory()->create(['role_name' => 'Product Manager']);
        $role3 = Role::factory()->create(['role_name' => 'Data Analyst']);

        $this->user->roles()->attach([$role1->id, $role2->id, $role3->id]);

        // Reorder the roles
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/v1/users/{$this->user->id}/job-roles/reorder", [
            'role_orders' => [
                ['role_id' => $role2->id, 'display_order' => 1],
                ['role_id' => $role1->id, 'display_order' => 2],
                ['role_id' => $role3->id, 'display_order' => 3],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'updated_count'
                ]
            ]);

        $this->assertEquals(3, $response->json('data.updated_count'));

        // Verify the order by getting user roles
        $getResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/v1/users/{$this->user->id}/job-roles");

        $getResponse->assertStatus(200);
        $roles = $getResponse->json('data.roles');
        
        $this->assertEquals($role2->id, $roles[0]['role_id']); // Product Manager first
        $this->assertEquals($role1->id, $roles[1]['role_id']); // Software Engineer second
        $this->assertEquals($role3->id, $roles[2]['role_id']); // Data Analyst third
    }

    public function test_can_set_single_role_order(): void
    {
        // Create a role and assign it to the user
        $role = Role::factory()->create(['role_name' => 'Software Engineer']);
        $this->user->roles()->attach($role->id);

        // Set the display order
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/v1/users/{$this->user->id}/job-roles/{$role->id}/order", [
            'display_order' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Role order updated successfully',
            ]);

        // Verify the order was set
        $userRole = UserRole::where('user_id', $this->user->id)
            ->where('role_id', $role->id)
            ->first();
        
        $this->assertEquals(5, $userRole->display_order);
    }

    public function test_user_roles_are_ordered_correctly(): void
    {
        // Create roles with different names
        $role1 = Role::factory()->create(['role_name' => 'Zebra Role']);
        $role2 = Role::factory()->create(['role_name' => 'Alpha Role']);
        $role3 = Role::factory()->create(['role_name' => 'Beta Role']);

        // Assign roles to user
        $this->user->roles()->attach([$role1->id, $role2->id, $role3->id]);

        // Set custom order for some roles
        UserRole::where('user_id', $this->user->id)
            ->where('role_id', $role2->id)
            ->update(['display_order' => 1]);
        
        UserRole::where('user_id', $this->user->id)
            ->where('role_id', $role1->id)
            ->update(['display_order' => 2]);

        // Get user roles and verify ordering
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/v1/users/{$this->user->id}/job-roles");

        $response->assertStatus(200);
        $roles = $response->json('data.roles');
        
        // Should be ordered by display_order first, then by name
        $this->assertEquals($role2->id, $roles[0]['role_id']); // Alpha Role (display_order: 1)
        $this->assertEquals($role1->id, $roles[1]['role_id']); // Zebra Role (display_order: 2)
        $this->assertEquals($role3->id, $roles[2]['role_id']); // Beta Role (no display_order, sorted by name)
    }

    public function test_cannot_reorder_roles_that_dont_belong_to_user(): void
    {
        // Create a role that doesn't belong to the user
        $otherRole = Role::factory()->create(['role_name' => 'Other Role']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/v1/users/{$this->user->id}/job-roles/reorder", [
            'role_orders' => [
                ['role_id' => $otherRole->id, 'display_order' => 1],
            ],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'ROLES_003',
            ]);
    }

    public function test_cannot_set_order_for_role_that_dont_belong_to_user(): void
    {
        // Create a role that doesn't belong to the user
        $otherRole = Role::factory()->create(['role_name' => 'Other Role']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/v1/users/{$this->user->id}/job-roles/{$otherRole->id}/order", [
            'display_order' => 1,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'ROLES_003',
            ]);
    }

    public function test_validates_display_order_is_positive_integer(): void
    {
        $role = Role::factory()->create(['role_name' => 'Software Engineer']);
        $this->user->roles()->attach($role->id);

        // Test with zero
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/v1/users/{$this->user->id}/job-roles/{$role->id}/order", [
            'display_order' => 0,
        ]);

        $response->assertStatus(422);

        // Test with negative number
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/v1/users/{$this->user->id}/job-roles/{$role->id}/order", [
            'display_order' => -1,
        ]);

        $response->assertStatus(422);
    }

    public function test_validates_reorder_request_structure(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/v1/users/{$this->user->id}/job-roles/reorder", [
            'role_orders' => [
                ['role_id' => 1], // Missing display_order
            ],
        ]);

        $response->assertStatus(422);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/v1/users/{$this->user->id}/job-roles/reorder", [
            'role_orders' => [
                ['display_order' => 1], // Missing role_id
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_manage_other_users_roles(): void
    {
        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/v1/users/{$otherUser->id}/job-roles/reorder", [
            'role_orders' => [
                ['role_id' => 1, 'display_order' => 1],
            ],
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized to manage roles for this user',
                'error_code' => 'ROLES_001',
            ]);
    }

    public function test_get_user_roles_includes_display_order(): void
    {
        $role = Role::factory()->create(['role_name' => 'Software Engineer']);
        $this->user->roles()->attach($role->id);
        
        // Set display order directly in database
        UserRole::where('user_id', $this->user->id)
            ->where('role_id', $role->id)
            ->update(['display_order' => 3]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/v1/users/{$this->user->id}/job-roles");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'roles' => [
                        '*' => ['role_id', 'role_name', 'display_order', 'assigned_at']
                    ]
                ]
            ]);

        $roles = $response->json('data.roles');
        $this->assertEquals(3, $roles[0]['display_order']);
    }

    public function test_requires_authentication_for_reorder_endpoints(): void
    {
        $response = $this->putJson("/api/v1/users/{$this->user->id}/job-roles/reorder", [
            'role_orders' => [
                ['role_id' => 1, 'display_order' => 1],
            ],
        ]);

        $response->assertStatus(401);

        $response = $this->putJson("/api/v1/users/{$this->user->id}/job-roles/1/order", [
            'display_order' => 1,
        ]);

        $response->assertStatus(401);
    }
}
