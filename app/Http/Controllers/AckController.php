<?php

namespace App\Http\Controllers;

use App\Models\MessageReceipt;
use Illuminate\Http\Request;

class AckController extends Controller
{
    public function ack(Request $request)
    {
        $client = $request->attributes->get('auth_client');
        $data = $request->validate([
            'last_received_id' => ['required', 'string'],
        ]);

        MessageReceipt::updateOrCreate(
            ['client_id' => $client->id],
            ['last_acked_message_id' => $data['last_received_id']]
        );

        return response()->json(['status' => 'acked']);
    }
}
