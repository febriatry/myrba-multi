<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        // Add permission middleware if needed
        // $this->middleware('permission:log view')->only('index');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $logs = ActivityLog::latest();
            
            return DataTables::of($logs)
                ->addIndexColumn()
                ->editColumn('created_at', function($row) {
                    return $row->created_at->format('d/m/Y H:i:s');
                })
                ->editColumn('subject_type', function($row) {
                    return class_basename($row->subject_type);
                })
                ->addColumn('properties', function($row) {
                    return json_encode($row->properties);
                })
                ->rawColumns(['properties'])
                ->make(true);
        }

        return view('activity_logs.index');
    }
}
