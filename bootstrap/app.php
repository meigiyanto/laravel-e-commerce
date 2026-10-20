<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'payment/midtrans/notification',
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'role' => RoleMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('admin/*') ? route('admin.login') : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->expectsJson(),
        );
    
        $exceptions->render(function (
            \Throwable $e,
            Request $request
        ) {
            if (
                $request->is('api/*')
                || $request->expectsJson()
            ) {
                return null;
            }
    
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
    
            $area = $request->is('admin') || $request->is('admin/*') ? 'dashboard' : 'storefront';
    
            $specificView = "errors.{$area}.{$status}";
    
            if (view()->exists($specificView)) {
                return response()->view(
                    $specificView,
                    ['exception' => $e],
                    $status
                );
            }
    
            $fallbackView = $status >= 500 ? "errors.{$area}.5xx" : "errors.{$area}.4xx";
    
            if (view()->exists($fallbackView)) {
                return response()->view(
                    $fallbackView,
                    ['exception' => $e],
                    $status
                );
            }
    
            return null;
        });
    })
    ->create();
    /*
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->create();
    */
