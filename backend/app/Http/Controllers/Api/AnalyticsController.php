<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request)
    {
        $workspaceId = $request->query('workspace_id');
        
        if (!$workspaceId) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace ID is required'
            ], 400);
        }

        try {
            $stats = [
                'contacts' => [
                    'total' => Contact::where('workspace_id', $workspaceId)->count(),
                    'valid' => Contact::where('workspace_id', $workspaceId)
                        ->where('is_valid', true)->count(),
                    'blocked' => Contact::where('workspace_id', $workspaceId)
                        ->where('is_blocked', true)->count(),
                ],
                'campaigns' => [
                    'total' => Campaign::where('workspace_id', $workspaceId)->count(),
                    'active' => Campaign::where('workspace_id', $workspaceId)
                        ->where('status', 'running')->count(),
                    'completed' => Campaign::where('workspace_id', $workspaceId)
                        ->where('status', 'completed')->count(),
                ],
                'conversations' => [
                    'total' => Conversation::where('workspace_id', $workspaceId)->count(),
                    'open' => Conversation::where('workspace_id', $workspaceId)
                        ->where('status', 'open')->count(),
                    'unread' => Conversation::where('workspace_id', $workspaceId)
                        ->where('unread_count', '>', 0)->count(),
                ],
                'messages' => [
                    'total' => Message::whereHas('conversation', function($q) use ($workspaceId) {
                        $q->where('workspace_id', $workspaceId);
                    })->count(),
                    'today' => Message::whereHas('conversation', function($q) use ($workspaceId) {
                        $q->where('workspace_id', $workspaceId);
                    })->whereDate('created_at', today())->count(),
                    'this_month' => Message::whereHas('conversation', function($q) use ($workspaceId) {
                        $q->where('workspace_id', $workspaceId);
                    })->whereMonth('created_at', now()->month)->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaign analytics
     */
    public function campaignAnalytics(Request $request)
    {
        $workspaceId = $request->query('workspace_id');
        
        if (!$workspaceId) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace ID is required'
            ], 400);
        }

        try {
            $campaigns = Campaign::where('workspace_id', $workspaceId)
                ->select([
                    'id',
                    'name',
                    'type',
                    'status',
                    'total_recipients',
                    'sent_count',
                    'delivered_count',
                    'read_count',
                    'failed_count',
                    'created_at',
                    'completed_at'
                ])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($campaign) {
                    $deliveryRate = $campaign->total_recipients > 0 
                        ? ($campaign->delivered_count / $campaign->total_recipients) * 100 
                        : 0;
                    
                    $readRate = $campaign->delivered_count > 0 
                        ? ($campaign->read_count / $campaign->delivered_count) * 100 
                        : 0;

                    return [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                        'type' => $campaign->type,
                        'status' => $campaign->status,
                        'total_recipients' => $campaign->total_recipients,
                        'sent_count' => $campaign->sent_count,
                        'delivered_count' => $campaign->delivered_count,
                        'read_count' => $campaign->read_count,
                        'failed_count' => $campaign->failed_count,
                        'delivery_rate' => round($deliveryRate, 2),
                        'read_rate' => round($readRate, 2),
                        'created_at' => $campaign->created_at,
                        'completed_at' => $campaign->completed_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $campaigns
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get campaign analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get message analytics
     */
    public function messageAnalytics(Request $request)
    {
        $workspaceId = $request->query('workspace_id');
        $days = $request->query('days', 30);
        
        if (!$workspaceId) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace ID is required'
            ], 400);
        }

        try {
            $messages = Message::whereHas('conversation', function($q) use ($workspaceId) {
                $q->where('workspace_id', $workspaceId);
            })
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, direction')
            ->groupBy('date', 'direction')
            ->orderBy('date')
            ->get();

            $analytics = [
                'daily_messages' => $messages->groupBy('date')->map(function($day) {
                    return [
                        'inbound' => $day->where('direction', 'inbound')->sum('count'),
                        'outbound' => $day->where('direction', 'outbound')->sum('count'),
                        'total' => $day->sum('count'),
                    ];
                }),
                'total_inbound' => $messages->where('direction', 'inbound')->sum('count'),
                'total_outbound' => $messages->where('direction', 'outbound')->sum('count'),
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get message analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get admin statistics (super admin only)
     */
    public function adminStats(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $stats = [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                    'this_month' => User::whereMonth('created_at', now()->month)->count(),
                ],
                'workspaces' => [
                    'total' => Workspace::count(),
                    'active' => Workspace::where('is_active', true)->count(),
                ],
                'campaigns' => [
                    'total' => Campaign::count(),
                    'running' => Campaign::where('status', 'running')->count(),
                    'completed' => Campaign::where('status', 'completed')->count(),
                ],
                'messages' => [
                    'total' => Message::count(),
                    'today' => Message::whereDate('created_at', today())->count(),
                    'this_month' => Message::whereMonth('created_at', now()->month)->count(),
                ],
                'revenue' => [
                    'total' => DB::table('payments')
                        ->where('status', 'completed')
                        ->sum('amount'),
                    'this_month' => DB::table('payments')
                        ->where('status', 'completed')
                        ->whereMonth('created_at', now()->month)
                        ->sum('amount'),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get admin stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
