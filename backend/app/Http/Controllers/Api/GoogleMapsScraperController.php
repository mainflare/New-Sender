<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\GoogleMapsScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoogleMapsScraperController extends Controller
{
    protected $scraperService;

    public function __construct(GoogleMapsScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
    }

    /**
     * Search places on Google Maps
     */
    public function searchPlaces(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
            'location' => 'nullable|string',
            'radius' => 'nullable|integer|min:100|max:50000',
        ]);

        $results = $this->scraperService->searchPlaces(
            $request->query,
            $request->location ?? '',
            $request->radius ?? 5000
        );

        return response()->json($results);
    }

    /**
     * Get place details
     */
    public function getPlaceDetails(Request $request)
    {
        $request->validate([
            'place_id' => 'required|string',
        ]);

        $details = $this->scraperService->getPlaceDetails($request->place_id);

        return response()->json($details);
    }

    /**
     * Search nearby places
     */
    public function searchNearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'type' => 'nullable|string',
            'radius' => 'nullable|integer|min:100|max:50000',
        ]);

        $results = $this->scraperService->searchNearby(
            $request->lat,
            $request->lng,
            $request->type ?? '',
            $request->radius ?? 5000
        );

        return response()->json($results);
    }

    /**
     * Get next page of results
     */
    public function getNextPage(Request $request)
    {
        $request->validate([
            'page_token' => 'required|string',
        ]);

        $results = $this->scraperService->getNextPage($request->page_token);

        return response()->json($results);
    }

    /**
     * Scrape and save contacts from Google Maps
     */
    public function scrapeAndSave(Request $request)
    {
        $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'query' => 'required|string',
            'location' => 'nullable|string',
            'max_results' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            // Check user's subscription limits
            $user = $request->user();
            $subscription = $user->subscription;
            
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found'
                ], 403);
            }

            // Get existing contacts count
            $existingContactsCount = Contact::where('workspace_id', $request->workspace_id)->count();
            
            if ($existingContactsCount >= $subscription->contacts_limit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact limit reached. Please upgrade your plan.'
                ], 403);
            }

            // Scrape contacts
            $maxResults = min(
                $request->max_results ?? 60,
                $subscription->contacts_limit - $existingContactsCount
            );

            $results = $this->scraperService->scrapeAndSaveContacts(
                $request->query,
                $request->workspace_id,
                $request->location ?? '',
                $maxResults
            );

            if (!$results['success']) {
                return response()->json($results, 500);
            }

            // Save contacts to database
            $savedCount = 0;
            $duplicateCount = 0;
            $errorCount = 0;

            foreach ($results['contacts'] as $contactData) {
                try {
                    // Check if contact already exists
                    $existing = Contact::where('phone_number', $contactData['phone_number'])
                        ->where('workspace_id', $request->workspace_id)
                        ->first();

                    if ($existing) {
                        $duplicateCount++;
                        continue;
                    }

                    Contact::create($contactData);
                    $savedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    \Log::error('Failed to save contact: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Contacts scraped successfully',
                'data' => [
                    'total_scraped' => $results['total_scraped'],
                    'saved' => $savedCount,
                    'duplicates' => $duplicateCount,
                    'errors' => $errorCount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to scrape contacts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scrape single business details
     */
    public function scrapeSingleBusiness(Request $request)
    {
        $request->validate([
            'place_id' => 'required|string',
            'workspace_id' => 'required|exists:workspaces,id',
        ]);

        try {
            $details = $this->scraperService->getPlaceDetails($request->place_id);

            if (!$details['success'] || !isset($details['data']['phone_number'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No phone number found for this business'
                ], 404);
            }

            $phoneNumber = $this->scraperService->extractPhoneNumber($details['data']['phone_number']);

            if (!$phoneNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number format'
                ], 400);
            }

            // Check if contact already exists
            $existing = Contact::where('phone_number', $phoneNumber)
                ->where('workspace_id', $request->workspace_id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact already exists',
                    'data' => $existing
                ], 409);
            }

            // Create contact
            $contact = Contact::create([
                'workspace_id' => $request->workspace_id,
                'name' => $details['data']['name'],
                'phone_number' => $phoneNumber,
                'custom_fields' => [
                    'address' => $details['data']['address'],
                    'website' => $details['data']['website'],
                    'rating' => $details['data']['rating'],
                    'business_type' => $details['data']['types'][0] ?? null,
                ],
                'is_whatsapp_user' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contact created successfully',
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to scrape business',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
