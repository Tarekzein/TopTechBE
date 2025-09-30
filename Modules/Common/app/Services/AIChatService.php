<?php

namespace Modules\Common\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Store\Models\Product;
use Modules\Common\Models\AIChatLog;

class AIChatService
{
    public function ask(
        string $message,
        ?string $sessionToken = null,
        ?Request $request = null,
        $customProducts = null // ✅ new param
    ) {
        // 1️⃣ Resolve user
        $userId = Auth::id();
        if (!$userId && $request && $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($request->bearerToken());
            if ($accessToken) {
                $userId = $accessToken->tokenable_id;
            }
        }

        // 2️⃣ Build session identifier
        $session = $userId ? "user_" . $userId : ($sessionToken ?? $this->generateGuestToken());

        // 3️⃣ Load recent chat history
        $history = AIChatLog::where(function ($q) use ($userId, $session) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->where('session_token', $session);
                }
            })
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get(['user_message','ai_response']);

        $chatHistory = [];
        foreach ($history as $h) {
            $chatHistory[] = ["role" => "user", "content" => $h->user_message];
            $chatHistory[] = ["role" => "assistant", "content" => $h->ai_response];
        }

        // 4️⃣ Load product data
        if ($customProducts) {
            $products = $customProducts;
        } else {
            $products = Product::select('id','name','description','price')
                ->whereNotNull('description')
                ->limit(50)
                ->get();
        }

        $context = $products->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => $p->price,
            'description' => $p->description,
        ])->toJson(JSON_PRETTY_PRINT);

        // 5️⃣ Build messages
        $messages = [
            [
                "role" => "system",
                "content" => "You are a helpful product assistant for an ecommerce store. 
Only answer based on the product database provided.
If a product is not in the database, politely say you don’t know."
            ],
            ["role" => "system", "content" => "Product Database:\n" . $context],
            ...$chatHistory,
            ["role" => "user", "content" => $message],
        ];

        // 6️⃣ Call OpenRouter
        $response = Http::withToken(config('services.openrouter.api_key'))
            ->post(config('services.openrouter.base_url') . '/chat/completions', [
                "model" => "openai/gpt-4o-mini",
                "messages" => $messages,
            ])
            ->json();

        $aiReply = $response['choices'][0]['message']['content'] ?? 'No response';

        // 7️⃣ Save log
        AIChatLog::create([
            'user_id'       => $userId,
            'session_token' => $userId ? null : $session,
            'user_message'  => $message,
            'ai_response'   => $aiReply,
        ]);

        // 8️⃣ Return response
        return [
            "reply" => $aiReply,
            "session_token" => $userId ? null : $session,
            "raw" => $response,
        ];
    }

    public function getHistory(?Request $request = null)
    {
        $userId = Auth::id();

        if (!$userId && $request && $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($request->bearerToken());
            if ($accessToken) {
                $userId = $accessToken->tokenable_id;
            }
        }

        $sessionToken = $request?->input('session_token');

        if (!$userId && !$sessionToken) {
            return collect();
        }

        return AIChatLog::where(function ($q) use ($userId, $sessionToken) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->where('session_token', $sessionToken);
                }
            })
            ->orderBy('created_at', 'asc')
            ->get(['id','user_message','ai_response','created_at']);
    }

    private function generateGuestToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
