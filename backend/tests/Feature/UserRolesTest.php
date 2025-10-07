<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserRolesTest extends TestCase
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

    public function test_can_add_multiple_roles_to_user(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/users/{$this->user->id}/job-roles", [
            'roles' => ['Software Engineer', 'Product Manager', 'Data Analyst'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'added' => [
                        '*' => ['role_id', 'role_name']
                    ],
                    'skipped' => []
                ]
            ]);

        // Verify roles were created in database
        $this->assertDatabaseHas('roles', ['role_name' => 'Software Engineer']);
        $this->assertDatabaseHas('roles', ['role_name' => 'Product Manager']);
        $this->assertDatabaseHas('roles', ['role_name' => 'Data Analyst']);

        // Verify user-role associations were created
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $this->user->id,
            'role_id' => Role::where('role_name', 'Software Engineer')->first()->id,
        ]);
    }

    public function test_can_add_single_role_to_user(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/users/{$this->user->id}/job-roles/single", [
            'role_name' => 'Software Engineer',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'added' => [
                        '*' => ['role_id', 'role_name']
                    ],
                    'skipped' => []
                ]
            ]);
    }

    public function test_skips_duplicate_roles(): void
    {
        // First, add a role
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/users/{$this->user->id}/job-roles", [
            'roles' => ['Software Engineer'],
        ]);

        // Try to add the same role again
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/users/{$this->user->id}/job-roles", [
            'roles' => ['Software Engineer', 'Product Manager'],
        ]);

        $response->assertStatus(201);
        
        $data = $response->json('data');
        $this->assertCount(1, $data['added']); // Only Product Manager should be added
        $this->assertCount(1, $data['skipped']); // Software Engineer should be skipped
        $this->assertEquals('Software Engineer', $data['skipped'][0]['role_name']);
        $this->assertEquals('User already has this role', $data['skipped'][0]['reason']);
    }

    public function test_can_get_user_roles(): void
    {
        // Add some roles first
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/users/{$this->user->id}/job-roles", [
            'roles' => ['Software Engineer', 'Product Manager'],
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/v1/users/{$this->user->id}/job-roles");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'roles' => [
                        '*' => ['role_id', 'role_name', 'assigned_at']
                    ],
                    'total',
                    'page',
                    'limit'
                ]
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data['roles']);
        $this->assertEquals(2, $data['total']);
    }

    public function test_can_remove_role_from_user(): void
    {
        // Add a role first
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/users/{$this->user->id}/job-roles", [
            'roles' => ['Software Engineer'],
        ]);

        $role = Role::where('role_name', 'Software Engineer')->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/v1/users/{$this->user->id}/job-roles/{$role->id}");

        $response->assertStatus(204);

        // Verify the association was removed
        $this->assertDatabaseMissing('user_roles', [
            'user_id' => $this->user->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_cannot_manage_other_users_roles(): void
    {
        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/users/{$otherUser->id}/job-roles", [
            'roles' => ['Software Engineer'],
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized to manage roles for this user',
                'error_code' => 'ROLES_001',
            ]);
    }

    public function test_validates_role_name_length(): void
    {
        $longRoleName = str_repeat('A', 101); // 101 characters

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson("/api/v1/users/{$this->user->id}/job-roles", [
            'roles' => [$longRoleName],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'ROLES_002',
            ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->postJson("/api/v1/users/{$this->user->id}/job-roles", [
            'roles' => ['Software Engineer'],
        ]);

        $response->assertStatus(401);
    }
}
