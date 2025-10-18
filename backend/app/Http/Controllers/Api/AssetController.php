<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    /**
     * Display a listing of assets
     */
    public function index(Request $request)
    {
        $workspaceId = $request->query('workspace_id');
        
        if (!$workspaceId) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace ID is required'
            ], 400);
        }

        $query = Asset::where('workspace_id', $workspaceId);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $assets = $query->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $assets
        ]);
    }

    /**
     * Store a newly created asset
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workspace_id' => 'required|exists:workspaces,id',
            'file' => 'required|file|max:10240', // 10MB max
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $user = $request->user();

            // Generate unique filename
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            
            // Determine file type
            $mimeType = $file->getMimeType();
            $type = $this->determineFileType($mimeType);

            // Store file
            $path = $file->storeAs(
                'assets/' . $request->workspace_id,
                $filename,
                'public'
            );

            // Create asset record
            $asset = Asset::create([
                'workspace_id' => $request->workspace_id,
                'user_id' => $user->id,
                'name' => $request->name ?? $file->getClientOriginalName(),
                'file_name' => $filename,
                'file_path' => $path,
                'mime_type' => $mimeType,
                'file_size' => $file->getSize(),
                'type' => $type,
                'metadata' => [
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => $file->getClientOriginalExtension(),
                ],
            ]);

            // Add URL to response
            $asset->url = $asset->url;

            return response()->json([
                'success' => true,
                'message' => 'Asset uploaded successfully',
                'data' => $asset
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload asset',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified asset
     */
    public function show($id)
    {
        try {
            $asset = Asset::findOrFail($id);
            $asset->url = $asset->url;

            return response()->json([
                'success' => true,
                'data' => $asset
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found'
            ], 404);
        }
    }

    /**
     * Update the specified asset
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $asset = Asset::findOrFail($id);
            $asset->update($request->only(['name']));

            return response()->json([
                'success' => true,
                'message' => 'Asset updated successfully',
                'data' => $asset
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update asset',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified asset
     */
    public function destroy($id)
    {
        try {
            $asset = Asset::findOrFail($id);

            // Delete file from storage
            if (Storage::disk('public')->exists($asset->file_path)) {
                Storage::disk('public')->delete($asset->file_path);
            }

            $asset->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asset deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete asset',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Increment usage count
     */
    public function incrementUsage($id)
    {
        try {
            $asset = Asset::findOrFail($id);
            $asset->increment('usage_count');

            return response()->json([
                'success' => true,
                'message' => 'Usage recorded'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record usage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get storage statistics
     */
    public function getStatistics(Request $request)
    {
        $workspaceId = $request->query('workspace_id');
        
        if (!$workspaceId) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace ID is required'
            ], 400);
        }

        try {
            $assets = Asset::where('workspace_id', $workspaceId);
            
            $stats = [
                'total_assets' => $assets->count(),
                'total_size' => $assets->sum('file_size'),
                'by_type' => [
                    'image' => $assets->where('type', 'image')->count(),
                    'video' => $assets->where('type', 'video')->count(),
                    'audio' => $assets->where('type', 'audio')->count(),
                    'document' => $assets->where('type', 'document')->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine file type from MIME type
     */
    private function determineFileType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } else {
            return 'document';
        }
    }
}
