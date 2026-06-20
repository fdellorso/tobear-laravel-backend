<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait OwnsModel
{
    protected function authorizeOwnership(Request $request, Model $model): void
    {
        if ($request->user()->id !== $model->user_id) {
            abort(403, 'Unauthorized');
        }
    }
}
