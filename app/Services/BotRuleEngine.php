<?php

namespace App\Services;

use App\Models\BotRule;
use App\Models\Message;
use App\Models\WhatsAppConversation;

class BotRuleEngine
{
    public function evaluate(Message $message, WhatsAppConversation $conversation): ?array
    {
        $rules = BotRule::where('workspace_id', $conversation->workspace_id)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            if (!$rule->matches($message->body ?? '', $conversation)) {
                continue;
            }

            $action = [
                'rule'         => $rule,
                'action_type'  => $rule->action_type,
                'action_value' => $rule->action_value ?? [],
            ];

            if ($rule->stop_on_match) {
                return $action;
            }

            $this->executeAction($action, $message, $conversation);
        }

        return null;
    }

    public function executeAction(array $action, Message $message, WhatsAppConversation $conversation): void
    {
        $waService = WhatsAppService::for($conversation->whatsappAccount);

        match ($action['action_type']) {
            'reply' => $this->handleReply($action, $conversation, $waService),
            'send_template' => $this->handleTemplate($action, $conversation, $waService),
            'assign_agent'  => $this->handleAssignAgent($action, $conversation),
            'assign_bot'    => $this->handleAssignBot($action, $conversation),
            'close'         => $conversation->update(['status' => 'resolved']),
            'add_label'     => $this->handleAddLabel($action, $conversation),
            'escalate_ai'   => dispatch(new \App\Jobs\GenerateReplySuggestions($conversation, $message)),
            default         => null,
        };
    }

    private function handleReply(array $action, WhatsAppConversation $conversation, WhatsAppService $wa): void
    {
        $text = $action['action_value']['text'] ?? '';
        if (!$text) {
            return;
        }

        $waMessageId = $wa->sendTextMessage($conversation->contact->phone, $text);

        \App\Models\Message::create([
            'room_id'         => $conversation->room_id,
            'user_id'         => null,
            'body'            => $text,
            'origin'          => 'internal',
            'wa_message_id'   => $waMessageId,
            'delivery_status' => 'sent',
        ]);

        $conversation->update(['first_reply_at' => $conversation->first_reply_at ?? now()]);
    }

    private function handleTemplate(array $action, WhatsAppConversation $conversation, WhatsAppService $wa): void
    {
        $templateName = $action['action_value']['template_name'] ?? '';
        $language     = $action['action_value']['language'] ?? 'en_US';
        $components   = $action['action_value']['components'] ?? [];

        if (!$templateName) {
            return;
        }

        $wa->sendTemplate($conversation->contact->phone, $templateName, $language, $components);
    }

    private function handleAssignAgent(array $action, WhatsAppConversation $conversation): void
    {
        $agentId = $action['action_value']['agent_id'] ?? null;
        $conversation->update(['assigned_agent_id' => $agentId]);
    }

    private function handleAssignBot(array $action, WhatsAppConversation $conversation): void
    {
        $botId = $action['action_value']['bot_id'] ?? null;
        $conversation->update(['assigned_bot_id' => $botId]);
    }

    private function handleAddLabel(array $action, WhatsAppConversation $conversation): void
    {
        // Labels stored in contact metadata for now
        $label    = $action['action_value']['label'] ?? '';
        $room     = $conversation->room;
        $metadata = $room->contact_metadata ?? [];
        $labels   = $metadata['labels'] ?? [];

        if ($label && !in_array($label, $labels)) {
            $labels[] = $label;
            $metadata['labels'] = $labels;
            $room->update(['contact_metadata' => $metadata]);
        }
    }
}
