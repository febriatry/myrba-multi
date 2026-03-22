<?php

namespace App\Http\Controllers;

use App\Models\BannerManagement;
use App\Http\Requests\{StoreBannerManagementRequest, UpdateBannerManagementRequest};
use Yajra\DataTables\Facades\DataTables;
use Image;

class BannerManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:banner management view')->only('index', 'show');
        $this->middleware('permission:banner management create')->only('create', 'store');
        $this->middleware('permission:banner management edit')->only('edit', 'update');
        $this->middleware('permission:banner management delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            $bannerManagements = BannerManagement::query();

            return Datatables::of($bannerManagements)
                ->addIndexColumn()
                ->addColumn('file_banner', function ($row) {
                    if ($row->file_banner == null) {
                    return 'https://via.placeholder.com/350?text=No+Image+Avaiable';
                }
                    return asset('storage/uploads/file_banners/' . $row->file_banner);
                })

                ->addColumn('action', 'banner-managements.include.action')
                ->toJson();
        }

        return view('banner-managements.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('banner-managements.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBannerManagementRequest $request)
    {
        $attr = $request->validated();

        if ($request->file('file_banner') && $request->file('file_banner')->isValid()) {

            $path = storage_path('app/public/uploads/file_banners/');
            $filename = $request->file('file_banner')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('file_banner')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
				$constraint->aspectRatio();
            })->save($path . $filename);

            $attr['file_banner'] = $filename;
        }

        BannerManagement::create($attr);

        return redirect()
            ->route('banner-managements.index')
            ->with('success', __('The bannerManagement was created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BannerManagement $bannerManagement
     * @return \Illuminate\Http\Response
     */
    public function show(BannerManagement $bannerManagement)
    {
        return view('banner-managements.show', compact('bannerManagement'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BannerManagement $bannerManagement
     * @return \Illuminate\Http\Response
     */
    public function edit(BannerManagement $bannerManagement)
    {
        return view('banner-managements.edit', compact('bannerManagement'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BannerManagement $bannerManagement
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBannerManagementRequest $request, BannerManagement $bannerManagement)
    {
        $attr = $request->validated();

        if ($request->file('file_banner') && $request->file('file_banner')->isValid()) {

            $path = storage_path('app/public/uploads/file_banners/');
            $filename = $request->file('file_banner')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('file_banner')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
				$constraint->aspectRatio();
            })->save($path . $filename);

            // delete old file_banner from storage
            if ($bannerManagement->file_banner != null && file_exists($path . $bannerManagement->file_banner)) {
                unlink($path . $bannerManagement->file_banner);
            }

            $attr['file_banner'] = $filename;
        }

        $bannerManagement->update($attr);

        return redirect()
            ->route('banner-managements.index')
            ->with('success', __('The bannerManagement was updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BannerManagement $bannerManagement
     * @return \Illuminate\Http\Response
     */
    public function destroy(BannerManagement $bannerManagement)
    {
        try {
            $path = storage_path('app/public/uploads/file_banners/');

            if ($bannerManagement->file_banner != null && file_exists($path . $bannerManagement->file_banner)) {
                unlink($path . $bannerManagement->file_banner);
            }

            $bannerManagement->delete();

            return redirect()
                ->route('banner-managements.index')
                ->with('success', __('The bannerManagement was deleted successfully.'));
        } catch (\Throwable $th) {
            return redirect()
                ->route('banner-managements.index')
                ->with('error', __("The bannerManagement can't be deleted because it's related to another table."));
        }
    }
}
