<?php

namespace App\Http\Controllers;

use App\Models\ConfigPesanNotif;
use App\Http\Requests\{UpdateConfigPesanNotifRequest};

class ConfigPesanNotifController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:config pesan notif view')->only('index');
        $this->middleware('permission:config pesan notif edit')->only('update');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $configPesanNotif = ConfigPesanNotif::first();
        return view('config-pesan-notifs.edit', compact('configPesanNotif'));
    }

    public function update(UpdateConfigPesanNotifRequest $request, ConfigPesanNotif $configPesanNotif)
    {

        $configPesanNotif->update($request->validated());

        return redirect()
            ->route('config-pesan-notifs.index')
            ->with('success', __('The configPesanNotif was updated successfully.'));
    }
}
