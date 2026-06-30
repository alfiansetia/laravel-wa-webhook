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
        $rawFrom  = $payload['from'] ?? null;

        if (!$rawFrom) {
            return response()->json(['status' => 'error', 'message' => 'Cannot determine chat ID']);
        }

        // ── Status / Story Broadcast ─────────────────────────────────────────────
        // WAHA kirim event ini dengan from = "status@broadcast".
        // 'participant' di _data.key berisi JID pengirim status sebenarnya.
        // Kita simpan per-kontak dengan chat_id = "status_{participant}" agar setiap
        // kontak punya "Status Chat" tersendiri dan bisa dilihat secara terpisah.
        $isStatusBroadcast = ($rawFrom === 'status@broadcast');

        if ($isStatusBroadcast) {
            $participant = $payload['_data']['key']['participant'] ?? null;

            // Kalau tidak ada participant, abaikan saja
            if (!$participant) {
                return response()->json(['status' => 'ignored', 'reason' => 'status broadcast without participant']);
            }

            $chatId      = 'status_' . $participant;   // e.g. "status_6281234567890@s.whatsapp.net"
            $chatIdAlt   = null;
            $pushName    = $payload['_data']['pushName'] ?? $payload['_data']['notifyName'] ?? null;
            $statusLabel = $pushName ? $pushName . ' — Status' : $participant . ' — Status';

            $chat = Chat::firstOrCreate(
                [
                    'account_id' => $account->id,
                    'chat_id'    => $chatId,
                ],
                [
                    'user_name'   => $statusLabel,
                    'chat_id_alt' => $participant,   // simpan JID asli di alt untuk keperluan referensi
                ]
            );

            // Update nama jika kontak sebelumnya belum ada pushName
            if ($pushName && !str_ends_with($chat->user_name, '— Status')) {
                $chat->update(['user_name' => $pushName . ' — Status']);
            }
        } else {
            // ── Pesan biasa (DM / Group) ─────────────────────────────────────────────
            $chatId = $rawFrom;

            // Extract alternative JID (@s.whatsapp.net) from _data.key.remoteJidAlt
            // This is needed because WAHA NOWEB cannot send to @lid addresses directly
            $remoteJid = $payload['_data']['key']['remoteJid']    ?? null;
            $chatIdAlt = $payload['_data']['key']['remoteJidAlt'] ?? null;

            // Normalisasi: ekstrak nomor murni dari JID (strip suffix @c.us / @s.whatsapp.net / @lid)
            // Digunakan sebagai fallback akhir untuk mendeteksi kontak yang sama
            $extractPhone = fn($jid) => $jid ? preg_replace('/@.*$/', '', $jid) : null;
            $phoneFromChatId   = $extractPhone($chatId);
            $phoneFromRemoteJid = $extractPhone($remoteJid);
            $phoneFromAlt      = $extractPhone($chatIdAlt);

            // ── Anti-duplicate lookup (multi-layer) ──────────────────────────────────
            // Layer 1: cocokkan JID langsung (chat_id / chat_id_alt)
            // Layer 2: cocokkan via remoteJid dari _data.key
            // Layer 3: cocokkan via nomor murni (handles @c.us vs @lid vs @s.whatsapp.net)
            // ── Anti-duplicate lookup (multi-layer) & Merge ───────────────────────────
            // Kumpulkan semua chat yang berpotensi merujuk ke kontak yang sama
            $matchingChats = Chat::where('account_id', $account->id)
                ->where(function ($q) use ($chatId, $chatIdAlt, $remoteJid, $phoneFromChatId, $phoneFromRemoteJid, $phoneFromAlt) {
                    // Layer 1 — exact JID match
                    $q->where('chat_id', $chatId);
                    if ($chatIdAlt) {
                        $q->orWhere('chat_id', $chatIdAlt)
                            ->orWhere('chat_id_alt', $chatId)
                            ->orWhere('chat_id_alt', $chatIdAlt);
                    }
                    // Layer 2 — remoteJid dari _data.key
                    if ($remoteJid && $remoteJid !== $chatId) {
                        $q->orWhere('chat_id', $remoteJid)
                            ->orWhere('chat_id_alt', $remoteJid);
                    }
                    // Layer 3 — nomor murni (fallback untuk chat lama tanpa chat_id_alt)
                    // Hanya untuk nomor personal (@c.us / @s.whatsapp.net / @lid), bukan group (@g.us)
                    if ($phoneFromChatId && !str_contains($chatId, '@g.us')) {
                        $q->orWhere('chat_id', 'like', $phoneFromChatId . '@%')
                            ->orWhere('chat_id_alt', 'like', $phoneFromChatId . '@%');
                    }
                    if ($phoneFromRemoteJid && $phoneFromRemoteJid !== $phoneFromChatId) {
                        $q->orWhere('chat_id', 'like', $phoneFromRemoteJid . '@%')
                            ->orWhere('chat_id_alt', 'like', $phoneFromRemoteJid . '@%');
                    }
                })
                ->get();

            if ($matchingChats->count() > 1) {
                // Tentukan primary chat: Utamakan yang formatnya @lid (NOWEB native)
                $existingChat = $matchingChats->first(fn($c) => str_ends_with($c->chat_id, '@lid'))
                    ?? $matchingChats->sortBy('created_at')->first();

                // Lakukan merger data dari chat duplikat lainnya
                foreach ($matchingChats as $duplicate) {
                    if ($duplicate->id === $existingChat->id) {
                        continue;
                    }

                    // Pindahkan pesan dari chat duplikat ke primary chat
                    $duplicateMessages = Message::where('chat_id', $duplicate->id)->get();
                    foreach ($duplicateMessages as $msg) {
                        // Cek apakah pesan dengan message_id ini sudah ada di primary chat
                        $exists = Message::where('chat_id', $existingChat->id)
                            ->where('message_id', $msg->message_id)
                            ->exists();

                        if ($exists) {
                            $msg->delete(); // Hapus pesan yang duplikat
                        } else {
                            $msg->update(['chat_id' => $existingChat->id]);
                        }
                    }

                    // Gabungkan chat_id_alt jika primary belum punya tapi duplikat punya
                    if (empty($existingChat->chat_id_alt) && !empty($duplicate->chat_id_alt)) {
                        $existingChat->update(['chat_id_alt' => $duplicate->chat_id_alt]);
                    }

                    // Hapus chat duplikat
                    $duplicate->delete();
                    Log::info('Merged duplicate chat into primary', [
                        'primary_id' => $existingChat->id,
                        'primary_chat_id' => $existingChat->chat_id,
                        'deleted_duplicate_id' => $duplicate->id,
                        'deleted_duplicate_chat_id' => $duplicate->chat_id,
                    ]);
                }
            } else {
                $existingChat = $matchingChats->first();
            }

            // Determine the participant name (the other person in the chat)
            // NOWEB engine uses 'pushName', WEBJS uses 'notifyName'
            $pushName = $payload['_data']['pushName'] ?? $payload['_data']['notifyName'] ?? $payload['_data']['verifiedBizName'] ?? null;

            // Detect group chat
            $isGroupChat = str_ends_with($chatId, '@g.us');

            // Beri label yang bagus jika ini adalah Newsletter/Channel dan pushName kosong
            if (str_ends_with($chatId, '@newsletter') && empty($pushName)) {
                $pushName = 'Channel/Newsletter (' . substr(explode('@', $chatId)[0], 0, 8) . '...)';
            }

            // Determine participant name for chat creation
            if ($isGroupChat) {
                // For groups: use group subject from _data if available, otherwise use chatId
                $groupSubject = $payload['_data']['subject'] ?? $payload['_data']['groupMetadata']['subject'] ?? null;
                $participantName = ($existingChat?->user_name) ?? $groupSubject ?? $chatId;
            } elseif (!$isFromMe) {
                $participantName = $pushName ?? ($existingChat?->user_name) ?? $chatId;
            } else {
                // Outgoing DM: tidak ada pushName — pakai nama dari chat existing jika ada
                $participantName = ($existingChat?->user_name) ?? $pushName ?? $chatId;
            }

            if ($existingChat) {
                // ── Gunakan chat yang sudah ada (hindari duplikat) ──
                $chat = $existingChat;

                // Update nama HANYA untuk DM (bukan group), jika incoming dan dapat nama baru
                if (!$isGroupChat && !$isFromMe && $pushName && $chat->user_name !== $pushName) {
                    $chat->update(['user_name' => $pushName]);
                }

                // Update chat_id_alt jika belum ada
                if ($chatIdAlt && empty($chat->chat_id_alt)) {
                    $chat->update(['chat_id_alt' => $chatIdAlt]);
                }
            } else {
                // ── Buat chat baru ──
                $chat = Chat::create([
                    'account_id'  => $account->id,
                    'chat_id'     => $chatId,
                    'user_name'   => $participantName,
                    'chat_id_alt' => $chatIdAlt,
                ]);
            }
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
            // Handle media: image, video, sticker, document, audio
            $messageBody = $payload['caption'] ?? $payload['body'] ?? '';
            // If body starts with base64 signatures or is extremely long, clear it to avoid db pollution
            if (str_starts_with($messageBody, '/9j/') || strlen($messageBody) > 1000) {
                $messageBody = '';
            }
            if (empty($messageBody)) {
                $messageBody = '[' . ucfirst($msgType) . ']';
            }
        }

        // For group chats: prefix last_message with sender name for chat list preview
        $isGroupChat = str_ends_with($chat->chat_id, '@g.us');
        if ($isGroupChat) {
            $senderPrefix = $isFromMe ? 'You' : ($pushName ?? 'Contact');
            $chat->update(['last_message' => $senderPrefix . ': ' . $messageBody]);
        } else {
            // Update last message preview
            $chat->update(['last_message' => $messageBody]);
        }

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
