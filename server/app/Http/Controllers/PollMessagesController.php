<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageReceipt;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

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

        $now = Date::now();
        $receipt = MessageReceipt::firstOrNew(['client_id' => $client->id]);
        $previousInterval = $receipt->poll_interval_seconds ?: 3;

        $receipt->last_polled_at = $now;
        if ($messages->isEmpty()) {
            $interval = min($previousInterval + 3, 30);
            $receipt->poll_interval_seconds = $interval;
            $receipt->next_poll_at = $now->clone()->addSeconds($interval);
            $receipt->save();

            return response()->noContent();
        }

        $receipt->poll_interval_seconds = 3;
        $receipt->next_poll_at = $now->clone()->addSeconds(3);
        $receipt->save();

        $payload = $messages->map(function ($message) {
            $createdAt = $message->created_at;

            if ($createdAt instanceof Carbon) {
                $createdAt = $createdAt->toIso8601String();
            } elseif (!is_null($createdAt)) {
                $createdAt = Carbon::parse($createdAt)->toIso8601String();
            }

            return [
                'id' => $message->id,
                'type' => $message->type,
                'ciphertext' => $message->ciphertext,
                'nonce' => $message->nonce,
                'tag' => $message->tag,
                'created_at' => $createdAt,
            ];
        });

        return response()->json(['messages' => $payload]);
    }
}
