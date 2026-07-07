<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ExecuteArtisanCommandController extends Controller
{
    private const READ_ONLY_COMMANDS = [
        'storage:link',
        'config:clear',
        'cache:clear',
        'route:list',
        'migrate:status',
        'queue:failed',
    ];

    public function __invoke(Request $request, $command)
    {
        if (! in_array($command, self::READ_ONLY_COMMANDS, true)) {
            abort(404);
        }

        Artisan::call($command);

        return response(Artisan::output());
    }

    public function migrate(Request $request)
    {
        Artisan::call('migrate', ['--force' => true]);

        return response(Artisan::output());
    }
}
