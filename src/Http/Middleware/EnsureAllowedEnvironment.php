<?php

namespace SajidUlIslam\CrudGenerator\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAllowedEnvironment
{
    /**
     * Handle an incoming request.
     *
     * Restrict access to the CRUD generator UI to allowed environments only.
     * By default, only the 'local' environment is permitted.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedEnvironments = config('crud-generator.allowed_environments', ['local']);

        if (!app()->environment($allowedEnvironments)) {
            abort(403, 'CRUD Generator is not available in this environment.');
        }

        return $next($request);
    }
}
