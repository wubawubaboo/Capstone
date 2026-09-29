<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route group to one or more roles: `->middleware('role:secretary')`.
 * Registered as the `role` alias in bootstrap/app.php. Must run after `auth`.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        abort_unless(in_array($request->user()?->role, $allowed, true), 403, 'Unauthorized action.');

        return $next($request);
    }
}
