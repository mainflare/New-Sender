<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Subscription;
use App\Models\Campaign;
use App\Models\Message;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Middleware to check if user is super admin
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!$request->user()->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Super admin access required.'
                ], 403);
            }
            return $next($request);
        });
    }

    /**
     * Get admin dashboard statistics
     */
    public function dashboard(Request $request)
    {
        try {
            $stats = [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                    'inactive' => User::where('is_active', false)->count(),
                    'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
                ],
                'workspaces' => [
                    'total' => Workspace::count(),
                    'active' => Workspace::where('is_active', true)->count(),
                ],
                'subscriptions' => [
                    'active' => Subscription::where('stripe_status', 'active')->count(),
                    'cancelled' => Subscription::where('stripe_status', 'cancelled')->count(),
                    'trial' => Subscription::whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '>', now())->count(),
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
                    'total' => Payment::where('status', 'completed')->sum('amount'),
                    'this_month' => Payment::where('status', 'completed')
                        ->whereMonth('created_at', now()->month)->sum('amount'),
                    'last_month' => Payment::where('status', 'completed')
                        ->whereMonth('created_at', now()->subMonth()->month)->sum('amount'),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users with pagination and filters
     */
    public function getUsers(Request $request)
    {
        $query = User::query();

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Sort
        $sortBy = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->with('subscription')->paginate($request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get user details
     */
    public function getUserDetails($id)
    {
        $user = User::with(['workspaces', 'subscription', 'activityLogs' => function($q) {
            $q->latest()->limit(10);
        }])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'sometimes|string|max:20',
            'company' => 'sometimes|string|max:255',
            'role' => 'sometimes|in:super_admin,admin,user',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Suspend/Activate user
     */
    public function toggleUserStatus($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'User status updated',
            'data' => ['is_active' => $user->is_active]
        ]);
    }

    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get all subscriptions
     */
    public function getSubscriptions(Request $request)
    {
        $query = Subscription::with('user:id,name,email');

        // Filter by status
        if ($request->has('status')) {
            $query->where('stripe_status', $request->status);
        }

        // Filter by plan
        if ($request->has('plan')) {
            $query->where('plan_name', $request->plan);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription($id)
    {
        $subscription = Subscription::find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        $subscription->update([
            'stripe_status' => 'cancelled',
            'ends_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully'
        ]);
    }

    /**
     * Get all payments
     */
    public function getPayments(Request $request)
    {
        $query = Payment::with('user:id,name,email');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Get system settings
     */
    public function getSettings()
    {
        $settings = DB::table('settings')->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Get system health status
     */
    public function systemHealth()
    {
        try {
            $health = [
                'database' => $this->checkDatabaseConnection(),
                'storage' => $this->checkStorageSpace(),
                'cache' => $this->checkCache(),
                'queue' => $this->checkQueue(),
            ];

            return response()->json([
                'success' => true,
                'data' => $health
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check system health',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection OK'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    /**
     * Check storage space
     */
    private function checkStorageSpace()
    {
        $storagePath = storage_path();
        $freeSpace = disk_free_space($storagePath);
        $totalSpace = disk_total_space($storagePath);
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercentage = ($usedSpace / $totalSpace) * 100;

        return [
            'status' => $usagePercentage > 90 ? 'warning' : 'healthy',
            'free' => $this->formatBytes($freeSpace),
            'total' => $this->formatBytes($totalSpace),
            'used_percentage' => round($usagePercentage, 2),
        ];
    }

    /**
     * Check cache status
     */
    private function checkCache()
    {
        try {
            \Cache::put('health_check', 'ok', 10);
            $value = \Cache::get('health_check');
            return ['status' => $value === 'ok' ? 'healthy' : 'unhealthy'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    /**
     * Check queue status
     */
    private function checkQueue()
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            return [
                'status' => $failedJobs > 100 ? 'warning' : 'healthy',
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Exception $e) {
            return ['status' => 'unknown', 'message' => $e->getMessage()];
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get audit logs
     */
    public function getAuditLogs(Request $request)
    {
        $query = DB::table('activity_logs')->orderBy('created_at', 'desc');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        $logs = $query->paginate($request->query('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}
