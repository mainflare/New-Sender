<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription as StripeSubscription;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Exception;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    /**
     * Create Stripe customer
     */
    public function createCustomer($email, $name)
    {
        try {
            $customer = Customer::create([
                'email' => $email,
                'name' => $name,
            ]);

            return $customer;
        } catch (Exception $e) {
            throw new Exception("Failed to create Stripe customer: " . $e->getMessage());
        }
    }

    /**
     * Create payment intent
     */
    public function createPaymentIntent($amount, $currency = 'usd', $customerId = null)
    {
        try {
            $params = [
                'amount' => $amount * 100, // Convert to cents
                'currency' => $currency,
                'payment_method_types' => ['card'],
            ];

            if ($customerId) {
                $params['customer'] = $customerId;
            }

            $paymentIntent = PaymentIntent::create($params);

            return $paymentIntent;
        } catch (Exception $e) {
            throw new Exception("Failed to create payment intent: " . $e->getMessage());
        }
    }

    /**
     * Create subscription
     */
    public function createSubscription($customerId, $priceId)
    {
        try {
            $subscription = StripeSubscription::create([
                'customer' => $customerId,
                'items' => [
                    ['price' => $priceId],
                ],
                'payment_behavior' => 'default_incomplete',
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            return $subscription;
        } catch (Exception $e) {
            throw new Exception("Failed to create subscription: " . $e->getMessage());
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription($subscriptionId)
    {
        try {
            $subscription = StripeSubscription::retrieve($subscriptionId);
            $subscription->cancel();

            return $subscription;
        } catch (Exception $e) {
            throw new Exception("Failed to cancel subscription: " . $e->getMessage());
        }
    }

    /**
     * Update subscription
     */
    public function updateSubscription($subscriptionId, $newPriceId)
    {
        try {
            $subscription = StripeSubscription::retrieve($subscriptionId);
            
            StripeSubscription::update($subscriptionId, [
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'price' => $newPriceId,
                    ],
                ],
            ]);

            return $subscription;
        } catch (Exception $e) {
            throw new Exception("Failed to update subscription: " . $e->getMessage());
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook($payload, $signature)
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                env('STRIPE_WEBHOOK_SECRET')
            );

            return $event;
        } catch (Exception $e) {
            throw new Exception("Webhook verification failed: " . $e->getMessage());
        }
    }

    /**
     * Get customer
     */
    public function getCustomer($customerId)
    {
        try {
            return Customer::retrieve($customerId);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve customer: " . $e->getMessage());
        }
    }

    /**
     * Get subscription
     */
    public function getSubscription($subscriptionId)
    {
        try {
            return StripeSubscription::retrieve($subscriptionId);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve subscription: " . $e->getMessage());
        }
    }
}

