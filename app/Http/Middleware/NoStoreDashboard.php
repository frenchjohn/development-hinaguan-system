<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard pages are always re-rendered live (check-ins, counts, weather).
 * Never let the browser (or any proxy) cache them — a cached page is how a
 * stale/old design can reappear after a reload. Pages opt in by using this
 * middleware on the staff/admin route groups.
 */
class NoStoreDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->header('Pragma', 'no-cache');
        }

        return $response;
    }
}
