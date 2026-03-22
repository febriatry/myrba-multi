<?php

namespace App\Http\Controllers;

use App\Models\WaMessageStatusLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WaStatusLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'platform.team', 'role:Platform Owner'])->only('index', 'exportCsv');
    }

    public function index(Request $request)
    {
        $filters = $this->getFilters($request);
        $query = $this->buildQuery($filters);

        $logs = (clone $query)
            ->orderByDesc('status_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $successStatuses = ['sent', 'delivered', 'read'];
        $summary = [
            'total' => (clone $query)->count(),
            'success' => (clone $query)->whereIn('status', $successStatuses)->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
        ];

        $statusOptions = WaMessageStatusLog::query()
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('wa-status-logs.index', compact('logs', 'summary', 'statusOptions', 'filters'));
    }

    public function exportCsv(Request $request)
    {
        $filters = $this->getFilters($request);
        $rows = $this->buildQuery($filters)
            ->orderByDesc('status_at')
            ->orderByDesc('id')
            ->get();

        $filename = 'wa-status-logs-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['status_at', 'status', 'recipient_id', 'message_id', 'type', 'error']);

            foreach ($rows as $row) {
                $errorText = '-';
                if (is_array($row->errors) && count($row->errors) > 0) {
                    $errorText = $row->errors[0]['title'] ?? json_encode($row->errors);
                }

                fputcsv($handle, [
                    optional($row->status_at)->format('Y-m-d H:i:s'),
                    $row->status,
                    $row->recipient_id,
                    $row->message_id,
                    $row->type,
                    $errorText,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function getFilters(Request $request): array
    {
        return [
            'status' => $request->query('status'),
            'recipient_id' => $request->query('recipient_id'),
            'message_id' => $request->query('message_id'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
        ];
    }

    private function buildQuery(array $filters)
    {
        $query = WaMessageStatusLog::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['recipient_id'])) {
            $query->where('recipient_id', 'like', '%' . $filters['recipient_id'] . '%');
        }

        if (!empty($filters['message_id'])) {
            $query->where('message_id', 'like', '%' . $filters['message_id'] . '%');
        }

        if (!empty($filters['start_date'])) {
            $startAt = Carbon::parse($filters['start_date'])->startOfDay();
            $query->where('status_at', '>=', $startAt);
        }

        if (!empty($filters['end_date'])) {
            $endAt = Carbon::parse($filters['end_date'])->endOfDay();
            $query->where('status_at', '<=', $endAt);
        }

        return $query;
    }
}
