<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class PollController extends Controller
{
    public function poll(Request $request)
    {
        $client = $request->attributes->get('auth_client');
        $cursor = $request->query('cursor');

        $messages = Message::where('to_client_id', $client->id)
            ->when($cursor, fn($q) => $q->where('id', '>', $cursor))
            ->orderBy('id')
            ->limit(50)
            ->get(['id', 'type', 'ciphertext', 'nonce', 'tag', 'created_at']);

        if ($messages->isEmpty()) {
            return response()->noContent();
        }

        return response()->json(['messages' => $messages]);
    }
}
