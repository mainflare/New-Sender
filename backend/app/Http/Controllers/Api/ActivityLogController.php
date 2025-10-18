<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Get activity logs
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        // Filter by workspace
        if ($request->has('workspace_id')) {
            $query->where('workspace_id', $request->workspace_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by log name
        if ($request->has('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        // Search in description
        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get activity statistics
     */
    public function statistics(Request $request)
    {
        $workspaceId = $request->query('workspace_id');
        
        if (!$workspaceId) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace ID is required'
            ], 400);
        }

        try {
            $query = ActivityLog::where('workspace_id', $workspaceId);

            // Get date range (last 30 days by default)
            $startDate = $request->query('start_date', now()->subDays(30));
            $endDate = $request->query('end_date', now());

            $query->whereBetween('created_at', [$startDate, $endDate]);

            $stats = [
                'total_activities' => $query->count(),
                'by_log_name' => $query->selectRaw('log_name, count(*) as count')
                    ->groupBy('log_name')
                    ->pluck('count', 'log_name'),
                'by_user' => $query->selectRaw('user_id, count(*) as count')
                    ->groupBy('user_id')
                    ->with('user:id,name')
                    ->get()
                    ->pluck('count', 'user.name'),
                'recent_activities' => ActivityLog::where('workspace_id', $workspaceId)
                    ->with('user:id,name')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(),
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
     * Clear old logs
     */
    public function cleanup(Request $request)
    {
        try {
            // Delete logs older than 90 days
            $days = $request->query('days', 90);
            $deleted = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deleted} old activity logs"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
