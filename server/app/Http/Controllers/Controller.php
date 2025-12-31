<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function databaseUnavailableResponse(Throwable $exception)
    {
        if ($this->isDatabaseConnectionException($exception)) {
            return response()->json([
                'message' => 'Database connection failed. Start the configured database service or switch to the default sqlite driver (DB_CONNECTION=sqlite) and run migrations.',
            ], 503);
        }

        return null;
    }

    protected function isDatabaseConnectionException(Throwable $exception): bool
    {
        if ($exception instanceof QueryException) {
            $code = (string) $exception->getCode();

            if ($code === '2002' || str_contains($exception->getMessage(), 'SQLSTATE[HY000]')) {
                return true;
            }

            $exception = $exception->getPrevious() ?? $exception;
        }

        return $exception instanceof PDOException;
    }
}
