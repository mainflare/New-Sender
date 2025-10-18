<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsScraperService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.api_key');
    }

    /**
     * Search for places on Google Maps
     */
    public function searchPlaces(string $query, string $location = '', int $radius = 5000)
    {
        try {
            // Using Google Places API Text Search
            $response = Http::get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
                'query' => $query,
                'location' => $location,
                'radius' => $radius,
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'results' => $this->formatResults($data['results'] ?? []),
                    'next_page_token' => $data['next_page_token'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch data from Google Maps',
                'error' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Google Maps Scraper Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while scraping',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get place details including phone number
     */
    public function getPlaceDetails(string $placeId)
    {
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'name,formatted_address,formatted_phone_number,international_phone_number,website,rating,user_ratings_total,business_status,opening_hours,types',
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['result'])) {
                    return [
                        'success' => true,
                        'data' => $this->formatPlaceDetails($data['result']),
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch place details',
            ];
        } catch (\Exception $e) {
            Log::error('Place Details Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Search nearby places
     */
    public function searchNearby(float $lat, float $lng, string $type = '', int $radius = 5000)
    {
        try {
            $params = [
                'location' => "{$lat},{$lng}",
                'radius' => $radius,
                'key' => $this->apiKey,
            ];

            if ($type) {
                $params['type'] = $type;
            }

            $response = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', $params);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'results' => $this->formatResults($data['results'] ?? []),
                    'next_page_token' => $data['next_page_token'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch nearby places',
            ];
        } catch (\Exception $e) {
            Log::error('Nearby Search Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get next page of results
     */
    public function getNextPage(string $pageToken)
    {
        try {
            // Wait 2 seconds as required by Google API
            sleep(2);

            $response = Http::get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
                'pagetoken' => $pageToken,
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'results' => $this->formatResults($data['results'] ?? []),
                    'next_page_token' => $data['next_page_token'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch next page',
            ];
        } catch (\Exception $e) {
            Log::error('Next Page Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format search results
     */
    protected function formatResults(array $results): array
    {
        return array_map(function ($place) {
            return [
                'place_id' => $place['place_id'] ?? null,
                'name' => $place['name'] ?? null,
                'address' => $place['formatted_address'] ?? null,
                'location' => $place['geometry']['location'] ?? null,
                'rating' => $place['rating'] ?? null,
                'types' => $place['types'] ?? [],
                'business_status' => $place['business_status'] ?? null,
            ];
        }, $results);
    }

    /**
     * Format place details
     */
    protected function formatPlaceDetails(array $place): array
    {
        return [
            'name' => $place['name'] ?? null,
            'address' => $place['formatted_address'] ?? null,
            'phone_number' => $place['formatted_phone_number'] ?? null,
            'international_phone' => $place['international_phone_number'] ?? null,
            'website' => $place['website'] ?? null,
            'rating' => $place['rating'] ?? null,
            'total_ratings' => $place['user_ratings_total'] ?? null,
            'business_status' => $place['business_status'] ?? null,
            'types' => $place['types'] ?? [],
            'opening_hours' => $place['opening_hours'] ?? null,
        ];
    }

    /**
     * Extract phone number and convert to WhatsApp format
     */
    public function extractPhoneNumber(string $phoneNumber): ?string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Ensure it starts with +
        if (!str_starts_with($cleaned, '+')) {
            // Try to add country code if missing (this is a basic implementation)
            // You might want to use a library like libphonenumber-for-php for better accuracy
            $cleaned = '+' . $cleaned;
        }

        // Basic validation
        if (strlen($cleaned) >= 10 && strlen($cleaned) <= 15) {
            return $cleaned;
        }

        return null;
    }

    /**
     * Batch scrape and save to contacts
     */
    public function scrapeAndSaveContacts(string $query, int $workspaceId, string $location = '', int $maxResults = 60)
    {
        $allContacts = [];
        $results = $this->searchPlaces($query, $location);
        
        if (!$results['success']) {
            return $results;
        }

        // Process first page
        foreach ($results['results'] as $result) {
            if (count($allContacts) >= $maxResults) {
                break;
            }

            $details = $this->getPlaceDetails($result['place_id']);
            if ($details['success'] && isset($details['data']['phone_number'])) {
                $phoneNumber = $this->extractPhoneNumber($details['data']['phone_number']);
                
                if ($phoneNumber) {
                    $allContacts[] = [
                        'workspace_id' => $workspaceId,
                        'name' => $details['data']['name'],
                        'phone_number' => $phoneNumber,
                        'email' => null,
                        'custom_fields' => [
                            'address' => $details['data']['address'],
                            'website' => $details['data']['website'],
                            'rating' => $details['data']['rating'],
                            'business_type' => $details['data']['types'][0] ?? null,
                        ],
                        'is_whatsapp_user' => false, // Will be validated separately
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Small delay to respect rate limits
            usleep(100000); // 0.1 second
        }

        // Get next page if needed and available
        $pageToken = $results['next_page_token'];
        while ($pageToken && count($allContacts) < $maxResults) {
            $nextPage = $this->getNextPage($pageToken);
            
            if (!$nextPage['success']) {
                break;
            }

            foreach ($nextPage['results'] as $result) {
                if (count($allContacts) >= $maxResults) {
                    break;
                }

                $details = $this->getPlaceDetails($result['place_id']);
                if ($details['success'] && isset($details['data']['phone_number'])) {
                    $phoneNumber = $this->extractPhoneNumber($details['data']['phone_number']);
                    
                    if ($phoneNumber) {
                        $allContacts[] = [
                            'workspace_id' => $workspaceId,
                            'name' => $details['data']['name'],
                            'phone_number' => $phoneNumber,
                            'email' => null,
                            'custom_fields' => [
                                'address' => $details['data']['address'],
                                'website' => $details['data']['website'],
                                'rating' => $details['data']['rating'],
                                'business_type' => $details['data']['types'][0] ?? null,
                            ],
                            'is_whatsapp_user' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                usleep(100000);
            }

            $pageToken = $nextPage['next_page_token'] ?? null;
        }

        return [
            'success' => true,
            'total_scraped' => count($allContacts),
            'contacts' => $allContacts,
        ];
    }
}


