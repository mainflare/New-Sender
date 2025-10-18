<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class ContactController extends Controller
{
    /**
     * Display a listing of contacts
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

        $contacts = Contact::where('workspace_id', $workspaceId)
            ->with('audiences')
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
    }

    /**
     * Store a newly created contact
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workspace_id' => 'required|exists:workspaces,id',
            'phone_number' => 'required|string',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'company' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'tags' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check for duplicate
            $existing = Contact::where('workspace_id', $request->workspace_id)
                ->where('phone_number', $request->phone_number)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact already exists'
                ], 400);
            }

            $contact = Contact::create([
                'workspace_id' => $request->workspace_id,
                'phone_number' => $request->phone_number,
                'name' => $request->name,
                'email' => $request->email,
                'company' => $request->company,
                'address' => $request->address,
                'city' => $request->city,
                'country' => $request->country,
                'tags' => $request->tags ?? [],
                'custom_fields' => $request->custom_fields ?? [],
                'source' => 'manual',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contact created successfully',
                'data' => $contact
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contact',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified contact
     */
    public function show($id)
    {
        try {
            $contact = Contact::with(['audiences', 'conversations'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found'
            ], 404);
        }
    }

    /**
     * Update the specified contact
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'company' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'tags' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            'is_blocked' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contact = Contact::findOrFail($id);
            $contact->update($request->only([
                'name', 'email', 'company', 'address', 
                'city', 'country', 'tags', 'custom_fields', 'is_blocked'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Contact updated successfully',
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contact',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified contact
     */
    public function destroy($id)
    {
        try {
            $contact = Contact::findOrFail($id);
            $contact->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contact deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contact',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import contacts from CSV
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workspace_id' => 'required|exists:workspaces,id',
            'contacts' => 'required|array',
            'contacts.*.phone_number' => 'required|string',
            'contacts.*.name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $imported = 0;
            $skipped = 0;

            foreach ($request->contacts as $contactData) {
                $existing = Contact::where('workspace_id', $request->workspace_id)
                    ->where('phone_number', $contactData['phone_number'])
                    ->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                Contact::create([
                    'workspace_id' => $request->workspace_id,
                    'phone_number' => $contactData['phone_number'],
                    'name' => $contactData['name'] ?? null,
                    'email' => $contactData['email'] ?? null,
                    'company' => $contactData['company'] ?? null,
                    'source' => 'import',
                ]);

                $imported++;
            }

            return response()->json([
                'success' => true,
                'message' => "Imported {$imported} contacts, skipped {$skipped} duplicates",
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import contacts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate WhatsApp number
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL');
            
            $response = Http::post("{$whatsappServiceUrl}/api/whatsapp/validate-number", [
                'sessionId' => $request->session_id,
                'phone' => $request->phone_number,
            ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate number',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate multiple WhatsApp numbers
     */
    public function validateBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'phone_numbers' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL');
            
            $response = Http::post("{$whatsappServiceUrl}/api/whatsapp/validate-numbers-bulk", [
                'sessionId' => $request->session_id,
                'phones' => $request->phone_numbers,
            ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate numbers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scrape Google Maps for contacts
     */
    public function scrapeGoogleMaps(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'workspace_id' => 'required|exists:workspaces,id',
            'query' => 'required|string',
            'location' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: Implement Google Maps scraping
        // This would require a separate service or library
        
        return response()->json([
            'success' => false,
            'message' => 'Google Maps scraping feature coming soon'
        ], 501);
    }
}
