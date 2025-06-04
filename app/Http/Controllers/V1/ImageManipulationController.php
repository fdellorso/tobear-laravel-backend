<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResizeImageRequest;
use App\Models\ImageManipulation;
use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use App\Http\Resources\V1\ImageManipulationResource;
use Illuminate\Support\Facades\Storage;

class ImageManipulationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return ImageManipulationResource::collection(ImageManipulation::where('user_id', $request->user()->id)->paginate());
    }

    public function byAlbum(Request $request, Album $album)
    {
        if ($request->user()->id != $album->user_id) {
            abort(403, 'Unauthorized');
        }

        $where = [
            'album_id' => $album->id,
        ];

        return ImageManipulationResource::collection(ImageManipulation::where($where)->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function resize(ResizeImageRequest $request)
    {
        $all = $request->all();

        /**
         * @var UploadedFile|string $image
         */
        $image = $all['image'];
        unset($all['image']);
        $data = [
            'type' => ImageManipulation::TYPE_RESIZE,
            'data' => json_encode($all),
            'user_id' => $request->user()->id,
        ];

        if (isset($all['album_id'])) {
            $album = Album::find($all['album_id']);
            if ($request->user()->id != $album->user_id) {
                abort(403, 'Unauthorized');
            }

            $data['album_id'] = $all['album_id'];
        }

        // $dir = 'assets/' . Str::random() . '/';
        // $absolutePath = public_path($dir);
        // File::makeDirectory($absolutePath);

        $dir = Str::random() . '/';
        $absolutePath = Storage::disk('public_uploads')->path($dir);
        Storage::disk('public_uploads')->makeDirectory($dir);

        if ($image instanceof UploadedFile) {
            $data['name'] = $image->getClientOriginalName();
            $filename = pathinfo($data['name'], PATHINFO_FILENAME);
            $extension = $image->getClientOriginalExtension();
            $originalPath = $absolutePath . $data['name'];

            $image->move($absolutePath, $data['name']);
        } else {
            $data['name'] = pathinfo($image, PATHINFO_BASENAME);
            $filename = pathinfo($image, PATHINFO_FILENAME);
            $extension = pathinfo($image, PATHINFO_EXTENSION);
            $originalPath = $absolutePath . $data['name'];

            copy($image, $originalPath);
        }
        $data['path'] = $dir . $data['name'];

        $w = $all['w'];
        $h = $all['h'] ?? false;

        list($width, $height, $image) = $this->getImageWidthAndHeight($w, $h, $originalPath);

        $resizedFilename = $filename . '-resized.' . $extension;
        $image->resize($width, $height)->save($absolutePath . $resizedFilename);
        $data['output_path'] = $dir . $resizedFilename;

        $imageManipulation = ImageManipulation::create($data);

        return new ImageManipulationResource($imageManipulation);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, ImageManipulation $image)
    {
        $this->authorizeTask($request, $image);

        return new ImageManipulationResource($image);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, ImageManipulation $image)
    {
        $this->authorizeTask($request, $image);

        $filePath = $image->path;
        if (Storage::disk('public_uploads')->exists($filePath)) {
            Storage::disk('public_uploads')->delete($filePath);
        }

        $outputPath = $image->output_path;
        if (Storage::disk('public_uploads')->exists($outputPath)) {
            Storage::disk('public_uploads')->delete($outputPath);
        }

        $folderPath = dirname($image->path);
        $allFiles = Storage::disk('public_uploads')->allFiles($folderPath);
        $allDirectories = Storage::disk('public_uploads')->directories($folderPath);
        if (empty($allFiles) && empty($allDirectories) && Storage::disk('public_uploads')->exists($folderPath)) {
            Storage::disk('public_uploads')->deleteDirectory($folderPath);
        }

        $image->delete();

        return response('', 204);
    }

    protected function getImageWidthAndHeight($w, $h, string $originalPath): array
    {
        $image = Image::read($originalPath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        if (str_ends_with($w, '%')) {
            $ratioW = (float) str_replace('%', '', $w);
            $ratioH = $h ? (float) str_replace('%', '', $h) : $ratioW;

            $newWidth = ($originalWidth * $ratioW) / 100;
            $newHeight = ($originalHeight * $ratioH) / 100;
        } else {
            $newWidth = (float) $w;
            $newHeight = $h ? (float) $h : $originalHeight * $newWidth / $originalWidth;
        }

        return [
            $newWidth,
            $newHeight,
            $image
        ];
    }

    protected function authorizeTask(Request $request, ImageManipulation $image): void
    {
        if ($request->user()->id != $image->user_id) {
            abort(403, 'Unauthorized');
        }
    }
}
