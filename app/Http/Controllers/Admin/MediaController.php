<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.media.index', ['media' => Media::with('uploader')->latest()->paginate(40)]);
    }

    public function upload(Request $request)
    {
        $request->validate(['file' => 'required|file|max:5120|mimes:jpg,jpeg,png,gif,webp,svg|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml']);
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $dir = 'media/'.now()->format('Y/m');
        $name = Str::random(16).'.'.$ext;
        if ($ext === 'svg') {
            $svg = preg_replace(['/<script\b[^>]*>.*?<\/script>/is', '/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '/\son\w+="[^"]*"/i'], '', file_get_contents($file->getRealPath()));
            Storage::disk('public')->put("$dir/$name", $svg);
            [$w, $h] = [null, null];
        } else {
            $path = $file->storeAs($dir, $name, 'public');
            $size = @getimagesize(Storage::disk('public')->path($path));
            [$w, $h] = $size ? [$size[0], $size[1]] : [null, null];
        }
        $media = Media::create(['disk_path' => "$dir/$name", 'original_name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType(), 'size' => $file->getSize(),
            'width' => $w, 'height' => $h, 'uploaded_by' => $request->user()->id]);
        if ($request->expectsJson()) { return response()->json(['location' => $media->url(), 'id' => $media->id], 201); }
        return redirect()->route('admin.media.index')->with('ok', 'Uploaded.');
    }

    public function update(Request $request, Media $media)
    {
        $media->update($request->validate(['alt' => 'nullable|string|max:255']));
        return back()->with('ok', 'Alt text saved.');
    }

    public function destroy(Media $media)
    {
        $inUse = in_array((string) $media->id, [setting('logo_media_id'), setting('favicon_media_id')], true) || Article::where('og_image_id', $media->id)->exists();
        if ($inUse) { return back()->with('err', 'This file is used as the logo, favicon or a share image. Change that first.'); }
        Storage::disk('public')->delete($media->disk_path);
        $media->delete();
        return back()->with('ok', 'File deleted.');
    }
}
