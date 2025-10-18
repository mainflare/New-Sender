<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkspaceController extends Controller
{
    /**
     * Display a listing of the workspaces
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get workspaces owned by user
        $ownedWorkspaces = $user->workspaces()->with('members')->get();
        
        // Get workspaces where user is a member
        $memberWorkspaces = Workspace::whereHas('members', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('members')->get();
        
        $allWorkspaces = $ownedWorkspaces->merge($memberWorkspaces)->unique('id');

        return response()->json([
            'success' => true,
            'data' => $allWorkspaces
        ]);
    }

    /**
     * Store a newly created workspace
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $subscription = $user->subscription;

            // Check workspace limit
            if ($user->workspaces()->count() >= $subscription->max_workspaces) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workspace limit reached. Please upgrade your subscription.'
                ], 403);
            }

            $workspace = Workspace::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'description' => $request->description,
                'logo' => $request->logo,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workspace created successfully',
                'data' => $workspace
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified workspace
     */
    public function show(Request $request, $id)
    {
        try {
            $workspace = Workspace::with(['members.user', 'whatsappSessions', 'contacts', 'campaigns'])
                ->findOrFail($id);

            // Check access
            if (!$this->userHasAccess($request->user(), $workspace)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $workspace
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not found'
            ], 404);
        }
    }

    /**
     * Update the specified workspace
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $workspace = Workspace::findOrFail($id);

            // Check if user is owner or admin
            if (!$this->userCanManage($request->user(), $workspace)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $workspace->update($request->only(['name', 'description', 'logo', 'is_active']));

            return response()->json([
                'success' => true,
                'message' => 'Workspace updated successfully',
                'data' => $workspace
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified workspace
     */
    public function destroy(Request $request, $id)
    {
        try {
            $workspace = Workspace::findOrFail($id);

            // Check if user is owner
            if ($workspace->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only workspace owner can delete it'
                ], 403);
            }

            $workspace->delete();

            return response()->json([
                'success' => true,
                'message' => 'Workspace deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete workspace',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invite member to workspace
     */
    public function inviteMember(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,manager,agent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $workspace = Workspace::findOrFail($id);

            if (!$this->userCanManage($request->user(), $workspace)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Check if already a member
            $existingMember = WorkspaceMember::where('workspace_id', $id)
                ->where('user_id', $request->user_id)
                ->first();

            if ($existingMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already a member'
                ], 400);
            }

            $member = WorkspaceMember::create([
                'workspace_id' => $id,
                'user_id' => $request->user_id,
                'role' => $request->role,
                'is_active' => true,
                'invited_at' => now(),
                'joined_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Member invited successfully',
                'data' => $member->load('user')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to invite member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user has access to workspace
     */
    private function userHasAccess($user, $workspace)
    {
        if ($workspace->user_id === $user->id) {
            return true;
        }

        return WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user can manage workspace
     */
    private function userCanManage($user, $workspace)
    {
        if ($workspace->user_id === $user->id) {
            return true;
        }

        $member = WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        return $member && in_array($member->role, ['owner', 'admin']);
    }
}
