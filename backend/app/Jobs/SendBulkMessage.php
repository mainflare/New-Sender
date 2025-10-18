<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendBulkMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $sessionId;
    public $messages;
    public $delay;

    /**
     * Create a new job instance.
     */
    public function __construct(string $sessionId, array $messages, int $delay = 5000)
    {
        $this->sessionId = $sessionId;
        $this->messages = $messages;
        $this->delay = $delay;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL');

            foreach ($this->messages as $message) {
                try {
                    $response = Http::post("{$whatsappServiceUrl}/api/messages/send", [
                        'sessionId' => $this->sessionId,
                        'to' => $message['to'],
                        'message' => $message['message'],
                    ]);

                    if ($response->successful()) {
                        Log::info("Bulk message sent to {$message['to']}");
                    } else {
                        Log::error("Failed to send bulk message to {$message['to']}");
                    }

                    // Delay between messages
                    usleep($this->delay * 1000);

                } catch (\Exception $e) {
                    Log::error("Error in bulk send to {$message['to']}: {$e->getMessage()}");
                }
            }

            Log::info("Bulk message job completed");

        } catch (\Exception $e) {
            Log::error("Bulk message job error: {$e->getMessage()}");
            throw $e;
        }
    }
}
