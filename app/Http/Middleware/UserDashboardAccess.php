<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserDashboardAccess
{
    /**
     * Handle an incoming request.
     *
     * Allows admin/super-admin users to proceed normally.
     * Organization users are redirected to their organization dashboard.
     * Work center users are redirected to their first assigned center's dashboard.
     * Other users are denied access.
     *
     * @param  \Closure(\Illuminate\Http\ Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Allow admin and super-admin users
        if ($user && ($user->hasRole('admin') || $user->hasRole('super-admin'))) {
            return $next($request);
        }

        // Redirect organization users to their organization dashboard
        if ($user && $user->hasRole('organization') && $user->organization_id) {
            return redirect()->route('organization.dashboard', $user->organization_id);
        }

        // Redirect work center users to their work centers selection page
        if ($user && $user->hasRole('work_center_user')) {
            return redirect()->route('my-work-centers');
        }

        // Deny access for all other users
        abort(403);
    }
}
