<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Message;
use App\Services\EncryptionService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OperatorClientMessagesController extends Controller
{
    public function index(string $clientId, Request $request, EncryptionService $encryption)
    {
        $cursor = $request->query('cursor');
        $client = Client::find($clientId);

        if (!$client) {
            throw new NotFoundHttpException('Client not found');
        }

        try {
            $query = Message::query()
                ->where('to_client_id', $client->id)
                ->orderBy('id')
                ->limit(100);

            if ($cursor) {
                $query->where('id', '>', $cursor);
            }

            $messages = $query->get();
        } catch (QueryException $e) {
            if ($response = $this->databaseUnavailableResponse($e)) {
                return $response;
            }

            throw $e;
        }

        if ($messages->isEmpty()) {
            return response()->noContent();
        }

        $key = Crypt::decryptString($client->encryption_key_encrypted);

        $payload = $messages->map(function (Message $message) use ($encryption, $key) {
            $createdAt = $message->created_at;

            if ($createdAt instanceof Carbon) {
                $createdAt = $createdAt->toIso8601String();
            } elseif (!is_null($createdAt)) {
                $createdAt = Carbon::parse($createdAt)->toIso8601String();
            }

            $aad = ['ts' => $createdAt];
            if ($message->from_client_id === $message->to_client_id) {
                $aad['from'] = $message->from_client_id;
            } else {
                $aad['to'] = $message->to_client_id;
            }

            try {
                $body = $encryption->decrypt($key, $message->ciphertext, $message->nonce, $message->tag, $aad);
            } catch (DecryptException $e) {
                $body = ['error' => 'Unable to decrypt message'];
            }

            return [
                'id' => $message->id,
                'type' => $message->type,
                'from_client_id' => $message->from_client_id,
                'to_client_id' => $message->to_client_id,
                'created_at' => $createdAt,
                'payload' => $body,
            ];
        });

        return response()->json(['messages' => $payload]);
    }
}
