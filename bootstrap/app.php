<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        // Add broadcast routes
       
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'handle.unauthorized' => \App\Http\Middleware\Permissions\HandleUnauthorizedAttempts::class,
            'auth.admin' => \App\Http\Middleware\AdminAuthenticate::class,
            'auth.broadcast' => \App\Http\Middleware\AuthenticateBroadcast::class, // Add this
        ]);
        
        // Protect broadcast routes with auth middleware
        $middleware->appendToGroup('broadcast', [
            'auth.broadcast',
        ]);
    })
   
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You don\'t have access to perform this action.'
                ], 403);
            }
            
            return redirect()->back()->with('error', 'You don\'t have access to perform this action.');
        });
    })
    ->create();