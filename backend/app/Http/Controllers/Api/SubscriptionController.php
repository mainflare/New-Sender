<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    protected $stripeService;

    // Subscription plans configuration
    private $plans = [
        'free' => [
            'name' => 'Free Plan',
            'price' => 0,
            'max_workspaces' => 1,
            'max_contacts' => 100,
            'max_campaigns_per_month' => 10,
            'max_messages_per_day' => 100,
            'ai_chatbot_enabled' => false,
            'google_maps_scraper_enabled' => false,
            'api_access_enabled' => false,
        ],
        'starter' => [
            'name' => 'Starter Plan',
            'price' => 29.99,
            'max_workspaces' => 3,
            'max_contacts' => 1000,
            'max_campaigns_per_month' => 50,
            'max_messages_per_day' => 500,
            'ai_chatbot_enabled' => true,
            'google_maps_scraper_enabled' => false,
            'api_access_enabled' => false,
        ],
        'professional' => [
            'name' => 'Professional Plan',
            'price' => 79.99,
            'max_workspaces' => 10,
            'max_contacts' => 10000,
            'max_campaigns_per_month' => -1, // unlimited
            'max_messages_per_day' => 2000,
            'ai_chatbot_enabled' => true,
            'google_maps_scraper_enabled' => true,
            'api_access_enabled' => false,
        ],
        'enterprise' => [
            'name' => 'Enterprise Plan',
            'price' => 199.99,
            'max_workspaces' => -1, // unlimited
            'max_contacts' => -1, // unlimited
            'max_campaigns_per_month' => -1, // unlimited
            'max_messages_per_day' => -1, // unlimited
            'ai_chatbot_enabled' => true,
            'google_maps_scraper_enabled' => true,
            'api_access_enabled' => true,
        ],
    ];

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Get available plans
     */
    public function getPlans()
    {
        return response()->json([
            'success' => true,
            'data' => $this->plans
        ]);
    }

    /**
     * Get current subscription
     */
    public function getCurrentSubscription(Request $request)
    {
        try {
            $user = $request->user();
            $subscription = $user->subscription;

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $subscription
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_type' => 'required|in:starter,professional,enterprise',
            'billing_cycle' => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:stripe,paypal,coingate',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $planType = $request->plan_type;
            $billingCycle = $request->billing_cycle;
            
            if (!isset($this->plans[$planType])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid plan type'
                ], 400);
            }

            $plan = $this->plans[$planType];
            $price = $plan['price'];

            // Apply yearly discount (20%)
            if ($billingCycle === 'yearly') {
                $price = $price * 12 * 0.8; // 20% discount
            }

            // Cancel existing subscription if any
            $existingSubscription = $user->subscription;
            if ($existingSubscription && $existingSubscription->status === 'active') {
                $existingSubscription->update(['status' => 'cancelled']);
            }

            // Create new subscription
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_name' => $plan['name'],
                'plan_type' => $planType,
                'price' => $price,
                'billing_cycle' => $billingCycle,
                'status' => 'active',
                'payment_method' => $request->payment_method,
                'max_workspaces' => $plan['max_workspaces'],
                'max_contacts' => $plan['max_contacts'],
                'max_campaigns_per_month' => $plan['max_campaigns_per_month'],
                'max_messages_per_day' => $plan['max_messages_per_day'],
                'ai_chatbot_enabled' => $plan['ai_chatbot_enabled'],
                'google_maps_scraper_enabled' => $plan['google_maps_scraper_enabled'],
                'api_access_enabled' => $plan['api_access_enabled'],
                'expires_at' => $billingCycle === 'monthly' 
                    ? now()->addMonth() 
                    : now()->addYear(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'data' => $subscription
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upgrade/downgrade subscription
     */
    public function changeSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_type' => 'required|in:free,starter,professional,enterprise',
            'billing_cycle' => 'nullable|in:monthly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $subscription = $user->subscription;

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found'
                ], 404);
            }

            $planType = $request->plan_type;
            $plan = $this->plans[$planType];
            $billingCycle = $request->billing_cycle ?? $subscription->billing_cycle;

            $price = $plan['price'];
            if ($billingCycle === 'yearly' && $price > 0) {
                $price = $price * 12 * 0.8;
            }

            $subscription->update([
                'plan_name' => $plan['name'],
                'plan_type' => $planType,
                'price' => $price,
                'billing_cycle' => $billingCycle,
                'max_workspaces' => $plan['max_workspaces'],
                'max_contacts' => $plan['max_contacts'],
                'max_campaigns_per_month' => $plan['max_campaigns_per_month'],
                'max_messages_per_day' => $plan['max_messages_per_day'],
                'ai_chatbot_enabled' => $plan['ai_chatbot_enabled'],
                'google_maps_scraper_enabled' => $plan['google_maps_scraper_enabled'],
                'api_access_enabled' => $plan['api_access_enabled'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription updated successfully',
                'data' => $subscription
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Request $request)
    {
        try {
            $user = $request->user();
            $subscription = $user->subscription;

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found'
                ], 404);
            }

            $subscription->update([
                'status' => 'cancelled',
                'expires_at' => now(),
            ]);

            // Downgrade to free plan
            $freePlan = $this->plans['free'];
            $subscription->update([
                'plan_name' => $freePlan['name'],
                'plan_type' => 'free',
                'price' => 0,
                'max_workspaces' => $freePlan['max_workspaces'],
                'max_contacts' => $freePlan['max_contacts'],
                'max_campaigns_per_month' => $freePlan['max_campaigns_per_month'],
                'max_messages_per_day' => $freePlan['max_messages_per_day'],
                'ai_chatbot_enabled' => false,
                'google_maps_scraper_enabled' => false,
                'api_access_enabled' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully. Downgraded to free plan.',
                'data' => $subscription
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check subscription limits
     */
    public function checkLimits(Request $request)
    {
        try {
            $user = $request->user();
            $subscription = $user->subscription;

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subscription found'
                ], 404);
            }

            $workspacesCount = $user->workspaces()->count();
            
            $limits = [
                'workspaces' => [
                    'current' => $workspacesCount,
                    'max' => $subscription->max_workspaces,
                    'unlimited' => $subscription->max_workspaces === -1,
                    'reached' => $subscription->max_workspaces !== -1 && 
                                $workspacesCount >= $subscription->max_workspaces,
                ],
                'features' => [
                    'ai_chatbot' => $subscription->ai_chatbot_enabled,
                    'google_maps_scraper' => $subscription->google_maps_scraper_enabled,
                    'api_access' => $subscription->api_access_enabled,
                ],
                'subscription' => [
                    'plan_type' => $subscription->plan_type,
                    'status' => $subscription->status,
                    'expires_at' => $subscription->expires_at,
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $limits
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check limits',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
