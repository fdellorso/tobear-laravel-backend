<?php

namespace App\Http\Controllers;

class FileController extends Controller
{
    public function show($filename)
    {
        $path = public_path('assets/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }
}
