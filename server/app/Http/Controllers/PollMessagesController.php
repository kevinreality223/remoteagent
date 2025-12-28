<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class PollMessagesController extends Controller
{
    public function __invoke(Request $request)
    {
        $client = $request->attributes->get('client');
        $cursor = $request->query('cursor');

        $query = Message::query()
            ->where('to_client_id', $client->id)
            ->orderBy('id')
            ->limit(50);

        if ($cursor) {
            $query->where('id', '>', $cursor);
        }

        $messages = $query->get(['id', 'type', 'ciphertext', 'nonce', 'tag', 'created_at']);

        if ($messages->isEmpty()) {
            return response()->noContent();
        }

        return response()->json(['messages' => $messages]);
    }
}
