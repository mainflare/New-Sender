<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $workspaceId = $request->query('workspace_id');
        
        if (!$workspaceId) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace ID is required'
            ], 400);
        }

        $query = Template::where('workspace_id', $workspaceId);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $templates = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workspace_id' => 'required|exists:workspaces,id',
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:official,custom,quick_reply',
            'category' => 'nullable|string',
            'variables' => 'nullable|array',
            'buttons' => 'nullable|array',
            'header_type' => 'nullable|in:text,image,video,document',
            'header_content' => 'nullable|string',
            'footer' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template = Template::create([
                'workspace_id' => $request->workspace_id,
                'name' => $request->name,
                'content' => $request->content,
                'type' => $request->type,
                'category' => $request->category,
                'variables' => $request->variables ?? [],
                'buttons' => $request->buttons ?? [],
                'header_type' => $request->header_type,
                'header_content' => $request->header_content,
                'footer' => $request->footer,
                'status' => $request->type === 'official' ? 'pending' : null,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template created successfully',
                'data' => $template
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $template = Template::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $template
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'variables' => 'nullable|array',
            'buttons' => 'nullable|array',
            'header_type' => 'nullable|in:text,image,video,document',
            'header_content' => 'nullable|string',
            'footer' => 'nullable|string',
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
            $template = Template::findOrFail($id);
            
            if ($template->type === 'official' && $template->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit approved official templates'
                ], 400);
            }

            $template->update($request->only([
                'name', 'content', 'variables', 'buttons',
                'header_type', 'header_content', 'footer', 'is_active'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Template updated successfully',
                'data' => $template
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $template = Template::findOrFail($id);
            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function use($id)
    {
        try {
            $template = Template::findOrFail($id);
            $template->increment('usage_count');

            return response()->json([
                'success' => true,
                'message' => 'Template usage recorded',
                'data' => $template
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record usage',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
