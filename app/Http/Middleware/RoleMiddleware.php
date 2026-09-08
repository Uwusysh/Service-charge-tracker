<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthenticated.');
        }

        $allowed = [];
        foreach ($roles as $roleGroup) {
            foreach (explode(',', $roleGroup) as $role) {
                $role = trim($role);
                if ($role !== '') {
                    $allowed[] = $role;
                }
            }
        }

        if ($user->isAdministrator()) {
            return $next($request);
        }

        if (! in_array($user->role, $allowed, true)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
