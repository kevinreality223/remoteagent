<?php

namespace App\Http\Controllers;

use App\Jobs\MessageFanoutJob;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MessagePublishController extends Controller
{
    public function __invoke(Request $request)
    {
        $adminToken = $request->header('X-Admin-Token');
        if ($adminToken !== env('ADMIN_TOKEN')) {
            throw new AccessDeniedHttpException('Invalid admin token');
        }

        $validated = $request->validate([
            'to_client_ids' => ['required', 'array', 'min:1'],
            'to_client_ids.*' => ['uuid'],
            'type' => ['required', 'string', 'max:255'],
            'payload' => ['required', 'array'],
        ]);

        $chunks = array_chunk($validated['to_client_ids'], 50);
        foreach ($chunks as $chunk) {
            MessageFanoutJob::dispatch($chunk, null, $validated['type'], $validated['payload']);
        }

        return response()->json(['queued' => count($validated['to_client_ids'])]);
    }
}
