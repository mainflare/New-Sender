<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaign;
    public $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Processing campaign: {$this->campaign->id}");

            $campaign = $this->campaign->fresh();

            if ($campaign->status !== 'running') {
                Log::warning("Campaign {$campaign->id} is not running, skipping");
                return;
            }

            // Get target contacts
            $contacts = Contact::where('workspace_id', $campaign->workspace_id)
                ->whereIn('id', $campaign->target_audiences)
                ->where('is_valid', true)
                ->where('is_blocked', false)
                ->get();

            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL');
            $sessionId = $campaign->whatsappSession->session_id;
            $delay = $campaign->delay_between_messages * 1000; // Convert to milliseconds

            foreach ($contacts as $contact) {
                // Check if campaign is still running
                $campaign = $campaign->fresh();
                if ($campaign->status !== 'running') {
                    Log::info("Campaign {$campaign->id} stopped, breaking loop");
                    break;
                }

                try {
                    // Replace variables in message
                    $message = $this->replaceVariables($campaign->message_content, $contact);

                    // Send message via WhatsApp Service
                    $response = Http::post("{$whatsappServiceUrl}/api/messages/send", [
                        'sessionId' => $sessionId,
                        'to' => $contact->phone_number,
                        'message' => $message,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json('data');

                        // Save message to database
                        Message::create([
                            'conversation_id' => null, // Will be linked later
                            'whatsapp_session_id' => $campaign->whatsapp_session_id,
                            'message_id' => $data['id'] ?? uniqid(),
                            'type' => 'text',
                            'body' => $message,
                            'direction' => 'outbound',
                            'status' => 'sent',
                            'from_number' => $campaign->whatsappSession->phone_number,
                            'to_number' => $contact->phone_number,
                            'from_me' => true,
                            'campaign_id' => $campaign->id,
                            'sent_at' => now(),
                        ]);

                        $campaign->increment('sent_count');
                        Log::info("Message sent to {$contact->phone_number}");
                    } else {
                        $campaign->increment('failed_count');
                        Log::error("Failed to send message to {$contact->phone_number}");
                    }

                    // Delay between messages
                    usleep($delay * 1000);

                } catch (\Exception $e) {
                    Log::error("Error sending to {$contact->phone_number}: {$e->getMessage()}");
                    $campaign->increment('failed_count');
                }
            }

            // Mark campaign as completed
            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::info("Campaign {$campaign->id} completed");

        } catch (\Exception $e) {
            Log::error("Campaign processing error: {$e->getMessage()}");
            
            $this->campaign->update([
                'status' => 'failed',
            ]);

            throw $e;
        }
    }

    /**
     * Replace variables in message
     */
    private function replaceVariables(string $message, Contact $contact): string
    {
        $replacements = [
            '{name}' => $contact->name ?? 'there',
            '{phone}' => $contact->phone_number,
            '{email}' => $contact->email ?? '',
            '{company}' => $contact->company ?? '',
            '{city}' => $contact->city ?? '',
            '{country}' => $contact->country ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Campaign job failed: {$exception->getMessage()}");
        
        $this->campaign->update([
            'status' => 'failed',
        ]);
    }
}
