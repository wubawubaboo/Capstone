<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Signed-out visitors to a staff area go to the staff portal login,
        // everyone else to the resident login.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('secretary', 'secretary/*', 'vawc', 'vawc/*', 'admin', 'admin/*')
            ? route('staff.login')
            : route('login'));

        // Signed-in users visiting a login or registration page go to their
        // role's landing page instead.
        $middleware->redirectUsersTo(fn (Request $request) => route($request->user()->homeRoute() ?? 'landing'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (InvalidStatusTransitionException $e, $request) {
            return back()->withErrors(['error' => $e->getMessage()]);
        });
    })->create();
