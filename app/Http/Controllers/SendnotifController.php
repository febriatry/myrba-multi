<?php

namespace App\Http\Controllers;

use App\Models\AreaCoverage;
use Illuminate\Http\Request;
use App\Models\Pelanggan;
use App\Models\WaMessageStatusLog;

class SendnotifController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sendnotif view')->only('index', 'show');
    }
    public function index()
    {
        $areaCoverages = AreaCoverage::get();
        return view('sendnotifs.index', [
            'areaCoverages' => $areaCoverages,
        ]);
    }

    public function kirim_pesan(Request $request)
    {
        $request->validate([
            'pesan' => 'required|string',
            'coverage_area' => 'nullable',
            'odc' => 'nullable',
            'odp' => 'nullable',
        ]);

        $waGateway = getWaGatewayActive();
        if ($waGateway->is_aktif !== 'Yes') {
            return redirect()
                ->route('sendnotifs.index')
                ->with('error', __('WA Broadcast sedang nonaktif.'));
        }

        $recipients = collect();
        if ($request->odp != null) {
            $recipients = Pelanggan::where('odp', $request->odp)
                ->where('status_berlangganan', 'Aktif')
                ->get();
        } elseif ($request->odc != null) {
            $recipients = Pelanggan::where('odc', $request->odc)
                ->where('status_berlangganan', 'Aktif')
                ->get();
        } else {
            $recipients = Pelanggan::where('coverage_area', $request->coverage_area)
                ->where('status_berlangganan', 'Aktif')
                ->get();
        }

        if ($recipients->isEmpty()) {
            return redirect()
                ->route('sendnotifs.index')
                ->with('error', __('Tidak ada pelanggan aktif yang menjadi target.'));
        }

        $success = 0;
        $failed = 0;
        $errors = [];
        foreach ($recipients as $value) {
            try {
                $broadcastPayload = (object) [
                    'nama' => $value->nama,
                    'no_layanan' => $value->no_layanan,
                    'no_wa' => $value->no_wa,
                    'pesan' => $request->pesan,
                    'raw_message' => $request->pesan,
                    'broadcast_message' => $request->pesan,
                ];
                $parsed = sendNotifWa(
                    $waGateway->api_key ?? '',
                    $broadcastPayload,
                    'broadcast',
                    strval($value->no_wa)
                );
                $raw = $parsed->raw ?? null;
                $rawArray = is_array($raw) ? $raw : json_decode(json_encode($raw), true);
                $messageId = $parsed->message_id ?? data_get($rawArray, 'messages.0.id');
                $status = ($parsed->status === true || $parsed->status === 'true') ? 'sent' : 'failed';
                $broadcastMessage = (string) $request->pesan;
                $broadcastKey = hash('sha256', $broadcastMessage);
                WaMessageStatusLog::create([
                    'message_id' => $messageId ?: ('blast-' . uniqid()),
                    'recipient_id' => strval($value->no_wa),
                    'status' => $status,
                    'type' => 'broadcast',
                    'status_at' => now(),
                    'errors' => $status === 'sent' ? null : [['message' => $parsed->message ?? 'Unknown error']],
                    'payload' => array_merge(
                        [
                            'broadcast_message' => $broadcastMessage,
                            'broadcast_key' => $broadcastKey,
                        ],
                        is_array($rawArray) ? $rawArray : ['raw' => $raw]
                    ),
                ]);

                if ($status === 'sent') {
                    $success++;
                } else {
                    $failed++;
                    $errors[] = $value->no_wa . ': ' . ($parsed->message ?? 'Unknown error');
                }
                \Log::info('WA blast send result', [
                    'no_wa' => $value->no_wa,
                    'successful' => $status === 'sent',
                    'message' => $parsed->message ?? '',
                    'raw' => $raw,
                ]);
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $value->no_wa . ': ' . $e->getMessage();
                WaMessageStatusLog::create([
                    'message_id' => 'blast-' . uniqid(),
                    'recipient_id' => strval($value->no_wa),
                    'status' => 'failed',
                    'type' => 'broadcast',
                    'status_at' => now(),
                    'errors' => [['message' => $e->getMessage()]],
                    'payload' => ['exception' => $e->getMessage()],
                ]);
                \Log::error('WA blast send exception', [
                    'no_wa' => $value->no_wa,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($failed > 0) {
            $errorPreview = implode(' | ', array_slice($errors, 0, 3));
            return redirect()
                ->route('sendnotifs.index')
                ->with('error', __('Kirim pemberitahuan WA selesai. Berhasil: :success, Gagal: :failed. Detail: :detail', [
                    'success' => $success,
                    'failed' => $failed,
                    'detail' => $errorPreview,
                ]));
        }

        return redirect()
            ->route('sendnotifs.index')
            ->with('success', __('Kirim pemberitahuan WA berhasil. Total terkirim: :total', ['total' => $success]));
    }
}
