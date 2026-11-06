<?php

declare(strict_types=1);

use App\Models\Admin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin-auth.php'));

            Route::middleware(['web', 'auth:admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The back-office is the only authenticated area for now. Revisit when
        // the public site opens client accounts on the `web` guard.
        $middleware->redirectGuestsTo(fn (): string => route('admin.login'));
        $middleware->redirectUsersTo(static function (): string {
            $account = auth()->guard('admin')->user();

            return route($account instanceof Admin ? $account->role->home() : 'admin.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A screen of the office refused to an account is said inside the office, with the way back.
        $exceptions->render(static function (HttpException $refused, Request $request): ?Response {
            $account = auth()->guard('admin')->user();

            if ($refused->getStatusCode() !== 403 || ! $account instanceof Admin || ! $request->is('admin', 'admin/*') || $request->expectsJson()) {
                return null;
            }

            return response()->view('admin.forbidden', ['role' => $account->role], 403);
        });
    })->create();
