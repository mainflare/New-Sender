<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class CampaignController extends Controller
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

        $campaigns = Campaign::where('workspace_id', $workspaceId)
            ->with(['whatsappSession', 'template'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $campaigns
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workspace_id' => 'required|exists:workspaces,id',
            'whatsapp_session_id' => 'required|exists:whatsapp_sessions,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:bulk,scheduled,drip,triggered',
            'message_content' => 'required|string',
            'media_attachments' => 'nullable|array',
            'target_audiences' => 'required|array',
            'delay_between_messages' => 'nullable|integer|min:1',
            'scheduled_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Calculate total recipients
            $totalRecipients = Contact::where('workspace_id', $request->workspace_id)
                ->whereIn('id', $request->target_audiences)
                ->count();

            $campaign = Campaign::create([
                'workspace_id' => $request->workspace_id,
                'whatsapp_session_id' => $request->whatsapp_session_id,
                'template_id' => $request->template_id,
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'status' => 'draft',
                'message_content' => $request->message_content,
                'media_attachments' => $request->media_attachments ?? [],
                'target_audiences' => $request->target_audiences,
                'total_recipients' => $totalRecipients,
                'delay_between_messages' => $request->delay_between_messages ?? 5,
                'scheduled_at' => $request->scheduled_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => $campaign
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $campaign = Campaign::with(['workspace', 'whatsappSession', 'template', 'messages'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $campaign = Campaign::findOrFail($id);

            if (in_array($campaign->status, ['running', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update running or completed campaigns'
                ], 400);
            }

            $campaign->update($request->only([
                'name', 'description', 'message_content', 
                'media_attachments', 'delay_between_messages', 'scheduled_at'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'data' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $campaign = Campaign::findOrFail($id);

            if ($campaign->status === 'running') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete running campaign'
                ], 400);
            }

            $campaign->delete();

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function start(Request $request, $id)
    {
        try {
            $campaign = Campaign::findOrFail($id);

            if ($campaign->status !== 'draft' && $campaign->status !== 'paused') {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign cannot be started'
                ], 400);
            }

            $campaign->update([
                'status' => 'running',
                'started_at' => now(),
            ]);

            // TODO: Dispatch job to process campaign
            // ProcessCampaign::dispatch($campaign);

            return response()->json([
                'success' => true,
                'message' => 'Campaign started successfully',
                'data' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function pause(Request $request, $id)
    {
        try {
            $campaign = Campaign::findOrFail($id);

            if ($campaign->status !== 'running') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only running campaigns can be paused'
                ], 400);
            }

            $campaign->update(['status' => 'paused']);

            return response()->json([
                'success' => true,
                'message' => 'Campaign paused successfully',
                'data' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resume(Request $request, $id)
    {
        try {
            $campaign = Campaign::findOrFail($id);

            if ($campaign->status !== 'paused') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only paused campaigns can be resumed'
                ], 400);
            }

            $campaign->update(['status' => 'running']);

            return response()->json([
                'success' => true,
                'message' => 'Campaign resumed successfully',
                'data' => $campaign
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function analytics($id)
    {
        try {
            $campaign = Campaign::findOrFail($id);

            $deliveryRate = $campaign->total_recipients > 0 
                ? ($campaign->delivered_count / $campaign->total_recipients) * 100 
                : 0;

            $readRate = $campaign->delivered_count > 0 
                ? ($campaign->read_count / $campaign->delivered_count) * 100 
                : 0;

            $analytics = [
                'total_recipients' => $campaign->total_recipients,
                'sent_count' => $campaign->sent_count,
                'delivered_count' => $campaign->delivered_count,
                'read_count' => $campaign->read_count,
                'failed_count' => $campaign->failed_count,
                'delivery_rate' => round($deliveryRate, 2),
                'read_rate' => round($readRate, 2),
                'status' => $campaign->status,
                'started_at' => $campaign->started_at,
                'completed_at' => $campaign->completed_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
