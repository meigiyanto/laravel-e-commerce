<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (! $request->user()) {
            return redirect()->guest(route('admin.login'));
        }

        if (! $request->user()->isAdmin()) {
            abort(403, 'Admin access required.');
        }

        return $next($request);
    }
}
