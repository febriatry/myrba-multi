<?php

namespace App\Http\Controllers;

use App\Models\SettingWeb;
use App\Http\Requests\{UpdateSettingWebRequest};
use Image;

class SettingWebController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:setting web view')->only('index');
        $this->middleware('permission:setting web edit')->only('update');
    }

    public function index()
    {
        $settingWeb = SettingWeb::first();
        return view('setting-webs.edit', compact('settingWeb'));
    }


    public function update(UpdateSettingWebRequest $request, SettingWeb $settingWeb)
    {
        $attr = $request->validated();

        if ($request->file('logo') && $request->file('logo')->isValid()) {

            $path = storage_path('app/public/uploads/logos/');
            $filename = $request->file('logo')->hashName();

            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            Image::make($request->file('logo')->getRealPath())->resize(500, 500, function ($constraint) {
                $constraint->upsize();
                $constraint->aspectRatio();
            })->save($path . $filename);

            // delete old logo from storage
            if ($settingWeb->logo != null && file_exists($path . $settingWeb->logo)) {
                unlink($path . $settingWeb->logo);
            }

            $attr['logo'] = $filename;
        }

        $settingWeb->update($attr);

        return redirect()
            ->route('setting-webs.index')
            ->with('success', __('The settingWeb was updated successfully.'));
    }
}
