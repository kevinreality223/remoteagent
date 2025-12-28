<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Support\Facades\Date;

class OperatorClientsController extends Controller
{
    public function index()
    {
        $now = Date::now();
        $clients = Client::query()
            ->orderBy('created_at')
            ->get()
            ->map(function (Client $client) use ($now) {
                $lastSeen = $client->last_seen_at;
                $isOnline = $lastSeen !== null && $lastSeen->gte($now->clone()->subMinutes(2));

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'created_at' => $client->created_at,
                    'last_seen_at' => $lastSeen,
                    'status' => $isOnline ? 'online' : 'offline',
                ];
            });

        return response()->json(['clients' => $clients]);
    }
}
