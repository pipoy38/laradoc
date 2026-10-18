<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Transfer\DocsExporter;
use App\Support\Transfer\TransferArchive;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransferController extends Controller
{
    public function index()
    {
        return view('admin.transfer.index');
    }

    public function export()
    {
        $path = (new DocsExporter)->build();

        return response()->download($path, 'laradoc-export-'.now()->format('Ymd-Hi').'.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|max:204800|mimetypes:application/zip,application/x-zip-compressed']);

        try {
            $s = (new TransferArchive)->import($request->file('file')->getRealPath(), $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['file' => 'Import failed: '.$e->getMessage()]);
        }

        return redirect()->route('admin.transfer.index')->with('ok', sprintf(
            'Import complete. Categories: %d created, %d updated. Articles: %d created, %d updated, %d unchanged. Media: %d created, %d reused. Images skipped: %d.',
            $s['categories_created'], $s['categories_updated'], $s['articles_created'], $s['articles_updated'], $s['articles_unchanged'],
            $s['media_created'], $s['media_reused'] + $s['media_updated'], $s['images_dropped'],
        ));
    }
}
