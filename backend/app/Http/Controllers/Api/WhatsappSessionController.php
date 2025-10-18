<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WhatsappSessionController extends Controller
{
    /**
     * Display a listing of WhatsApp sessions
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

        $sessions = WhatsappSession::where('workspace_id', $workspaceId)->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Store a newly created WhatsApp session
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workspace_id' => 'required|exists:workspaces,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:web,cloud',
            'meta_credentials' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sessionId = 'session_' . Str::random(16) . '_' . time();

            $session = WhatsappSession::create([
                'workspace_id' => $request->workspace_id,
                'session_id' => $sessionId,
                'name' => $request->name,
                'type' => $request->type,
                'status' => 'initializing',
                'meta_credentials' => $request->meta_credentials,
                'is_active' => true,
            ]);

            // Initialize session in WhatsApp Service
            if ($request->type === 'web') {
                $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL');
                
                $response = Http::post("{$whatsappServiceUrl}/api/whatsapp/session/create", [
                    'sessionId' => $sessionId,
                    'workspaceId' => $request->workspace_id,
                ]);

                if (!$response->successful()) {
                    $session->delete();
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to initialize WhatsApp session'
                    ], 500);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp session created successfully',
                'data' => $session
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified session
     */
    public function show($id)
    {
        try {
            $session = WhatsappSession::with(['campaigns', 'conversations'])->findOrFail($id);

            // Get real-time status from WhatsApp Service
            if ($session->type === 'web') {
                $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL');
                
                try {
                    $response = Http::get("{$whatsappServiceUrl}/api/whatsapp/session/status/{$session->session_id}");
                    if ($response->successful()) {
                        $statusData = $response->json();
                        $session->status = $statusData['data']['status'] ?? $session->status;
                    }
                } catch (\Exception $e) {
                    // Continue with stored status
                }
            }

            return response()->json([
                'success' => true,
                'data' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }
    }

    /**
     * Update the specified session
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
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
            $session = WhatsappSession::findOrFail($id);
            $session->update($request->only(['name', 'is_active']));

            return response()->json([
                'success' => true,
                'message' => 'Session updated successfully',
                'data' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified session
     */
    public function destroy($id)
    {
        try {
            $session = WhatsappSession::findOrFail($id);

            // Destroy session in WhatsApp Service
            if ($session->type === 'web') {
                $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL');
                
                Http::post("{$whatsappServiceUrl}/api/whatsapp/session/destroy", [
                    'sessionId' => $session->session_id,
                ]);
            }

            $session->delete();

            return response()->json([
                'success' => true,
                'message' => 'Session deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get QR code for session
     */
    public function getQRCode($id)
    {
        try {
            $session = WhatsappSession::findOrFail($id);

            if ($session->type !== 'web') {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code is only for web sessions'
                ], 400);
            }

            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL');
            
            $response = Http::get("{$whatsappServiceUrl}/api/whatsapp/session/qr/{$session->session_id}");

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get QR code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disconnect session
     */
    public function disconnect(Request $request, $id)
    {
        try {
            $session = WhatsappSession::findOrFail($id);

            if ($session->type === 'web') {
                $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL');
                
                Http::post("{$whatsappServiceUrl}/api/whatsapp/session/destroy", [
                    'sessionId' => $session->session_id,
                ]);
            }

            $session->update([
                'status' => 'disconnected',
                'is_active' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session disconnected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect session',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
