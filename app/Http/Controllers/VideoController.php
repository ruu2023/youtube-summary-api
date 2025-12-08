<?php

namespace App\Http\Controllers;

use App\Http\Resources\VideoResource;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

    // import
    public function import(Request $request)
    {
        $validated = $request->validate([
            'video_id' => 'required|string',
        ]);

        $videoId = $validated['video_id'];
        $apiKey = config('services.youtube.key');

        $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'id' => $videoId,
            'key' => $apiKey,
            'part' => 'snippet'
        ]);

        if($response->failed())
        {
            return response()->json(['error' => 'Youtube API Error'], 500);
        }

        $items = $response->json('items');

        if(empty($items))
        {
            return response()->json(['error' => 'Video not found'], 404);
        }

        $snippet = $items[0]['snippet'];

        $video = Video::create([
            'user_id' => 1,
            'title' => $snippet['title'],
            'description' => $snippet['description'],
            'published_at' => date('Y-m-d H:i:s', strtotime($snippet['publishedAt'])),
        ]);

        return (new VideoResource($video))
            ->response()
            ->setStatusCode(201);
    }
}
    
