<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Image::latest()->get()->map(function ($image) {
            return [
                'id' => $image->id,
                'url' => url(Storage::disk("public_uploads")->url($image->path)),
                'label' => $image->label
            ];
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        // $path = $request->file('image')->store('images', "public_uploads");

        $filename = time() . '.' . $request->file('image')->getClientOriginalExtension();
        $destination = public_path('assets');

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $request->file('image')->move($destination, $filename);

        // Questo è il path relativo che puoi salvare nel DB
        $path = 'assets/' . $filename;

        $image = Image::create([
            'path' => $path,
            'label' => $request->label
        ]);

        return response($image, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Image $image)
    {
        // Costruisci il percorso completo del file
        $filePath = $image->path; // Esempio: 'images/1684499383.jpg'

        // Elimina il file se esiste
        if (Storage::disk('public_uploads')->exists($filePath)) {
            Storage::disk('public_uploads')->delete($filePath);
        }

        $image->delete();

        return response(null, 204);
    }
}
