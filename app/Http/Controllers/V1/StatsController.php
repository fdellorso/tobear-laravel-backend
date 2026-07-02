<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $total = $user->tasks()->count();
        $completed = $user->tasks()->where('completed', true)->count();
        $active = $total - $completed;
        $thisWeek = $user->tasks()
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
        $completedThisWeek = $user->tasks()
            ->where('completed', true)
            ->where('updated_at', '>=', now()->startOfWeek())
            ->count();

        return response()->json([
            'total' => $total,
            'completed' => $completed,
            'active' => $active,
            'this_week' => $thisWeek,
            'completed_this_week' => $completedThisWeek,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ]);
    }
}
