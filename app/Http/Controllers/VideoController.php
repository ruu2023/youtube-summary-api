<?php

namespace App\Http\Controllers;

use App\Http\Resources\VideoResource;
use App\Models\Video;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    // get
    public function index(Request $request) 
    {
        $query = Video::query();

        if($keyword = $request->input('q')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orwhere('description', 'like', "%{$keyword}%");
            });
        }

        $video = $query->latest('published_at')->get();

        return VideoResource::collection($video);
    }

    // store
    public function store(Request $request) 
    {
        // validation
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'published_at' => 'required|date'
        ]);

        $video = Video::create($validated);

        return (new VideoResource($video))
            ->response()
            ->setStatusCode(201);   
    }

    // show
    public function show(Video $video)
    {
        return new VideoResource($video);
    }

    // destroy
    public function destroy(Video $video)
    {
        $video->delete();
        // 204
        return response()->noContent();
    }
}
