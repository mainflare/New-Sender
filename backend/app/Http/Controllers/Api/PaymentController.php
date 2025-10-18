<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Create payment intent
     */
    public function createPaymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'nullable|string|size:3',
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
            
            $paymentIntent = $this->stripeService->createPaymentIntent(
                $request->amount,
                $request->currency ?? 'usd'
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
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

            $payment = Payment::create([
                'user_id' => $user->id,
                'subscription_id' => $request->subscription_id,
                'transaction_id' => $request->payment_intent_id,
                'payment_method' => $request->payment_method,
                'amount' => $request->amount,
                'currency' => strtoupper($request->currency),
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $user = $request->user();
            
            $payments = Payment::where('user_id', $user->id)
                ->with('subscription')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stripe webhook handler
     */
    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = $this->stripeService->verifyWebhook($payload, $signature);

            // Handle the event
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSuccess($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;

                case 'customer.subscription.created':
                    $this->handleSubscriptionCreated($event->data->object);
                    break;

                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdated($event->data->object);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe event: ' . $event->type);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSuccess($paymentIntent)
    {
        Log::info('Payment succeeded: ' . $paymentIntent->id);
        
        // Update payment status if exists
        Payment::where('transaction_id', $paymentIntent->id)
            ->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed($paymentIntent)
    {
        Log::error('Payment failed: ' . $paymentIntent->id);
        
        Payment::where('transaction_id', $paymentIntent->id)
            ->update(['status' => 'failed']);
    }

    /**
     * Handle subscription created
     */
    private function handleSubscriptionCreated($subscription)
    {
        Log::info('Subscription created: ' . $subscription->id);
    }

    /**
     * Handle subscription updated
     */
    private function handleSubscriptionUpdated($subscription)
    {
        Log::info('Subscription updated: ' . $subscription->id);
        
        // Update subscription status in database
        Subscription::where('payment_id', $subscription->id)
            ->update([
                'status' => $subscription->status === 'active' ? 'active' : 'cancelled',
            ]);
    }

    /**
     * Handle subscription deleted
     */
    private function handleSubscriptionDeleted($subscription)
    {
        Log::info('Subscription deleted: ' . $subscription->id);
        
        Subscription::where('payment_id', $subscription->id)
            ->update([
                'status' => 'cancelled',
                'expires_at' => now(),
            ]);
    }
}
