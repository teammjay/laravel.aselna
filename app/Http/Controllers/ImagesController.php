<?php

namespace App\Http\Controllers;

use App\Models\Images;
use Illuminate\Http\Request;

class ImagesController extends Controller
{
    // Handle image upload
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:2048'
        ]);

        $imageFile = $request->file('image');
        $originalName = $imageFile->getClientOriginalName();
        $filePath = $imageFile->store('images', 'public');

        // Save image info and automatically generate reference ID
        $image = Images::create([
            'file_path' => $filePath,
            'original_name' => $originalName
        ]);

        $imagePath = url('storage/' . $image->file_path);

        return response()->json([
            'message' => 'Image uploaded successfully!',
            'reference_id' => $image->reference_id,
            'image_url' => $imagePath
        ], 201);
    }

    // Get image by reference ID
    public function getImage($referenceId)
    {
        $image = Images::where('reference_id', $referenceId)->first();

        if (!$image) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $imagePath = url('storage/' . $image->file_path);

        return response()->file($imagePath);
    }
}
