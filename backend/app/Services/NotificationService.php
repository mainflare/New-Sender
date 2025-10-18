<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    /**
     * Create an in-app notification
     */
    public function createNotification(User $user, string $type, string $message, array $data = [])
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    /**
     * Send push notification (using FCM or similar service)
     */
    public function sendPushNotification(User $user, string $title, string $body, array $data = [])
    {
        // Check if user has push notifications enabled
        if (!($user->notification_settings['push_notifications'] ?? true)) {
            return;
        }

        // This is a placeholder for Firebase Cloud Messaging integration
        // You would need to:
        // 1. Install firebase/php-jwt package
        // 2. Configure FCM credentials
        // 3. Store device tokens in user table or separate table
        
        try {
            if (isset($user->fcm_token)) {
                // Example FCM implementation
                /*
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . config('services.fcm.server_key'),
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $user->fcm_token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => 'notification_icon',
                        'sound' => 'default',
                    ],
                    'data' => $data,
                ]);
                */
            }
        } catch (\Exception $e) {
            \Log::error('Push notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Notify about new message
     */
    public function notifyNewMessage($message, $recipients)
    {
        foreach ($recipients as $user) {
            $this->createNotification(
                $user,
                'new_message',
                'New message from ' . $message->conversation->contact->name,
                [
                    'message_id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'contact_name' => $message->conversation->contact->name,
                ]
            );

            $this->sendPushNotification(
                $user,
                'New Message',
                substr($message->content, 0, 100),
                ['conversation_id' => $message->conversation_id]
            );
        }
    }

    /**
     * Notify about campaign status
     */
    public function notifyCampaignStatus($campaign, $status)
    {
        $user = $campaign->user;
        
        $messages = [
            'started' => "Campaign '{$campaign->name}' has started",
            'completed' => "Campaign '{$campaign->name}' has completed",
            'failed' => "Campaign '{$campaign->name}' has failed",
        ];

        $this->createNotification(
            $user,
            "campaign_{$status}",
            $messages[$status] ?? "Campaign status updated",
            [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'status' => $status,
            ]
        );

        $this->sendPushNotification(
            $user,
            'Campaign ' . ucfirst($status),
            $messages[$status] ?? "Campaign status updated",
            ['campaign_id' => $campaign->id]
        );
    }

    /**
     * Notify about low credits
     */
    public function notifyLowCredits(User $user, $remainingMessages)
    {
        $this->createNotification(
            $user,
            'low_credits',
            "You have only {$remainingMessages} messages remaining",
            ['remaining' => $remainingMessages]
        );

        $this->sendPushNotification(
            $user,
            'Low Credits',
            "You have only {$remainingMessages} messages remaining. Please upgrade your plan.",
            []
        );
    }

    /**
     * Notify about subscription expiry
     */
    public function notifySubscriptionExpiry(User $user, $daysRemaining)
    {
        $this->createNotification(
            $user,
            'subscription_expiry',
            "Your subscription will expire in {$daysRemaining} days",
            ['days_remaining' => $daysRemaining]
        );

        $this->sendPushNotification(
            $user,
            'Subscription Expiring',
            "Your subscription will expire in {$daysRemaining} days. Please renew to continue using WhatsML.",
            []
        );
    }

    /**
     * Notify team member invitation
     */
    public function notifyTeamInvitation(User $user, $workspace, $invitedBy)
    {
        $this->createNotification(
            $user,
            'team_invitation',
            "{$invitedBy->name} invited you to join {$workspace->name}",
            [
                'workspace_id' => $workspace->id,
                'workspace_name' => $workspace->name,
                'invited_by' => $invitedBy->name,
            ]
        );

        $this->sendPushNotification(
            $user,
            'Team Invitation',
            "{$invitedBy->name} invited you to join {$workspace->name}",
            ['workspace_id' => $workspace->id]
        );
    }

    /**
     * Broadcast notification via WebSocket
     */
    public function broadcastNotification(User $user, array $notification)
    {
        // This would integrate with your WebSocket server
        // You can use Laravel Broadcasting with Pusher, Socket.io, etc.
        
        try {
            // Example: Send to Node.js WebSocket server
            Http::post(config('services.websocket.url') . '/broadcast', [
                'user_id' => $user->id,
                'event' => 'notification',
                'data' => $notification,
            ]);
        } catch (\Exception $e) {
            \Log::error('WebSocket broadcast failed: ' . $e->getMessage());
        }
    }
}


