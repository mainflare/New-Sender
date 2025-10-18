<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\ChatbotTrainingData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
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

        $chatbots = Chatbot::where('workspace_id', $workspaceId)->get();

        return response()->json([
            'success' => true,
            'data' => $chatbots
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workspace_id' => 'required|exists:workspaces,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ai_provider' => 'required|in:openai,gemini',
            'model' => 'nullable|string',
            'system_prompt' => 'nullable|string',
            'keywords' => 'nullable|array',
            'keyword_responses' => 'nullable|array',
            'use_nlp' => 'nullable|boolean',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $chatbot = Chatbot::create([
                'workspace_id' => $request->workspace_id,
                'name' => $request->name,
                'description' => $request->description,
                'ai_provider' => $request->ai_provider,
                'model' => $request->model ?? ($request->ai_provider === 'openai' ? 'gpt-4' : 'gemini-pro'),
                'system_prompt' => $request->system_prompt,
                'keywords' => $request->keywords ?? [],
                'keyword_responses' => $request->keyword_responses ?? [],
                'use_nlp' => $request->use_nlp ?? true,
                'is_active' => true,
                'temperature' => $request->temperature ?? 0.7,
                'max_tokens' => $request->max_tokens ?? 500,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chatbot created successfully',
                'data' => $chatbot
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create chatbot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $chatbot = Chatbot::with('trainingData')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $chatbot
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Chatbot not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $chatbot = Chatbot::findOrFail($id);
            $chatbot->update($request->only([
                'name', 'description', 'system_prompt', 'keywords',
                'keyword_responses', 'use_nlp', 'is_active', 'temperature', 'max_tokens'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Chatbot updated successfully',
                'data' => $chatbot
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update chatbot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $chatbot = Chatbot::findOrFail($id);
            $chatbot->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chatbot deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete chatbot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function train(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'training_data' => 'required|array',
            'training_data.*.question' => 'required|string',
            'training_data.*.answer' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $chatbot = Chatbot::findOrFail($id);

            foreach ($request->training_data as $data) {
                ChatbotTrainingData::create([
                    'chatbot_id' => $id,
                    'question' => $data['question'],
                    'answer' => $data['answer'],
                    'metadata' => $data['metadata'] ?? null,
                    'is_active' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Training data added successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add training data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function test(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $chatbot = Chatbot::findOrFail($id);

            // Check for keyword match first
            if (!$chatbot->use_nlp && !empty($chatbot->keywords)) {
                foreach ($chatbot->keywords as $keyword) {
                    if (stripos($request->message, $keyword) !== false) {
                        $response = $chatbot->keyword_responses[$keyword] ?? 'I understand you mentioned ' . $keyword;
                        
                        return response()->json([
                            'success' => true,
                            'data' => [
                                'response' => $response,
                                'type' => 'keyword'
                            ]
                        ]);
                    }
                }
            }

            // Use AI if NLP is enabled
            if ($chatbot->use_nlp) {
                $apiKey = $chatbot->ai_provider === 'openai' 
                    ? env('OPENAI_API_KEY') 
                    : env('GOOGLE_GEMINI_API_KEY');

                if (!$apiKey) {
                    return response()->json([
                        'success' => false,
                        'message' => 'AI API key not configured'
                    ], 500);
                }

                if ($chatbot->ai_provider === 'openai') {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $chatbot->model,
                        'messages' => [
                            ['role' => 'system', 'content' => $chatbot->system_prompt ?? 'You are a helpful assistant.'],
                            ['role' => 'user', 'content' => $request->message]
                        ],
                        'temperature' => $chatbot->temperature,
                        'max_tokens' => $chatbot->max_tokens,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $aiResponse = $data['choices'][0]['message']['content'] ?? 'No response';

                        return response()->json([
                            'success' => true,
                            'data' => [
                                'response' => $aiResponse,
                                'type' => 'ai',
                                'provider' => 'openai'
                            ]
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'No response generated'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test chatbot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trainingData($id)
    {
        try {
            $trainingData = ChatbotTrainingData::where('chatbot_id', $id)
                ->where('is_active', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $trainingData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get training data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
