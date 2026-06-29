<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function index($accountId)
    {
        $chats = Chat::where('account_id', $accountId)
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($chats);
    }

    public function store(Request $request, $accountId)
    {
        $validated = $request->validate([
            'chat_id' => 'required|string',
            'message' => 'required|string',
        ]);

        $account = Account::findOrFail($accountId);

        $chatId = $validated['chat_id'];
        if (!str_contains($chatId, '@')) {
            $cleanNumber = preg_replace('/[^0-9]/', '', $chatId);
            if (str_starts_with($cleanNumber, '0')) {
                $cleanNumber = '62' . substr($cleanNumber, 1);
            }
            $chatId = $cleanNumber . '@c.us';
        }

        $session = $account->waha_session_id;
        if (empty($session)) {
            return response()->json([
                'status' => 'error',
                'description' => 'WAHA WhatsApp session ID is not configured for this account. Please update the account config.',
            ], 400);
        }

        $baseUrl = $account->base_url;
        if (empty($baseUrl)) {
            return response()->json([
                'status' => 'error',
                'description' => 'WAHA Base URL is not configured for this account. Please update the account config first.',
            ], 400);
        }

        $apiKey = $account->api_key;
        if (empty($apiKey)) {
            return response()->json([
                'status' => 'error',
                'description' => 'WAHA API Key is not configured for this account. Please update the account config first.',
            ], 400);
        }

        $messageText = $validated['message'];

        try {
            $headers = [];
            if ($apiKey) {
                $headers['X-Api-Key'] = $apiKey;
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }

            Log::info('Sending initial message to WAHA from ChatController@store (webhook destination)', [
                'url' => rtrim($baseUrl, '/') . '/api/sendText',
                'chatId' => $chatId,
                'session' => $session
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post(rtrim($baseUrl, '/') . '/api/sendText', [
                    'chatId' => $chatId,
                    'text' => $messageText,
                    'session' => $session,
                ]);

            if ($response->failed()) {
                Log::error('WAHA sendText failed during createChat', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send message: ' . ($response->json('message') ?? $response->body()),
                ], 400);
            }

            $wahaData = $response->json();
            $wahaMessageId = $wahaData['id'] ?? ('temp_' . uniqid() . '_' . time());

            // Create or retrieve Chat record
            $chat = Chat::firstOrCreate([
                'account_id' => $account->id,
                'chat_id' => $chatId,
            ], [
                'user_name' => explode('@', $chatId)[0],
                'last_message' => $messageText,
            ]);

            $chat->update([
                'last_message' => $messageText,
                'updated_at' => now(),
            ]);

            // Save the outgoing message record using the actual WAHA message ID to prevent receipt duplicates
            Message::firstOrCreate([
                'message_id' => $wahaMessageId,
            ], [
                'chat_id' => $chat->id,
                'body' => $messageText,
                'type' => 'out',
                'raw_data' => $wahaData ? json_encode($wahaData) : null,
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'chat' => $chat,
                'chat_id' => $chatId,
            ]);
        } catch (\Exception $e) {
            Log::error('WAHA ChatController@store sendMessage Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send message (Internal error): ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $chat = Chat::findOrFail($id);
        $chat->delete();
        return response()->json(['status' => 'success']);
    }
}
