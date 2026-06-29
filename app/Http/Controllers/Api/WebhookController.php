<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();
        Log::info('webhook', $data);

        $event = $data['event'] ?? null;

        // Only process message events (e.g., message, message.any, message.create)
        if ($event !== 'message' && !str_starts_with($event, 'message.')) {
            return response()->json(['status' => 'ignored', 'event' => $event]);
        }

        $sessionId = $data['session'] ?? null;
        $payload = $data['payload'] ?? [];
        $me = $data['me'] ?? [];

        if (!$sessionId || empty($payload)) {
            return response()->json(['status' => 'error', 'message' => 'Missing session or payload']);
        }

        // Find or create account by session
        $account = Account::firstOrCreate(
            ['waha_session_id' => $sessionId],
            [
                'name' => $sessionId,
                'phone_number' => $me['id'] ?? null,
                'status' => 'active',
            ]
        );

        // Auto-detect and update base URL from media attachment if available
        if (isset($payload['media']['url'])) {
            $parsedUrl = parse_url($payload['media']['url']);
            if (isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
                $portStr = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
                $detectedUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $portStr;

                if (empty($account->base_url) || str_contains($account->base_url, 'localhost') || $account->base_url === 'http://127.0.0.1:3000') {
                    $account->update(['base_url' => $detectedUrl]);
                }
            }
        }

        // Determine chat participant
        // WAHA always uses 'from' as the chat/conversation partner ID regardless of direction
        $isFromMe = $payload['fromMe'] ?? false;
        $chatId = $payload['from'] ?? null;

        if (!$chatId) {
            return response()->json(['status' => 'error', 'message' => 'Cannot determine chat ID']);
        }

        // Determine the participant name (the other person in the chat)
        // NOWEB engine uses 'pushName', WEBJS uses 'notifyName'
        $pushName = $payload['_data']['pushName'] ?? $payload['_data']['notifyName'] ?? null;

        if (!$isFromMe) {
            // Incoming message: the sender is the other person
            $participantName = $pushName ?? $chatId;
        } else {
            // Outgoing message: the recipient is the other person.
            // Check if we already have a chat to preserve its name, or default to the chat ID
            $existingChat = Chat::where('account_id', $account->id)
                ->where('chat_id', $chatId)
                ->first();
            $participantName = $existingChat ? $existingChat->user_name : $chatId;
        }

        // Find or create chat
        $chat = Chat::firstOrCreate(
            [
                'account_id' => $account->id,
                'chat_id' => $chatId,
            ],
            [
                'user_name' => $participantName,
            ]
        );

        // Update user_name if we got an incoming message with a proper contact name
        if (!$isFromMe && $pushName && $chat->user_name !== $pushName) {
            $chat->update(['user_name' => $pushName]);
        }

        // Determine cleaner body content for database and chat list preview
        $msgType = $payload['type'] ?? $payload['_data']['type'] ?? null;
        if (!$msgType) {
            if (!empty($payload['location'])) {
                $msgType = 'location';
            } elseif ($payload['hasMedia'] ?? false) {
                $msgType = 'image';
            } else {
                $msgType = 'chat';
            }
        }
        $messageBody = '';

        if ($msgType === 'chat') {
            $messageBody = $payload['body'] ?? '';
        } elseif ($msgType === 'location') {
            $messageBody = '📍 Shared Location';
        } else {
            // Handl media: image, video, sticker, document, audio
            $messageBody = $payload['caption'] ?? $payload['body'] ?? '';
            // If body starts with base64 signatures or is extremely long, clear it to avoid db pollution
            if (str_starts_with($messageBody, '/9j/') || strlen($messageBody) > 1000) {
                $messageBody = '';
            }
            if (empty($messageBody)) {
                $messageBody = '[' . ucfirst($msgType) . ']';
            }
        }

        // Update last message preview
        $chat->update(['last_message' => $messageBody]);

        // Store message (use firstOrCreate to prevent duplicates from optimistic UI inserts)
        $messageId = $payload['id'] ?? uniqid();
        $message = Message::firstOrCreate(
            ['message_id' => $messageId],
            [
                'chat_id' => $chat->id,
                'type' => $isFromMe ? 'out' : 'in',
                'body' => $messageBody,
                'raw_data' => json_encode($data),
            ]
        );

        // If message already existed (from optimistic insert), update raw_data with full webhook payload
        if (!$message->wasRecentlyCreated) {
            $message->update(['raw_data' => json_encode($data)]);
        }

        return response()->json([
            'status' => 'success',
            'account_id' => $account->id,
            'chat_id' => $chat->id,
            'message_id' => $message->id,
        ]);
    }
}
