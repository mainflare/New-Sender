<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignStarted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $campaign;

    /**
     * Create a new notification instance.
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        
        if ($notifiable->notification_settings['email_notifications'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Campaign Started: ' . $this->campaign->name)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your campaign "' . $this->campaign->name . '" has started.')
            ->line('Total Recipients: ' . $this->campaign->total_recipients)
            ->line('Campaign Type: ' . ucfirst($this->campaign->type))
            ->action('View Campaign', url('/campaigns/' . $this->campaign->id))
            ->line('Thank you for using WhatsML!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->name,
            'type' => 'campaign_started',
            'message' => 'Campaign "' . $this->campaign->name . '" has started',
            'total_recipients' => $this->campaign->total_recipients,
        ];
    }
}
