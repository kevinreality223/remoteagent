<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateClient
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $token = substr($header, 7);
        $clientId = $request->input('client_id') ?: $request->header('X-Client-Id');

        if (! $clientId) {
            return response()->json(['message' => 'Missing client_id'], Response::HTTP_UNAUTHORIZED);
        }

        $client = Client::find($clientId);

        if (! $client || ! Hash::check($token, $client->api_token_hash)) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('auth_client', $client);

        return $next($request);
    }
}
