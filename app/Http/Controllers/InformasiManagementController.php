<?php

namespace App\Http\Controllers;

use App\Models\InformasiManagement;
use App\Http\Requests\{StoreInformasiManagementRequest, UpdateInformasiManagementRequest};
use App\Services\Fcm\FcmClient;
use Yajra\DataTables\Facades\DataTables;
use Image;
use Illuminate\Support\Facades\Log;

class InformasiManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:informasi management view')->only('index', 'show');
        $this->middleware('permission:informasi management create')->only('create', 'store');
        $this->middleware('permission:informasi management edit')->only('edit', 'update');
        $this->middleware('permission:informasi management delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $informasiManagements = InformasiManagement::query();

            return Datatables::of($informasiManagements)
                ->addIndexColumn()
                ->addColumn('deskripsi', function ($row) {
                    return str($row->deskripsi)->limit(100);
                })

                ->addColumn('thumbnail', function ($row) {
                    if ($row->thumbnail == null) {
                        return 'https://via.placeholder.com/350?text=No+Image+Avaiable';
                    }
                    return asset('storage/uploads/thumbnails/' . $row->thumbnail);
                })

                ->addColumn('action', 'informasi-managements.include.action')
                ->toJson();
        }

        return view('informasi-managements.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('informasi-managements.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInformasiManagementRequest $request)
    {
        $attr = $request->validated();

        if ($request->file('thumbnail') && $request->file('thumbnail')->isValid()) {

            $path = storage_path('app/public/uploads/thumbnails/');
            $filename = $request->file('thumbnail')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('thumbnail')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            })->save($path . $filename);

            $attr['thumbnail'] = $filename;
        }

        $informasi = InformasiManagement::create($attr);

        try {
            if (($informasi->is_aktif ?? null) === 'Yes') {
                $topic = (string) config('fcm.default_topic', 'myrba_client');
                (new FcmClient())->sendToTopic(
                    topic: $topic,
                    title: (string) ($informasi->judul ?? 'Pengumuman'),
                    body: (string) str($informasi->deskripsi ?? '')->limit(160),
                    data: [
                        'type' => 'informasi',
                        'id' => (string) ($informasi->id ?? ''),
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('FCM push informasi gagal', ['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('informasi-managements.index')
            ->with('success', __('The informasiManagement was created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InformasiManagement $informasiManagement
     * @return \Illuminate\Http\Response
     */
    public function show(InformasiManagement $informasiManagement)
    {
        return view('informasi-managements.show', compact('informasiManagement'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\InformasiManagement $informasiManagement
     * @return \Illuminate\Http\Response
     */
    public function edit(InformasiManagement $informasiManagement)
    {
        return view('informasi-managements.edit', compact('informasiManagement'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\InformasiManagement $informasiManagement
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInformasiManagementRequest $request, InformasiManagement $informasiManagement)
    {
        $attr = $request->validated();
        $wasActive = ($informasiManagement->is_aktif ?? null) === 'Yes';

        if ($request->file('thumbnail') && $request->file('thumbnail')->isValid()) {

            $path = storage_path('app/public/uploads/thumbnails/');
            $filename = $request->file('thumbnail')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('thumbnail')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            })->save($path . $filename);

            // delete old thumbnail from storage
            if ($informasiManagement->thumbnail != null && file_exists($path . $informasiManagement->thumbnail)) {
                unlink($path . $informasiManagement->thumbnail);
            }

            $attr['thumbnail'] = $filename;
        }

        $informasiManagement->update($attr);

        try {
            $isActive = ($informasiManagement->is_aktif ?? null) === 'Yes';
            if ($isActive && !$wasActive) {
                $topic = (string) config('fcm.default_topic', 'myrba_client');
                (new FcmClient())->sendToTopic(
                    topic: $topic,
                    title: (string) ($informasiManagement->judul ?? 'Pengumuman'),
                    body: (string) str($informasiManagement->deskripsi ?? '')->limit(160),
                    data: [
                        'type' => 'informasi',
                        'id' => (string) ($informasiManagement->id ?? ''),
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('FCM push informasi update gagal', ['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('informasi-managements.index')
            ->with('success', __('The informasiManagement was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\InformasiManagement $informasiManagement
     * @return \Illuminate\Http\Response
     */
    public function destroy(InformasiManagement $informasiManagement)
    {
        try {
            $path = storage_path('app/public/uploads/thumbnails/');

            if ($informasiManagement->thumbnail != null && file_exists($path . $informasiManagement->thumbnail)) {
                unlink($path . $informasiManagement->thumbnail);
            }

            $informasiManagement->delete();

            return redirect()
                ->route('informasi-managements.index')
                ->with('success', __('The informasiManagement was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('informasi-managements.index')
                ->with('error', __("The informasiManagement can't be deleted because it's related to another table."));
        }
    }
}
