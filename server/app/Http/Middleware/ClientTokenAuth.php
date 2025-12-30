<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ClientTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $clientId = $request->header('X-Client-Id') ?? $request->input('client_id') ?? $request->route('client_id');

        if (!$token || !$clientId) {
            throw new UnauthorizedHttpException('Bearer', 'Missing credentials');
        }

        $client = Client::find($clientId);
        if (!$client || !Hash::check($token, $client->api_token_hash)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid token');
        }

        $request->attributes->set('client', $client);

        $client->forceFill(['last_seen_at' => Date::now()])->save();

        return $next($request);
    }
}
