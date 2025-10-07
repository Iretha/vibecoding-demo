<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RolesTest extends TestCase
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

    public function test_can_get_all_roles(): void
    {
        // Create some test roles
        Role::factory()->create(['role_name' => 'Software Engineer']);
        Role::factory()->create(['role_name' => 'Product Manager']);
        Role::factory()->create(['role_name' => 'Data Analyst']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'roles' => [
                        '*' => ['role_id', 'role_name', 'user_count']
                    ],
                    'total'
                ]
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data['roles']);
        $this->assertEquals(3, $data['total']);
    }

    public function test_can_search_roles(): void
    {
        // Create some test roles
        Role::factory()->create(['role_name' => 'Software Engineer']);
        Role::factory()->create(['role_name' => 'Software Architect']);
        Role::factory()->create(['role_name' => 'Product Manager']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/roles?search=Software');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data['roles']);
        
        $roleNames = collect($data['roles'])->pluck('role_name')->toArray();
        $this->assertContains('Software Engineer', $roleNames);
        $this->assertContains('Software Architect', $roleNames);
        $this->assertNotContains('Product Manager', $roleNames);
    }

    public function test_can_get_specific_role(): void
    {
        $role = Role::factory()->create(['role_name' => 'Software Engineer']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'role_id',
                    'role_name',
                    'user_count',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => 'Software Engineer',
                ]
            ]);
    }

    public function test_returns_404_for_nonexistent_role(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/roles/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Role not found',
                'error_code' => 'ROLES_005',
            ]);
    }

    public function test_can_get_popular_roles(): void
    {
        // Create roles with different user counts
        $role1 = Role::factory()->create(['role_name' => 'Software Engineer']);
        $role2 = Role::factory()->create(['role_name' => 'Product Manager']);
        $role3 = Role::factory()->create(['role_name' => 'Data Analyst']);

        // Add users to roles to simulate popularity
        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);
        $user3 = User::factory()->create(['email_verified_at' => now()]);

        // Role1 has 3 users (most popular)
        $user1->roles()->attach($role1);
        $user2->roles()->attach($role1);
        $user3->roles()->attach($role1);

        // Role2 has 2 users
        $user1->roles()->attach($role2);
        $user2->roles()->attach($role2);

        // Role3 has 1 user (least popular)
        $user1->roles()->attach($role3);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/roles/popular');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'roles' => [
                        '*' => ['role_id', 'role_name', 'user_count']
                    ],
                    'total'
                ]
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data['roles']);
        
        // Check that roles are ordered by popularity (user_count desc)
        $userCounts = collect($data['roles'])->pluck('user_count')->toArray();
        $this->assertEquals([3, 2, 1], $userCounts);
    }

    public function test_can_get_users_with_specific_role(): void
    {
        $role = Role::factory()->create(['role_name' => 'Software Engineer']);
        
        // Create users and assign them to the role
        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);
        
        $user1->roles()->attach($role);
        $user2->roles()->attach($role);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/v1/roles/{$role->id}/users");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'users' => [
                        '*' => ['user_id', 'name', 'email', 'assigned_at']
                    ],
                    'total',
                    'page',
                    'limit'
                ]
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data['users']);
        $this->assertEquals(2, $data['total']);
    }

    public function test_public_endpoints_work_without_authentication(): void
    {
        // Test that public endpoints work without authentication
        $response = $this->getJson('/api/v1/roles');
        $response->assertStatus(200);

        $response = $this->getJson('/api/v1/roles/popular');
        $response->assertStatus(200);

        // Create a role first
        $role = Role::factory()->create(['role_name' => 'Test Role']);
        $response = $this->getJson("/api/v1/roles/{$role->id}");
        $response->assertStatus(200);
    }

    public function test_requires_authentication(): void
    {
        // Test that protected endpoints still require authentication
        $response = $this->getJson('/api/v1/roles/1/users');

        $response->assertStatus(401);
    }
}
