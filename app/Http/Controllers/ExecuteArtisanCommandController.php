<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ExecuteArtisanCommandController extends Controller
{
    private const WHITELIST = [
        'migrate',
        'storage:link',
        'config:cache',
        'route:cache',
        'view:cache',

        'route:list',
        'cache:clear',
        'config:clear',
        'migrate:status',
        'queue:failed',
    ];

    public function __invoke(Request $request, $command)
    {
        $token = env('ARTISAN_DEBUG_TOKEN');

        if ($token === null || $request->query('token') !== $token) {
            abort(404);
        }

        if (! in_array($command, self::WHITELIST, true)) {
            abort(404);
        }

        $parameters = [];

        if ($command === 'migrate') {
            $parameters['--force'] = true;
        }

        Artisan::call($command, $parameters);

        return response(Artisan::output());
    }
}
