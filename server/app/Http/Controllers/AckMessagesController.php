<?php

namespace App\Http\Controllers;

use App\Models\MessageReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class AckMessagesController extends Controller
{
    public function __invoke(Request $request)
    {
        $client = $request->attributes->get('client');
        $validated = $request->validate([
            'last_received_id' => ['required', 'integer', 'min:1'],
        ]);

        $now = Date::now();

        MessageReceipt::updateOrCreate(
            ['client_id' => $client->id],
            [
                'last_acked_message_id' => $validated['last_received_id'],
                'poll_interval_seconds' => 3,
                'last_polled_at' => $now,
                'next_poll_at' => $now->clone()->addSeconds(3),
            ]
        );

        return response()->json(['status' => 'acked']);
    }
}
