<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function accounts()
    {
        $accounts = Account::withCount('chats')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($accounts);
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'waha_session_id' => 'required|string|max:255|unique:accounts,waha_session_id',
            'phone_number' => 'nullable|string|max:255',
            'base_url' => 'nullable|url|max:255',
            'api_key' => 'nullable|string',
            'status' => 'required|string|in:active,inactive',
        ]);

        $account = Account::create($validated);
        return response()->json(['status' => 'success', 'account' => $account]);
    }

    public function updateAccount(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'waha_session_id' => 'required|string|max:255|unique:accounts,waha_session_id,' . $account->id,
            'phone_number' => 'nullable|string|max:255',
            'base_url' => 'nullable|url|max:255',
            'api_key' => 'nullable|string',
            'status' => 'required|string|in:active,inactive',
        ]);

        $account->update($validated);
        return response()->json(['status' => 'success', 'account' => $account]);
    }

    public function destroyAccount($id)
    {
        $account = Account::findOrFail($id);
        $account->delete();
        return response()->json(['status' => 'success']);
    }

    public function chats($accountId)
    {
        $chats = Chat::where('account_id', $accountId)
            ->withCount('messages')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($chats);
    }

    public function destroyChat($id)
    {
        $chat = Chat::findOrFail($id);
        $chat->delete();
        return response()->json(['status' => 'success']);
    }

    public function createChat(Request $request, $accountId)
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

        $messageText = $validated['message'];

        try {
            $headers = [];
            if ($apiKey) {
                $headers['X-Api-Key'] = $apiKey;
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }

            Log::info('Sending initial message to WAHA from createChat (webhook destination)', [
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

            return response()->json([
                'status' => 'success',
                'chat_id' => $chatId,
            ]);
        } catch (\Exception $e) {
            Log::error('WAHA createChat sendMessage Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send message (Internal error): ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroyMessage($id)
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

    public function messages($chatId)
    {
        $chat = Chat::with('account')->findOrFail($chatId);

        $messages = Message::where('chat_id', $chatId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'chat' => $chat,
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, $chatId)
    {
        $request->validate([
            'text' => 'required|string',
        ]);

        $chat = Chat::with('account')->findOrFail($chatId);
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

        try {
            $headers = [];
            if ($apiKey) {
                // Set both common WAHA key headers
                $headers['X-Api-Key'] = $apiKey;
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }

            Log::info('Sending message to WAHA', [
                'url' => rtrim($baseUrl, '/') . '/api/sendText',
                'chatId' => $chat->chat_id,
                'session' => $session
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post(rtrim($baseUrl, '/') . '/api/sendText', [
                    'chatId' => $chat->chat_id,
                    'text' => $text,
                    'session' => $session,
                ]);

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

            $wahaReply = $response->json();

            // Update chat preview with the sent message
            $chat->update(['last_message' => $text]);

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

    public function proxyMedia(Request $request)
    {
        $request->validate([
            'account_id' => 'required|integer',
            'url' => 'required|url',
        ]);

        $accountId = $request->query('account_id');
        $url = $request->query('url');

        $account = Account::findOrFail($accountId);

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
}
