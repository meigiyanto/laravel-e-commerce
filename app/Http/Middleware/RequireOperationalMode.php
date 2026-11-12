<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireOperationalMode
{
    public function handle(
        Request $request,
        Closure $next,
        string $mode
    ): Response {
        abort_unless(
            config('operational.mode', 'single_store') === $mode,
            404
        );

        return $next($request);
    }
}
