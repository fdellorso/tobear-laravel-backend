<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImageManipulationRequest;
use App\Http\Requests\UpdateImageManipulationRequest;
use App\Models\ImageManipulation;

class ImageManipulationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreImageManipulationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ImageManipulation $imageManipulation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateImageManipulationRequest $request, ImageManipulation $imageManipulation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ImageManipulation $imageManipulation)
    {
        //
    }
}
