<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'approved.vendor' => \App\Http\Middleware\EnsureApprovedVendor::class,
        ]);

        // Demo mode routes each visitor to their own pool database. Self-disables
        // when demo.enabled is false, so it's harmless in normal operation.
        $middleware->web(append: [
            \App\Http\Middleware\DemoDatabase::class,
        ]);

        // The connection switch must happen *before* anything touches the
        // database. Appending alone runs it last — after StartSession (the
        // session driver is `database`) and after SubstituteBindings resolves
        // route-model bindings, both of which would already have queried the
        // base `.env` database. Pin it into the priority list just before
        // StartSession: that's after EncryptCookies (so the slot cookie is
        // decrypted) but ahead of every other DB-touching middleware.
        $middleware->prependToPriorityList(
            before: \Illuminate\Session\Middleware\StartSession::class,
            prepend: \App\Http\Middleware\DemoDatabase::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
