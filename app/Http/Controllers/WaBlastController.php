<?php

namespace App\Http\Controllers;

use App\Models\WaBlast;
use Illuminate\Contracts\View\View;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\{JsonResponse, RedirectResponse};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaBlastController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:wa session view')->only('index', 'show');
        $this->middleware('permission:wa session create')->only('create', 'store');
        $this->middleware('permission:wa session edit')->only('edit', 'update');
        $this->middleware('permission:wa session delete')->only('destroy');
    }


    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            $waBlasts = WaBlast::query();
            return DataTables::of($waBlasts)
                ->addIndexColumn()
                ->addColumn('status', function ($row) {
                    if ($row->status == 'STOPPED') {
                        return '<span class="badge bg-danger">' . $row->status . '</span>';
                    } else {
                        return '<span class="badge bg-success">' . $row->status . '</span>';
                    }
                })
                ->addColumn('is_aktif', function ($row) {
                    $disabled = $row->status == 'STOPPED' ? 'disabled' : '';
                    $checked = $row->is_aktif == 'Yes' ? 'checked' : '';

                    return '
                <div class="form-check form-switch">
                    <input class="form-check-input toggle-aktif" type="checkbox"
                        data-id="' . $row->id . '"
                        ' . $checked . '
                        ' . $disabled . '>
                    <label class="form-check-label">' . $row->is_aktif . '</label>
                </div>';
                })
                ->addColumn('action', 'wa-blast.include.action')
                ->rawColumns(['status', 'is_aktif', 'action'])
                ->toJson();
        }

        return view('wa-blast.index');
    }

    public function updateAktif(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:sessions,id',
            'is_aktif' => 'required|string|in:Yes,No'
        ]);

        // First set all records to "No"
        WaBlast::query()->update(['is_aktif' => 'No']);

        // Then update the selected record if is_aktif is "Yes"
        if ($request->is_aktif == 'Yes') {
            $waBlast = WaBlast::find($request->id);
            $waBlast->is_aktif = 'Yes';
            $waBlast->save();
        }

        return response()->json(['success' => true]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_name' => 'required|string|max:255',
        ]);

        $apiKey = bin2hex(random_bytes(16));

        WaBlast::create([
            'session_name' => $validated['session_name'],
            'status' => 'STOPPED',
            'api_key' => $apiKey,
            'user_id' => auth()->id(),
        ]);

        return to_route('wa-blast.index')->with('success', __('WA Blast berhasil dibuat.'));
    }

    public function show(WaBlast $waBlast): View
    {
        return view('wa-blast.show', compact('waBlast'));
    }

    public function destroy(WaBlast $waBlast): RedirectResponse
    {
        try {
            $waBlast->delete();

            return to_route('wa-blast.index')->with('success', __('WA Blast berhasil dihapus.'));
        } catch (\Exception $e) {
            return to_route('wa-blast.index')->with('error', __("WA Blast tidak dapat dihapus karena terkait dengan tabel lain."));
        }
    }
}
