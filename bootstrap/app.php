<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(function () {
            $user = Auth::user();
            if ($user) {
                if ($user->role === 'Administrator' || $user->role === 'Admin') {
                    return '/admin/dashboard';
                } elseif ($user->role === 'Supervisor') {
                    return '/supervisor/calendar';
                } else {
                    return '/staff/dashboard';
                }
            }
            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
