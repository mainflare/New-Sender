<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class ConversationController extends Controller
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

        $query = Conversation::where('workspace_id', $workspaceId)
            ->with(['contact', 'assignedUser', 'labels']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by assigned user
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter archived
        if ($request->has('is_archived')) {
            $query->where('is_archived', $request->is_archived);
        }

        $conversations = $query->orderBy('last_message_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    public function show($id)
    {
        try {
            $conversation = Conversation::with([
                'contact', 
                'assignedUser', 
                'labels',
                'whatsappSession'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $conversation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found'
            ], 404);
        }
    }

    public function messages(Request $request, $id)
    {
        try {
            $conversation = Conversation::findOrFail($id);
            
            $messages = Message::where('conversation_id', $id)
                ->orderBy('created_at', 'desc')
                ->paginate($request->query('per_page', 50));

            // Mark as read
            $conversation->update(['unread_count' => 0]);

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendMessage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'media_url' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $conversation = Conversation::with(['contact', 'whatsappSession'])->findOrFail($id);

            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL');
            
            $response = Http::post("{$whatsappServiceUrl}/api/messages/send", [
                'sessionId' => $conversation->whatsappSession->session_id,
                'to' => $conversation->contact->phone_number,
                'message' => $request->message,
            ]);

            if ($response->successful()) {
                $data = $response->json('data');
                
                // Save message to database
                $message = Message::create([
                    'conversation_id' => $id,
                    'whatsapp_session_id' => $conversation->whatsapp_session_id,
                    'message_id' => $data['id'] ?? uniqid(),
                    'type' => 'text',
                    'body' => $request->message,
                    'direction' => 'outbound',
                    'status' => 'sent',
                    'from_number' => $conversation->whatsappSession->phone_number,
                    'to_number' => $conversation->contact->phone_number,
                    'from_me' => true,
                    'sent_at' => now(),
                ]);

                // Update conversation
                $conversation->update(['last_message_at' => now()]);

                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => $message
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assign(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $conversation = Conversation::findOrFail($id);
            $conversation->update(['assigned_to' => $request->user_id]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation assigned successfully',
                'data' => $conversation->load('assignedUser')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign conversation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addLabel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'label_id' => 'required|exists:conversation_labels,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $conversation = Conversation::findOrFail($id);
            $conversation->labels()->syncWithoutDetaching([$request->label_id]);

            return response()->json([
                'success' => true,
                'message' => 'Label added successfully',
                'data' => $conversation->load('labels')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add label',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $conversation = Conversation::findOrFail($id);
            $conversation->update($request->only(['status', 'notes', 'is_archived']));

            return response()->json([
                'success' => true,
                'message' => 'Conversation updated successfully',
                'data' => $conversation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update conversation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $conversation = Conversation::findOrFail($id);
            $conversation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
