<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($roles === [] || ! $user->hasAnyRole(...$roles)) {
            abort(403, 'You are not authorized to access this resource.');
        }

        return $next($request);
    }
}
