<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImageRequest;
use App\Http\Resources\V1\ImageResource;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return ImageResource::collection(Image::where('user_id', $request->user()->id)->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreImageRequest $request)
    {
        $all = $request->all();
        $image = $all['image'];
        unset($all['image']);
        $data = [
            'label' => $all['label'] ?? null,
            'user_id' => $request->user()->id,
        ];

        $dir = Str::random().'/';
        $absolutePath = Storage::disk('public_uploads')->path($dir);
        Storage::disk('public_uploads')->makeDirectory($dir);

        $dataName = $image->getClientOriginalName();
        $image->move($absolutePath, $dataName);
        $data['path'] = $dir.$dataName;

        $imageStored = Image::create($data);

        return response($imageStored, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Image $image)
    {
        $this->authorizeImage($request, $image);

        $filePath = $image->path;
        if (Storage::disk('public_uploads')->exists($filePath)) {
            Storage::disk('public_uploads')->delete($filePath);
        }

        $folderPath = dirname($image->path);
        $allFiles = Storage::disk('public_uploads')->allFiles($folderPath);
        $allDirectories = Storage::disk('public_uploads')->directories($folderPath);
        if (empty($allFiles) && empty($allDirectories) && Storage::disk('public_uploads')->exists($folderPath)) {
            Storage::disk('public_uploads')->deleteDirectory($folderPath);
        }

        $image->delete();

        return response(null, 204);
    }

    protected function authorizeImage(Request $request, Image $image): void
    {
        if ($request->user()->id != $image->user_id) {
            abort(403, 'Unauthorized');
        }
    }
}
