<?php

namespace App\Http\Controllers;

use App\Models\MessageReceipt;
use Illuminate\Http\Request;

class AckMessagesController extends Controller
{
    public function __invoke(Request $request)
    {
        $client = $request->attributes->get('client');
        $validated = $request->validate([
            'last_received_id' => ['required', 'integer', 'min:1'],
        ]);

        MessageReceipt::updateOrCreate(
            ['client_id' => $client->id],
            ['last_acked_message_id' => $validated['last_received_id']]
        );

        return response()->json(['status' => 'acked']);
    }
}
