<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OperatorTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Operator-Token');
        if (!$token) {
            throw new UnauthorizedHttpException('Token', 'Missing operator token');
        }

        $tokens = collect(explode(',', (string) config('operators.tokens')))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values();

        if ($tokens->isEmpty() || !$tokens->contains($token)) {
            throw new AccessDeniedHttpException('Invalid operator token');
        }

        return $next($request);
    }
}
