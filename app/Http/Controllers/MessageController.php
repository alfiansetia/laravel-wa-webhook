<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function index(Request $request, $chatId)
    {
        $chat = Chat::with('account')->findOrFail($chatId);

        $beforeId = $request->query('before_id');
        $afterId = $request->query('after_id');
        $limit = $request->query('limit', 40);

        if ($afterId) {
            // Polling for newly arrived messages.
            $messages = Message::where('chat_id', $chatId)
                ->where('id', '>', $afterId)
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'chat' => $chat,
                'messages' => $messages,
                'has_more' => false
            ]);
        }

        // Standard load or legacy message scrolling.
        $query = Message::where('chat_id', $chatId);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        $hasMore = false;
        if ($messages->isNotEmpty()) {
            $oldestIdInBatch = $messages->last()->id;
            $hasMore = Message::where('chat_id', $chatId)
                ->where('id', '<', $oldestIdInBatch)
                ->exists();
        }

        $messagesAsc = $messages->reverse()->values();

        return response()->json([
            'chat' => $chat,
            'messages' => $messagesAsc,
            'has_more' => $hasMore,
            'oldest_id' => $messages->isNotEmpty() ? $messages->last()->id : null
        ]);
    }

    public function store(Request $request, $chatId)
    {
        $request->validate([
            'text' => 'required|string',
            'reply_to' => 'nullable|string',
        ]);

        $chat = Chat::with('account')->findOrFail($chatId);

        // Cegah membalas pesan di room channel/newsletter
        if (str_ends_with($chat->chat_id, '@newsletter')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saluran / Newsletter bersifat satu arah. Anda tidak bisa mengirim pesan balasan ke sini.',
            ], 400);
        }

        $account = $chat->account;

        if (!$account) {
            return response()->json([
                'status' => 'error',
                'message' => 'Associated session account configuration is missing.',
            ], 400);
        }

        $session = $account->waha_session_id;
        if (empty($session)) {
            return response()->json([
                'status' => 'error',
                'message' => 'WAHA WhatsApp session ID is not configured for this account. Please update the account config.',
            ], 400);
        }

        $baseUrl = $account->base_url;
        if (empty($baseUrl)) {
            return response()->json([
                'status' => 'error',
                'message' => 'WAHA Base URL is not configured for this account. Please update the account config first.',
            ], 400);
        }

        $apiKey = $account->api_key;
        if (empty($apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'WAHA API Key is not configured for this account. Please update the account config first.',
            ], 400);
        }

        $text = $request->input('text');
        $replyTo = $request->input('reply_to');

        // WAHA NOWEB tidak bisa kirim ke @lid address.
        // Gunakan chat_id_alt (@s.whatsapp.net) sebagai fallback jika ada.
        // Jika ini adalah room Status (dimulai dengan status_), arahkan ke nomor asli pemilik status (chat_id_alt).
        $targetChatId = $chat->chat_id;
        if (str_starts_with($targetChatId, 'status_') && !empty($chat->chat_id_alt)) {
            $targetChatId = $chat->chat_id_alt;
            Log::info('Redirecting status reply to contact JID', [
                'status_chat_id' => $chat->chat_id,
                'target_contact' => $targetChatId,
            ]);
        } elseif (str_ends_with($targetChatId, '@lid') && !empty($chat->chat_id_alt)) {
            $targetChatId = $chat->chat_id_alt;
            Log::info('Using chat_id_alt for @lid contact', [
                'original' => $chat->chat_id,
                'fallback' => $targetChatId,
            ]);
        }

        try {
            $headers = [];
            if ($apiKey) {
                $headers['X-Api-Key'] = $apiKey;
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }

            Log::info('Sending message to WAHA', [
                'url' => rtrim($baseUrl, '/') . '/api/sendText',
                'chatId' => $targetChatId,
                'session' => $session,
                'reply_to' => $replyTo
            ]);

            $postData = [
                'chatId' => $targetChatId,
                'text' => $text,
                'session' => $session,
            ];

            if (!empty($replyTo)) {
                $postData['reply_to'] = $replyTo;
            }

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post(rtrim($baseUrl, '/') . '/api/sendText', $postData);

            if ($response->failed()) {
                Log::error('WAHA sendText failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send message: ' . ($response->json('message') ?? $response->body()),
                ], 400);
            }

            // Update chat preview with the sent message
            $chat->update(['last_message' => $text]);

            // Mark as seen
            $this->markAsSeen($chat);

            return response()->json([
                'status' => 'success',
                'message' => 'Message sent successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('WAHA sendMessage Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $message = Message::findOrFail($id);
        $chatId = $message->chat_id;

        $message->delete();

        // Update last message preview if needed
        $latestMsg = Message::where('chat_id', $chatId)->orderBy('created_at', 'desc')->first();
        Chat::where('id', $chatId)->update([
            'last_message' => $latestMsg ? $latestMsg->body : null
        ]);

        return response()->json(['status' => 'success']);
    }

    public function proxyMedia(Request $request)
    {
        $request->validate([
            'account_id' => 'required|integer',
            'url' => 'required|url',
        ]);

        $accountId = $request->query('account_id');
        $url = $request->query('url');

        $account = \App\Models\Account::findOrFail($accountId);

        $headers = [];
        if ($account->api_key) {
            $headers['X-Api-Key'] = $account->api_key;
            $headers['Authorization'] = 'Bearer ' . $account->api_key;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(20)
                ->get($url);

            if ($response->failed()) {
                Log::error('Media Proxy Fetch failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return response('Media Fetch Failed', 404);
            }

            $contentType = $response->header('Content-Type') ?? 'application/octet-stream';

            return response($response->body(), 200)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=86400');
        } catch (\Exception $e) {
            Log::error('Media Proxy Exception', ['error' => $e->getMessage()]);
            return response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark all unread messages as read in WAHA instance
     */
    private function markAsSeen(Chat $chat)
    {
        $account = $chat->account;
        if (!$account || empty($account->waha_session_id) || empty($account->base_url)) {
            return;
        }

        $session = $account->waha_session_id;
        $baseUrl = $account->base_url;
        $apiKey = $account->api_key;

        // Skip newsletters/channels which are read-only and don't support sendSeen.
        if (str_ends_with($chat->chat_id, '@newsletter')) {
            return;
        }

        // Handle @lid fallback / status redirects similar to sendText
        $targetChatId = $chat->chat_id;
        if (str_starts_with($targetChatId, 'status_') && !empty($chat->chat_id_alt)) {
            $targetChatId = $chat->chat_id_alt;
        } elseif (str_ends_with($targetChatId, '@lid') && !empty($chat->chat_id_alt)) {
            $targetChatId = $chat->chat_id_alt;
        }

        try {
            $headers = [];
            if ($apiKey) {
                $headers['X-Api-Key'] = $apiKey;
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }

            Http::withHeaders($headers)
                ->timeout(5)
                ->post(rtrim($baseUrl, '/') . '/api/sendSeen', [
                    'chatId' => $targetChatId,
                    'session' => $session,
                ]);

            Log::info('Sent sendSeen to WAHA', [
                'chatId' => $targetChatId,
                'session' => $session
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to mark chat as seen in WAHA', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
