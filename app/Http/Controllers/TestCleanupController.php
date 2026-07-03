<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TestCleanupController extends Controller
{
    public function cleanup(Request $request)
    {
        if (! in_array(app()->environment(), ['local', 'testing'])) {
            abort(404);
        }

        $token = $request->header('X-Test-Token');
        if ($token !== config('app.test_cleanup_token')) {
            abort(403);
        }

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json(['deleted' => 0]);
        }

        $deleted = $user->tasks()->delete();

        return response()->json(['deleted' => $deleted]);
    }
}
