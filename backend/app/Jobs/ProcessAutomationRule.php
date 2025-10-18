<?php

namespace App\Jobs;

use App\Models\AutomationRule;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessAutomationRule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $message;
    public $workspaceId;

    /**
     * Create a new job instance.
     */
    public function __construct(Message $message, int $workspaceId)
    {
        $this->message = $message;
        $this->workspaceId = $workspaceId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get active automation rules for the workspace
            $rules = AutomationRule::where('workspace_id', $this->workspaceId)
                ->where('is_active', true)
                ->get();

            foreach ($rules as $rule) {
                if ($this->shouldTriggerRule($rule)) {
                    $this->executeRule($rule);
                    $rule->increment('execution_count');
                }
            }

        } catch (\Exception $e) {
            Log::error("Automation rule processing error: {$e->getMessage()}");
        }
    }

    /**
     * Check if rule should be triggered
     */
    private function shouldTriggerRule(AutomationRule $rule): bool
    {
        switch ($rule->trigger_type) {
            case 'keyword':
                $keywords = $rule->trigger_config['keywords'] ?? [];
                foreach ($keywords as $keyword) {
                    if (stripos($this->message->body, $keyword) !== false) {
                        return true;
                    }
                }
                return false;

            case 'event':
                // Check if message matches event criteria
                return true;

            default:
                return false;
        }
    }

    /**
     * Execute automation rule action
     */
    private function executeRule(AutomationRule $rule): void
    {
        try {
            switch ($rule->action_type) {
                case 'send_message':
                    $this->sendAutoReply($rule);
                    break;

                case 'assign_conversation':
                    $this->assignConversation($rule);
                    break;

                case 'add_tag':
                    $this->addTag($rule);
                    break;

                case 'trigger_chatbot':
                    $this->triggerChatbot($rule);
                    break;
            }

        } catch (\Exception $e) {
            Log::error("Rule execution error: {$e->getMessage()}");
        }
    }

    /**
     * Send auto reply
     */
    private function sendAutoReply(AutomationRule $rule): void
    {
        $replyMessage = $rule->action_config['message'] ?? '';
        
        if (!$replyMessage) {
            return;
        }

        $whatsappServiceUrl = env('WHATSAPP_SERVICE_URL');
        
        Http::post("{$whatsappServiceUrl}/api/messages/send", [
            'sessionId' => $this->message->whatsappSession->session_id,
            'to' => $this->message->from_number,
            'message' => $replyMessage,
        ]);

        Log::info("Auto-reply sent for rule {$rule->id}");
    }

    /**
     * Assign conversation to user
     */
    private function assignConversation(AutomationRule $rule): void
    {
        $userId = $rule->action_config['user_id'] ?? null;
        
        if (!$userId || !$this->message->conversation_id) {
            return;
        }

        Conversation::where('id', $this->message->conversation_id)
            ->update(['assigned_to' => $userId]);

        Log::info("Conversation assigned for rule {$rule->id}");
    }

    /**
     * Add tag to contact
     */
    private function addTag(AutomationRule $rule): void
    {
        $tag = $rule->action_config['tag'] ?? null;
        
        if (!$tag || !$this->message->conversation) {
            return;
        }

        $contact = $this->message->conversation->contact;
        $tags = $contact->tags ?? [];
        
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $contact->update(['tags' => $tags]);
        }

        Log::info("Tag added for rule {$rule->id}");
    }

    /**
     * Trigger chatbot
     */
    private function triggerChatbot(AutomationRule $rule): void
    {
        // TODO: Implement chatbot triggering
        Log::info("Chatbot triggered for rule {$rule->id}");
    }
}
