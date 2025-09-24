<?php

namespace Modules\Common\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Common\Services\AIChatService;
use Modules\Store\Models\Product;
class ChatController extends Controller
{
    protected $chat;

    public function __construct(AIChatService $chat)
    {
        $this->chat = $chat;
    }

    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'session_token' => 'nullable|string',
        ]);

        $answer = $this->chat->ask(
            $request->message,
            $request->session_token,
            $request
        );

        return response()->json($answer);
    }

   public function history(Request $request)
{
    $request->validate([
        'session_token' => 'nullable|string',
    ]);

    $logs = $this->chat->getHistory($request);

    return response()->json([
        'history' => $logs
    ]);
}
public function compare(Request $request)
{
    $request->validate([
        'product_ids' => 'required|string',
    ]);

    $ids = explode(',', $request->product_ids);
    $products = Product::whereIn('id', $ids)
        ->select('id','name','description','price')
        ->get();

    if ($products->isEmpty()) {
        return response()->json(['error' => 'No products found for given IDs'], 404);
    }

    $message = "Compare the following products:\n" . $products->toJson(JSON_PRETTY_PRINT);

    $response = app(\Modules\Common\Services\AIChatService::class)
        ->ask($message, null, $request, $products); // ✅ pass only selected products

    return response()->json($response);
}


}
