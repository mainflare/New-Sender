<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test user can create workspace
     */
    public function test_user_can_create_workspace()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/workspaces', [
            'name' => 'Test Workspace',
            'description' => 'Test Description',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                ]
            ]);

        $this->assertDatabaseHas('workspaces', [
            'name' => 'Test Workspace',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test user can list their workspaces
     */
    public function test_user_can_list_workspaces()
    {
        Workspace::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/workspaces');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test user can view workspace details
     */
    public function test_user_can_view_workspace_details()
    {
        $workspace = Workspace::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/workspaces/' . $workspace->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                ]
            ]);
    }

    /**
     * Test user can update workspace
     */
    public function test_user_can_update_workspace()
    {
        $workspace = Workspace::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/workspaces/' . $workspace->id, [
            'name' => 'Updated Workspace',
            'description' => 'Updated Description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Updated Workspace',
        ]);
    }

    /**
     * Test user can delete workspace
     */
    public function test_user_can_delete_workspace()
    {
        $workspace = Workspace::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/workspaces/' . $workspace->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('workspaces', [
            'id' => $workspace->id,
        ]);
    }

    /**
     * Test user cannot access another user's workspace
     */
    public function test_user_cannot_access_another_users_workspace()
    {
        $otherUser = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/workspaces/' . $workspace->id);

        $response->assertStatus(403);
    }

    /**
     * Test workspace name is required
     */
    public function test_workspace_name_is_required()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/workspaces', [
            'description' => 'Test Description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test user can invite members to workspace
     */
    public function test_user_can_invite_members_to_workspace()
    {
        $workspace = Workspace::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $invitedUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/workspaces/' . $workspace->id . '/invite', [
            'user_id' => $invitedUser->id,
            'role' => 'agent',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $invitedUser->id,
            'role' => 'agent',
        ]);
    }
}
