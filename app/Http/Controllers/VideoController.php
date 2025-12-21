<?php

namespace App\Http\Controllers;

use App\Http\Resources\VideoResource;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

class VideoController extends Controller
{
    // get
    public function index(Request $request) 
    {
        $query = $request->user()->videos();

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
            'video_id' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'published_at' => 'required|date',
            'category_id' => 'nullable|integer|exists:categories,id'
        ]);
        
        $validated['user_id'] = $request->user()->id;

        $video = Video::create($validated);

        return (new VideoResource($video))
            ->response()
            ->setStatusCode(201);   
    }

    // show
    public function show(Video $video)
    {
        Gate::authorize('view', $video);
        return new VideoResource($video);
    }
    
    // update
    public function update(Request $request, Video $video)
    {
        // 本人のデータチェック
        Gate::authorize('update', $video);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'published_at' => 'nullable|date',
            'category_id' => 'nullable|integer|exists:categories,id'
        ]);
        
        $video->update($validated);
        
        return new VideoResource($video);
    }

    // destroy
    public function destroy(Video $video)
    {
        Gate::authorize('delete', $video);
        
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
            'video_id' => $videoId,
            'title' => $snippet['title'],
            'description' => $snippet['description'],
            'published_at' => date('Y-m-d H:i:s', strtotime($snippet['publishedAt'])),
        ]);

        return (new VideoResource($video))
            ->response()
            ->setStatusCode(201);
    }

    // channel import 
    public function importChannel(Request $request) 
    {
        $validated = $request->validate([
            'channel_id' => 'required|string',
            'from' => 'required|date',
            'to' => 'required|date'
        ]);

        $apiKey = config('services.youtube.key');
        $channelId = $validated['channel_id'];

        $params = [
            'key' => $apiKey,
            'part' => 'contentDetails'
        ];

        if(str_starts_with($channelId, '@'))
        {
            $params['forHandle'] = $channelId;
        } else {
            $params['id'] = $channelId;
        }

        // create carbon instance
        $fromDate = Carbon::parse($validated['from'])->startOfDay();
        $toDate = Carbon::parse($validated['to'])->endOfDay();

        // 1. get playlist in channel's updated videos
        $channelResponse = Http::get('https://www.googleapis.com/youtube/v3/channels', $params);

        if($channelResponse->failed())
        {
            return response()->json(['error' => 'Channel API Error'], 500);
        }

        $channelItems = $channelResponse->json('items');
        if(empty($channelItems))
        {
            return response()->json(['error' => 'Channel no found'], 404);
        }

        // get playlist ids
        $uploadPlaylistId = $channelItems[0]['contentDetails']['relatedPlaylists']['uploads'];

        // 2. get video details and filter by day
        $nextPageToken = null;
        $totalImported = 0;
        $isFinished = false;

        do {
            $response = Http::get('https://www.googleapis.com/youtube/v3/playlistItems', [
                'key' => $apiKey,
                'playlistId' => $uploadPlaylistId,
                'part' => 'snippet',
                'maxResults' => 50,
                'pageToken' => $nextPageToken
            ]);

            if($response->failed())
            {
                return response()->json(['error' => 'Playlist API Error'], 500);
            }

            $data = $response->json();
            $items = $data['items'] ?? [];

            foreach($items as $item)
            {
                $snippet =  $item['snippet'];
                $publishedAt = Carbon::parse($snippet['publishedAt']);

                // checkdate
                // continue after to
                if($publishedAt->gt($toDate))
                {
                    continue;
                }

                // break before from
                if($publishedAt->lt($fromDate))
                {
                    $isFinished = true;
                    break;
                }

                $videoId = $snippet['resourceId']['videoId'];

                Video::updateOrCreate(
                    ['videoId' => $videoId],
                    [
                        'video_id' => $videoId,
                        'user_id' => 1,
                        'title' => $snippet['title'],
                        'description' => $snippet['description'],
                        'published_at' => $publishedAt->format('Y-m-d H:i:s'),
                    ]
                    );

                $totalImported++;
            }

            if($isFinished)
            {
                break;
            }

            $nextPageToken = $data['nextPageToken'] ?? null;
        } while ($nextPageToken);

        return response()->json([
            'message' => 'Import process completed (Low Cost Model).',
            'count' => $totalImported
        ], 200);
    }
}
    
