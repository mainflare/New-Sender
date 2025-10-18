<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log activity after request is processed
        if (Auth::check() && $this->shouldLog($request)) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    /**
     * Determine if request should be logged
     */
    private function shouldLog(Request $request): bool
    {
        // Don't log certain routes
        $excludedRoutes = [
            'api/me',
            'api/activity-logs',
            'sanctum/csrf-cookie',
        ];

        foreach ($excludedRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                return false;
            }
        }

        // Only log POST, PUT, DELETE methods
        return in_array($request->method(), ['POST', 'PUT', 'DELETE']);
    }

    /**
     * Log the activity
     */
    private function logActivity(Request $request, Response $response): void
    {
        try {
            $user = Auth::user();
            $workspaceId = $request->input('workspace_id') ?? $request->query('workspace_id');

            ActivityLog::create([
                'user_id' => $user->id,
                'workspace_id' => $workspaceId,
                'log_name' => $this->getLogName($request),
                'description' => $this->getDescription($request),
                'properties' => [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                    'status_code' => $response->getStatusCode(),
                    'input' => $this->sanitizeInput($request->except(['password', 'password_confirmation'])),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Silent fail - don't break the application
            \Log::error('Activity logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Get log name based on request
     */
    private function getLogName(Request $request): string
    {
        $path = $request->path();
        
        if (str_contains($path, 'campaigns')) return 'campaign';
        if (str_contains($path, 'contacts')) return 'contact';
        if (str_contains($path, 'workspaces')) return 'workspace';
        if (str_contains($path, 'chatbots')) return 'chatbot';
        if (str_contains($path, 'templates')) return 'template';
        if (str_contains($path, 'conversations')) return 'conversation';
        if (str_contains($path, 'whatsapp')) return 'whatsapp';
        if (str_contains($path, 'subscriptions')) return 'subscription';
        if (str_contains($path, 'payments')) return 'payment';
        
        return 'general';
    }

    /**
     * Get human-readable description
     */
    private function getDescription(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();
        
        $action = match($method) {
            'POST' => 'created',
            'PUT' => 'updated',
            'DELETE' => 'deleted',
            default => 'accessed',
        };

        return ucfirst($this->getLogName($request)) . " {$action}";
    }

    /**
     * Sanitize input data
     */
    private function sanitizeInput(array $input): array
    {
        // Remove sensitive fields
        $sensitiveFields = ['password', 'token', 'api_key', 'secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($input[$field])) {
                $input[$field] = '***REDACTED***';
            }
        }

        return $input;
    }
}
