<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessage extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
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
        $conversation = $this->message->conversation;
        $contact = $conversation->contact;

        return (new MailMessage)
            ->subject('New Message from ' . $contact->name)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You have received a new message from ' . $contact->name)
            ->line(substr($this->message->content, 0, 100) . '...')
            ->action('View Conversation', url('/conversations/' . $conversation->id))
            ->line('Thank you for using WhatsML!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $conversation = $this->message->conversation;
        $contact = $conversation->contact;

        return [
            'message_id' => $this->message->id,
            'conversation_id' => $conversation->id,
            'contact_name' => $contact->name,
            'contact_phone' => $contact->phone_number,
            'type' => 'new_message',
            'message' => 'New message from ' . $contact->name,
            'preview' => substr($this->message->content, 0, 100),
        ];
    }
}
