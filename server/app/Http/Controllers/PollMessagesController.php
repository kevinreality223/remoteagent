<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageReceipt;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class PollMessagesController extends Controller
{
    public function __invoke(Request $request)
    {
        $client = $request->attributes->get('client');
        $cursor = $request->query('cursor');

        if (!$cursor) {
            $receipt = MessageReceipt::find($client->id);
            if ($receipt && $receipt->last_acked_message_id) {
                $cursor = $receipt->last_acked_message_id;
            }
        }

        try {
            $query = Message::query()
                ->where('to_client_id', $client->id)
                ->orderBy('id')
                ->limit(50);

            if ($cursor) {
                $query->where('id', '>', $cursor);
            }

            $messages = $query->get(['id', 'type', 'ciphertext', 'nonce', 'tag', 'created_at']);
        } catch (QueryException $e) {
            if ($response = $this->databaseUnavailableResponse($e)) {
                return $response;
            }

            throw $e;
        }

        if ($messages->isEmpty()) {
            return response()->noContent();
        }

        $payload = $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'type' => $message->type,
                'ciphertext' => $message->ciphertext,
                'nonce' => $message->nonce,
                'tag' => $message->tag,
                'created_at' => $message->created_at?->toIso8601String(),
            ];
        });

        return response()->json(['messages' => $payload]);
    }
}
