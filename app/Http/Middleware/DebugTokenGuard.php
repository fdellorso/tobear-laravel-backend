<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DebugTokenGuard
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Debug-Token');

        if ($token !== config('app.debug_token')) {
            abort(404);
        }

        $allowlist = config('app.debug_ip_allowlist', []);
        if (! empty($allowlist) && ! in_array($request->ip(), $allowlist)) {
            abort(404);
        }

        return $next($request);
    }
}
