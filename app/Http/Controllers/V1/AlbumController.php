<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlbumRequest;
use App\Http\Requests\UpdateAlbumRequest;
use App\Http\Resources\V1\AlbumResource;
use App\Models\Album;
use App\Traits\OwnsModel;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    use OwnsModel;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return AlbumResource::collection(Album::where('user_id', $request->user()->id)->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAlbumRequest $request)
    {
        $data = $request->all();
        $data['user_id'] = $request->user()->id;
        $album = Album::create($data);

        return new AlbumResource($album);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Album $album)
    {
        $this->authorizeOwnership($request, $album);

        return new AlbumResource($album);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAlbumRequest $request, Album $album)
    {
        $this->authorizeOwnership($request, $album);

        $album->update($request->all());

        return new AlbumResource($album);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Album $album)
    {
        $this->authorizeOwnership($request, $album);

        $album->delete();

        return response('', 204);
    }
}
