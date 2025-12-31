<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Database\QueryException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class ClientRegistrationController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'fingerprint' => ['required', 'string', 'max:255'],
        ]);

        try {
            $existing = Client::where('fingerprint', $validated['fingerprint'])->first();
        } catch (QueryException $e) {
            if ($response = $this->databaseUnavailableResponse($e)) {
                return $response;
            }

            throw $e;
        }
        if ($existing) {
            return $this->respondWithCredentials($existing, $validated['name'] ?? null);
        }

        $personalToken = base64_encode(random_bytes(32));
        $apiToken = base64_encode(random_bytes(32));

        try {
            $client = Client::create([
                'name' => $validated['name'] ?? null,
                'fingerprint' => $validated['fingerprint'],
                'api_token_hash' => Hash::make($apiToken),
                'api_token_encrypted' => Crypt::encryptString($apiToken),
                'encryption_key_encrypted' => Crypt::encryptString($personalToken),
            ]);
        } catch (QueryException $e) {
            if ($response = $this->databaseUnavailableResponse($e)) {
                return $response;
            }

            // Handles race conditions where the same fingerprint is registered concurrently.
            if ($this->isFingerprintUniqueViolation($e)) {
                $existing = Client::where('fingerprint', $validated['fingerprint'])->first();

                if ($existing) {
                    return $this->respondWithCredentials($existing, $validated['name'] ?? null);
                }
            }

            throw $e;
        }

        return response()->json([
            'client_id' => $client->id,
            'personal_token' => $personalToken,
            'api_token' => $apiToken,
        ], 201);
    }

    private function respondWithCredentials(Client $client, ?string $name)
    {
        $canReuse = !empty($client->api_token_encrypted) && !empty($client->encryption_key_encrypted);

        if ($canReuse) {
            try {
                if ($name && $name !== $client->name) {
                    $client->forceFill(['name' => $name])->save();
                }

                return response()->json([
                    'client_id' => $client->id,
                    'personal_token' => Crypt::decryptString($client->encryption_key_encrypted),
                    'api_token' => Crypt::decryptString($client->api_token_encrypted),
                ]);
            } catch (DecryptException $e) {
                // If existing secrets cannot be decrypted (old plaintext values, corrupt data, etc.)
                // fall through and re-issue fresh credentials.
            }
        }

        $personalToken = base64_encode(random_bytes(32));
        $apiToken = base64_encode(random_bytes(32));

        $client->update([
            'name' => $name ?? $client->name,
            'api_token_hash' => Hash::make($apiToken),
            'api_token_encrypted' => Crypt::encryptString($apiToken),
            'encryption_key_encrypted' => Crypt::encryptString($personalToken),
        ]);

        return response()->json([
            'client_id' => $client->id,
            'personal_token' => $personalToken,
            'api_token' => $apiToken,
        ]);
    }

    private function isFingerprintUniqueViolation(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'clients.fingerprint') ||
            str_contains($message, 'for key \"clients_fingerprint');
    }
}
