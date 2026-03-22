<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrOvertimeApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:attendance manage')->only(['index', 'approve', 'reject']);
    }

    public function index(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'pending'));

        $rows = DB::table('hr_attendance_sessions as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->leftJoin('hr_employee_profiles as ep', 's.user_id', '=', 'ep.user_id')
            ->leftJoin('hr_jabatans as j', 'ep.jabatan_id', '=', 'j.id')
            ->where('s.date', $date)
            ->where('s.review_status', 'approved')
            ->where('s.overtime_minutes', '>', 0)
            ->when($status !== '', function ($query) use ($status) {
                if ($status === 'pending') {
                    $query->where('s.overtime_review_status', 'pending');
                } elseif ($status === 'approved') {
                    $query->where('s.overtime_review_status', 'approved');
                } elseif ($status === 'rejected') {
                    $query->where('s.overtime_review_status', 'rejected');
                }
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('u.name', 'like', '%' . $q . '%')
                        ->orWhere('u.email', 'like', '%' . $q . '%');
                });
            })
            ->select('s.*', 'u.name as user_name', 'u.email as user_email', 'j.name as jabatan_name')
            ->orderBy('u.name')
            ->paginate(20)
            ->withQueryString();

        return view('hr-overtime-approvals.index', compact('rows', 'date', 'q', 'status'));
    }

    public function approve(Request $request, int $id)
    {
        $row = DB::table('hr_attendance_sessions as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->where('s.id', $id)
            ->select('s.*', 'u.name as user_name')
            ->first();
        abort_if(!$row, 404);

        if ((string) ($row->review_status ?? '') !== 'approved') {
            return redirect()->route('hr-overtime-approvals.index', ['date' => $row->date])->with('error', 'Absensi belum divalidasi.');
        }

        $max = (int) ($row->overtime_minutes ?? 0);
        $validated = $request->validate([
            'approved_minutes' => 'nullable|integer|min:0|max:' . max(0, $max),
            'note' => 'nullable|string|max:255',
        ]);
        $approvedMinutes = isset($validated['approved_minutes']) ? (int) $validated['approved_minutes'] : $max;
        $approvedMinutes = max(0, min($max, $approvedMinutes));
        $note = isset($validated['note']) ? trim((string) $validated['note']) : null;
        $note = $note !== null && $note !== '' ? $note : null;

        DB::table('hr_attendance_sessions')->where('id', $id)->update([
            'overtime_review_status' => 'approved',
            'overtime_approved_minutes' => $approvedMinutes,
            'overtime_reviewed_by' => auth()->id(),
            'overtime_reviewed_at' => now(),
            'overtime_review_note' => $note,
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-overtime-approvals.index', ['date' => $row->date])->with('success', 'Lembur berhasil di-ACC.');
    }

    public function reject(Request $request, int $id)
    {
        $row = DB::table('hr_attendance_sessions')->where('id', $id)->first();
        abort_if(!$row, 404);

        $validated = $request->validate([
            'note' => 'nullable|string|max:255',
        ]);
        $note = isset($validated['note']) ? trim((string) $validated['note']) : null;
        $note = $note !== null && $note !== '' ? $note : null;

        DB::table('hr_attendance_sessions')->where('id', $id)->update([
            'overtime_review_status' => 'rejected',
            'overtime_approved_minutes' => 0,
            'overtime_reviewed_by' => auth()->id(),
            'overtime_reviewed_at' => now(),
            'overtime_review_note' => $note,
            'updated_at' => now(),
        ]);

        return redirect()->route('hr-overtime-approvals.index', ['date' => $row->date])->with('success', 'Lembur ditolak.');
    }
}
