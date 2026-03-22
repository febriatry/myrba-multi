<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Fcm\FcmClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminController extends Controller
{
    private function allowedAreaCoverageIds(): array
    {
        try {
            return getAllowedAreaCoverageIdsForUser();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function isInvestorUser(User $user): bool
    {
        try {
            $roles = $user->getRoleNames()->map(fn ($v) => strtolower(trim((string) $v)))->all();
            foreach ($roles as $r) {
                if (str_contains($r, 'investor') || str_contains($r, 'mitra')) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }
        return false;
    }

    private function investorActiveRules(int $userId)
    {
        return DB::table('investor_share_rules')
            ->where('user_id', $userId)
            ->where('is_aktif', 'Yes')
            ->get();
    }

    private function investorPeriodOptions(?string $minStartPeriod): array
    {
        $periodOptions = [];
        try {
            $end = Carbon::now()->startOfMonth();
            $start = $minStartPeriod !== null
                ? Carbon::createFromFormat('Y-m', $minStartPeriod)->startOfMonth()
                : $end->copy()->subMonths(23);
            if ($start->greaterThan($end)) {
                $start = $end->copy();
            }
            $cursor = $end->copy();
            while ($cursor->greaterThanOrEqualTo($start)) {
                $periodOptions[] = $cursor->format('Y-m');
                $cursor->subMonth();
            }
        } catch (\Throwable $e) {
            $periodOptions = [Carbon::now()->format('Y-m')];
        }
        return $periodOptions;
    }

    private function investorResolvePelangganIds(int $userId, string $statusFilter): array
    {
        if (!DB::getSchemaBuilder()->hasTable('investor_share_rules')) {
            return [];
        }

        $rules = $this->investorActiveRules($userId);
        if ($rules->isEmpty()) {
            return [];
        }

        $statusFilter = trim($statusFilter);
        $status = $statusFilter === '' ? 'Aktif' : $statusFilter;
        $status = in_array($status, ['Aktif', 'Non Aktif', 'Semua'], true) ? $status : 'Aktif';

        $manualPelangganByRule = [];
        if (DB::getSchemaBuilder()->hasTable('investor_share_rule_pelanggans')) {
            $ruleIds = $rules->pluck('id')->map(fn ($v) => (int) $v)->all();
            if (!empty($ruleIds)) {
                $rows = DB::table('investor_share_rule_pelanggans')
                    ->select('rule_id', 'pelanggan_id')
                    ->whereIn('rule_id', $ruleIds)
                    ->where('is_included', 'Yes')
                    ->get();
                foreach ($rows as $row) {
                    $rid = (int) $row->rule_id;
                    if (!isset($manualPelangganByRule[$rid])) {
                        $manualPelangganByRule[$rid] = [];
                    }
                    $manualPelangganByRule[$rid][] = (int) $row->pelanggan_id;
                }
            }
        }

        $pelangganIds = [];
        foreach ($rules as $rule) {
            $ruleId = (int) ($rule->id ?? 0);
            $manualList = $manualPelangganByRule[$ruleId] ?? [];
            if (!empty($manualList)) {
                $pelangganIds = array_merge($pelangganIds, $manualList);
                continue;
            }

            $q = DB::table('pelanggans')->select('id');
            if ($status !== 'Semua') {
                $q->where('status_berlangganan', $status);
            }
            if ($rule->rule_type === 'per_area' && !empty($rule->coverage_area_id)) {
                $q->where('coverage_area', (int) $rule->coverage_area_id);
            }
            if ($rule->rule_type === 'per_package' && !empty($rule->package_id)) {
                $q->where('paket_layanan', (int) $rule->package_id);
            }
            $pelangganIds = array_merge($pelangganIds, $q->pluck('id')->all());
        }

        $pelangganIds = array_values(array_unique(array_map('intval', $pelangganIds)));
        if (empty($pelangganIds)) {
            return [];
        }

        $q = DB::table('pelanggans')->whereIn('id', $pelangganIds);
        if ($status !== 'Semua') {
            $q->where('status_berlangganan', $status);
        }

        return $q->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    public function login(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $login = trim($validated['login']);
        $user = User::query()
            ->where('email', $login)
            ->orWhere('name', $login)
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return apiResponse(false, 'Login atau password salah.', [], 401);
        }

        $tokenName = $validated['device_name'] ?? ('myrba-admin-' . Str::random(6));
        $token = $user->createToken($tokenName)->plainTextToken;

        return apiResponse(true, 'Login berhasil.', [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->buildUserPayload($user),
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $this->getPermissionNames($user),
            'menus' => $this->buildMenuByPermissions($this->getPermissionNames($user)),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $permissions = $this->getPermissionNames($user);

        return apiResponse(true, 'Profil admin berhasil diambil.', [
            'user' => $this->buildUserPayload($user),
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $permissions,
            'menus' => $this->buildMenuByPermissions($permissions),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        if (!$user->hasPermissionTo('admin profile edit')) {
            return apiResponse(false, 'Forbidden', [], 403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $updates = [];
        if (array_key_exists('name', $validated) && $validated['name'] !== null) {
            $updates['name'] = trim((string) $validated['name']);
        }
        if (array_key_exists('password', $validated) && $validated['password'] !== null && $validated['password'] !== '') {
            $updates['password'] = Hash::make((string) $validated['password']);
        }
        if (!empty($updates)) {
            $updates['updated_at'] = now();
            DB::table('users')->where('id', (int) $user->id)->update($updates);
        }

        $fresh = User::find((int) $user->id);
        return apiResponse(true, 'Profil berhasil diperbarui.', [
            'user' => $fresh ? $this->buildUserPayload($fresh) : $this->buildUserPayload($user),
        ]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        if (!$user->hasPermissionTo('admin profile edit')) {
            return apiResponse(false, 'Forbidden', [], 403);
        }

        $validated = $request->validate([
            'avatar' => 'required|image|max:3072',
        ]);

        $file = $validated['avatar'];
        $filename = $file->hashName();
        $path = 'public/uploads/avatars';
        Storage::putFileAs($path, $file, $filename);

        DB::table('users')->where('id', (int) $user->id)->update([
            'avatar' => $filename,
            'updated_at' => now(),
        ]);

        $fresh = User::find((int) $user->id);
        return apiResponse(true, 'Foto profil berhasil diperbarui.', [
            'user' => $fresh ? $this->buildUserPayload($fresh) : $this->buildUserPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return apiResponse(true, 'Logout berhasil.');
    }

    public function menus(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $permissions = $this->getPermissionNames($user);
        return apiResponse(true, 'Menu berhasil diambil.', [
            'menus' => $this->buildMenuByPermissions($permissions),
        ]);
    }

    public function menuTree(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $permissions = $this->getPermissionNames($user);
        $includeLocked = filter_var($request->query('include_locked', '1'), FILTER_VALIDATE_BOOL);

        return apiResponse(true, 'Menu tree berhasil diambil.', [
            'menus' => $this->buildMenuTree($permissions, $includeLocked),
        ]);
    }

    public function badges(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $badges = [];
        $allowedAreas = $this->allowedAreaCoverageIds();

        if ($user->hasPermissionTo('pelanggan view')) {
            $badges['request_pelanggan'] = (int) DB::table('pelanggans')
                ->where('status_berlangganan', 'Menunggu')
                ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                    $q->whereIn('coverage_area', $allowedAreas);
                })
                ->when(empty($allowedAreas), function ($q) {
                    $q->whereRaw('1 = 0');
                })
                ->count();
        }

        if ($user->hasPermissionTo('tiket aduan view')) {
            $badges['daftar_tiket'] = (int) DB::table('tiket_aduans')
                ->join('pelanggans', 'tiket_aduans.pelanggan_id', '=', 'pelanggans.id')
                ->where('tiket_aduans.status', 'Menunggu')
                ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                    $q->whereIn('pelanggans.coverage_area', $allowedAreas);
                })
                ->when(empty($allowedAreas), function ($q) {
                    $q->whereRaw('1 = 0');
                })
                ->count();
        }

        if ($user->hasPermissionTo('tagihan view')) {
            $badges['daftar_tagihan'] = (int) DB::table('tagihans')
                ->join('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
                ->where('tagihans.status_bayar', 'Waiting Review')
                ->when(!empty($allowedAreas), function ($q) use ($allowedAreas) {
                    $q->whereIn('pelanggans.coverage_area', $allowedAreas);
                })
                ->when(empty($allowedAreas), function ($q) {
                    $q->whereRaw('1 = 0');
                })
                ->count();
        }

        return apiResponse(true, 'Badge berhasil diambil.', [
            'badges' => $badges,
        ]);
    }

    public function storeFcmToken(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $validated = $request->validate([
            'fcm_token' => 'required|string|max:4096',
            'platform' => 'nullable|string|max:20',
        ]);

        $token = trim((string) $validated['fcm_token']);
        $platform = trim((string) ($validated['platform'] ?? 'android'));
        $platform = $platform !== '' ? $platform : 'android';

        DB::transaction(function () use ($user, $token, $platform) {
            $existing = DB::table('admin_fcm_tokens')->where('token', $token)->lockForUpdate()->first();
            if ($existing) {
                DB::table('admin_fcm_tokens')->where('id', (int) $existing->id)->update([
                    'user_id' => (int) $user->id,
                    'platform' => $platform,
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                ]);
                return;
            }
            DB::table('admin_fcm_tokens')->insert([
                'user_id' => (int) $user->id,
                'token' => $token,
                'platform' => $platform,
                'last_seen_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return apiResponse(true, 'FCM token admin berhasil disimpan.');
    }

    public static function notifyAdminsByPermission(string $permission, string $title, string $body, array $data = []): void
    {
        try {
            $direct = DB::table('admin_fcm_tokens as t')
                ->join('model_has_permissions as mhp', function ($join) {
                    $join->on('t.user_id', '=', 'mhp.model_id')
                        ->where('mhp.model_type', '=', 'App\\Models\\User');
                })
                ->join('permissions as p', 'mhp.permission_id', '=', 'p.id')
                ->where('p.name', $permission)
                ->select('t.token');

            $viaRole = DB::table('admin_fcm_tokens as t')
                ->join('model_has_roles as mhr', function ($join) {
                    $join->on('t.user_id', '=', 'mhr.model_id')
                        ->where('mhr.model_type', '=', 'App\\Models\\User');
                })
                ->join('role_has_permissions as rhp', 'mhr.role_id', '=', 'rhp.role_id')
                ->join('permissions as p', 'rhp.permission_id', '=', 'p.id')
                ->where('p.name', $permission)
                ->select('t.token');

            $tokens = $direct->union($viaRole)->distinct()->get();

            if ($tokens->isEmpty()) {
                return;
            }

            $client = new FcmClient();
            foreach ($tokens as $row) {
                $token = trim((string) ($row->token ?? ''));
                if ($token === '') {
                    continue;
                }
                $client->sendToToken($token, $title, $body, $data);
            }
        } catch (\Throwable $e) {
            Log::warning('FCM admin push gagal', ['error' => $e->getMessage()]);
        }
    }

    public function requestPelanggan(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'pelanggan view')) {
            return $deny;
        }

        $query = DB::table('pelanggans')
            ->where('status_berlangganan', 'Menunggu')
            ->when(true, function ($q) {
                $allowedAreas = $this->allowedAreaCoverageIds();
                if (!empty($allowedAreas)) {
                    $q->whereIn('coverage_area', $allowedAreas);
                    return;
                }
                $q->whereRaw('1 = 0');
            })
            ->orderByDesc('id')
            ->select('id', 'nama', 'no_layanan', 'no_wa', 'alamat', 'material_status', 'material_approved_at', 'created_at');

        return apiResponse(true, 'Request pelanggan berhasil diambil.', $this->paginate($query, $request));
    }

    public function pelanggans(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'pelanggan view')) {
            return $deny;
        }

        $status = trim((string) $request->query('status', ''));
        $status = $status !== '' ? $status : null;

        $query = DB::table('pelanggans')
            ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->when(true, function ($q) {
                $allowedAreas = $this->allowedAreaCoverageIds();
                if (!empty($allowedAreas)) {
                    $q->whereIn('pelanggans.coverage_area', $allowedAreas);
                    return;
                }
                $q->whereRaw('1 = 0');
            })
            ->when(!empty($status), function ($q) use ($status) {
                $q->where('pelanggans.status_berlangganan', $status);
            })
            ->select(
                'pelanggans.id',
                'pelanggans.nama',
                'pelanggans.no_layanan',
                'pelanggans.no_wa',
                'pelanggans.status_berlangganan',
                'pelanggans.balance',
                'packages.nama_layanan',
                'area_coverages.nama as area_nama'
            )
            ->orderByDesc('pelanggans.id');

        return apiResponse(true, 'Data pelanggan berhasil diambil.', $this->paginate($query, $request));
    }

    public function pelangganUpdateStatus(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'pelanggan edit')) {
            return $deny;
        }

        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $validated = $request->validate([
            'status_berlangganan' => 'required|in:Aktif,Non Aktif',
        ]);

        $allowedAreas = $this->allowedAreaCoverageIds();
        $pelanggan = DB::table('pelanggans')->where('id', (int) $id)->first();
        if (!$pelanggan) {
            return apiResponse(false, 'Pelanggan tidak ditemukan.', [], 404);
        }
        if (empty($allowedAreas) || !in_array((int) ($pelanggan->coverage_area ?? 0), $allowedAreas, true)) {
            return apiResponse(false, 'Forbidden', [], 403);
        }

        DB::table('pelanggans')->where('id', (int) $id)->update([
            'status_berlangganan' => (string) $validated['status_berlangganan'],
            'updated_at' => now(),
        ]);

        return apiResponse(true, 'Status pelanggan berhasil diupdate.', [
            'pelanggan_id' => (int) $id,
            'status_berlangganan' => (string) $validated['status_berlangganan'],
        ]);
    }

    public function pelangganApproveMaterials(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'pelanggan edit')) {
            return $deny;
        }

        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $allowedAreas = $this->allowedAreaCoverageIds();
        $pelanggan = DB::table('pelanggans')->where('id', (int) $id)->first();
        if (!$pelanggan) {
            return apiResponse(false, 'Pelanggan tidak ditemukan.', [], 404);
        }
        if (empty($allowedAreas) || !in_array((int) ($pelanggan->coverage_area ?? 0), $allowedAreas, true)) {
            return apiResponse(false, 'Forbidden', [], 403);
        }
        if ((string) ($pelanggan->status_berlangganan ?? '') !== 'Menunggu') {
            return apiResponse(false, 'Pelanggan bukan status Menunggu.', [], 400);
        }

        $materialsCount = DB::table('pelanggan_request_materials')->where('pelanggan_id', (int) $id)->count();
        if ($materialsCount < 1) {
            return apiResponse(false, 'Material belum diisi, tidak dapat divalidasi gudang.', [], 400);
        }

        DB::table('pelanggans')->where('id', (int) $id)->update([
            'material_status' => 'Approved',
            'material_approved_by' => (int) $user->id,
            'material_approved_at' => now(),
            'updated_at' => now(),
        ]);

        return apiResponse(true, 'Material telah divalidasi tim gudang.', [
            'pelanggan_id' => (int) $id,
        ]);
    }

    public function pelangganApproveInstall(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'pelanggan edit')) {
            return $deny;
        }

        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $allowedAreas = $this->allowedAreaCoverageIds();
        $pelanggan = DB::table('pelanggans')->where('id', (int) $id)->first();
        if (!$pelanggan) {
            return apiResponse(false, 'Pelanggan tidak ditemukan.', [], 404);
        }
        if (empty($allowedAreas) || !in_array((int) ($pelanggan->coverage_area ?? 0), $allowedAreas, true)) {
            return apiResponse(false, 'Forbidden', [], 403);
        }
        if ((string) ($pelanggan->status_berlangganan ?? '') !== 'Menunggu') {
            return apiResponse(false, 'Pelanggan bukan status Menunggu.', [], 400);
        }
        if (isset($pelanggan->material_status) && (string) ($pelanggan->material_status ?? 'Pending') !== 'Approved') {
            return apiResponse(false, 'Validasi gudang belum selesai.', [], 400);
        }

        DB::table('pelanggans')->where('id', (int) $id)->update([
            'status_berlangganan' => 'Aktif',
            'updated_at' => now(),
        ]);

        return apiResponse(true, 'Pelanggan berhasil divalidasi pemasangan.', [
            'pelanggan_id' => (int) $id,
        ]);
    }

    public function tiketAduans(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'tiket aduan view')) {
            return $deny;
        }

        $query = DB::table('tiket_aduans')
            ->leftJoin('pelanggans', 'tiket_aduans.pelanggan_id', '=', 'pelanggans.id')
            ->when(true, function ($q) {
                $allowedAreas = $this->allowedAreaCoverageIds();
                if (!empty($allowedAreas)) {
                    $q->whereIn('pelanggans.coverage_area', $allowedAreas);
                    return;
                }
                $q->whereRaw('1 = 0');
            })
            ->select(
                'tiket_aduans.id',
                'tiket_aduans.nomor_tiket',
                'tiket_aduans.tanggal_aduan',
                'tiket_aduans.status',
                'tiket_aduans.prioritas',
                'tiket_aduans.deskripsi_aduan',
                'pelanggans.nama as nama_pelanggan',
                'pelanggans.no_layanan'
            )
            ->orderByDesc('tiket_aduans.id');

        return apiResponse(true, 'Data tiket aduan berhasil diambil.', $this->paginate($query, $request));
    }

    public function informasiManagement(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'informasi management view')) {
            return $deny;
        }

        $query = DB::table('informasi_management')
            ->select('id', 'judul', 'deskripsi', 'thumbnail', 'is_aktif', 'updated_at')
            ->orderByDesc('id');

        return apiResponse(true, 'Data informasi berhasil diambil.', $this->paginate($query, $request));
    }

    public function tagihans(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'tagihan view')) {
            return $deny;
        }

        $period = trim((string) $request->query('period', ''));
        $period = $period !== '' ? $period : null;

        $query = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->when(true, function ($q) {
                $allowedAreas = $this->allowedAreaCoverageIds();
                if (!empty($allowedAreas)) {
                    $q->whereIn('pelanggans.coverage_area', $allowedAreas);
                    return;
                }
                $q->whereRaw('1 = 0');
            })
            ->when(!empty($period), function ($q) use ($period) {
                $q->where('tagihans.periode', $period);
            })
            ->select(
                'tagihans.id',
                'tagihans.no_tagihan',
                'tagihans.total_bayar',
                'tagihans.periode',
                'tagihans.status_bayar',
                'tagihans.metode_bayar',
                'tagihans.tanggal_bayar',
                'tagihans.printed_at',
                'pelanggans.nama as nama_pelanggan',
                'pelanggans.no_layanan'
            )
            ->orderByDesc('tagihans.id');

        return apiResponse(true, 'Data tagihan berhasil diambil.', $this->paginate($query, $request));
    }

    public function tagihanBayar(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'tagihan edit')) {
            return $deny;
        }

        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $validated = $request->validate([
            'metode_bayar' => 'required|in:Cash,Transfer Bank,Payment Tripay,Saldo',
            'bank_account_id' => 'nullable|integer|min:1',
        ]);

        $allowedAreas = $this->allowedAreaCoverageIds();
        $tagihan = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->where('tagihans.id', (int) $id)
            ->select('tagihans.*', 'pelanggans.coverage_area', 'pelanggans.nama as pelanggan_nama', 'pelanggans.no_layanan')
            ->first();
        if (!$tagihan) {
            return apiResponse(false, 'Data tagihan tidak ditemukan.', [], 404);
        }
        if (empty($allowedAreas) || !in_array((int) ($tagihan->coverage_area ?? 0), $allowedAreas, true)) {
            return apiResponse(false, 'Forbidden', [], 403);
        }

        $status = (string) ($tagihan->status_bayar ?? '');
        if ($status === 'Sudah Bayar') {
            return apiResponse(false, 'Tagihan sudah dibayar.', [], 400);
        }

        $tgl = now();
        $payload = [
            'tanggal_bayar' => $tgl,
            'metode_bayar' => (string) $validated['metode_bayar'],
            'status_bayar' => 'Waiting Review',
            'tanggal_kirim_notif_wa' => $tgl,
            'created_by' => (int) $user->id,
            'updated_at' => $tgl,
        ];
        if ($payload['metode_bayar'] === 'Transfer Bank') {
            $payload['bank_account_id'] = !empty($validated['bank_account_id']) ? (int) $validated['bank_account_id'] : null;
        }

        DB::table('tagihans')->where('id', (int) $id)->update($payload);

        self::notifyAdminsByPermission(
            'tagihan view',
            'Tagihan menunggu review',
            'Tagihan ' . ($tagihan->no_tagihan ?? '-') . ' a/n ' . ($tagihan->pelanggan_nama ?? '-') . ' (' . ($tagihan->no_layanan ?? '-') . ')',
            [
                'type' => 'tagihan',
                'badge_key' => 'daftar_tagihan',
                'tagihan_id' => (string) (int) $id,
            ]
        );

        return apiResponse(true, 'Pembayaran tagihan berhasil diupdate (Waiting Review).', [
            'tagihan_id' => (int) $id,
        ]);
    }

    public function tagihanValidasi(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'tagihan validasi')) {
            return $deny;
        }

        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        DB::beginTransaction();
        try {
            $tgl = now();
            $tagihan = DB::table('tagihans')->where('id', (int) $id)->lockForUpdate()->first();
            if (!$tagihan) {
                DB::rollBack();
                return apiResponse(false, 'Data tagihan tidak ditemukan.', [], 404);
            }

            if (strtolower(trim((string) ($tagihan->status_bayar ?? ''))) === 'sudah bayar') {
                DB::rollBack();
                return apiResponse(false, 'Tagihan sudah berstatus dibayar.', [], 400);
            }

            DB::table('tagihans')->where('id', (int) $id)->update([
                'status_bayar' => 'Sudah Bayar',
                'tanggal_review' => $tgl,
                'reviewed_by' => (int) $user->id,
                'updated_at' => $tgl,
            ]);

            $categoryId = getInternetIncomeCategoryIdForPelanggan((int) ($tagihan->pelanggan_id ?? 0));
            DB::table('pemasukans')->insert([
                'nominal' => (float) ($tagihan->total_bayar ?? 0),
                'tanggal' => $tgl,
                'category_pemasukan_id' => $categoryId,
                'keterangan' => 'Pembayaran Tagihan no Tagihan ' . ($tagihan->no_tagihan ?? '-') . ' a/n ' . (string) ($tagihan->pelanggan_id ?? '-') . ' Periode ' . (string) ($tagihan->periode ?? '-'),
                'referense_id' => (int) $id,
                'metode_bayar' => (string) ($tagihan->metode_bayar ?? '-'),
                'created_at' => $tgl,
                'updated_at' => $tgl,
            ]);
            applyInvestorSharingForPaidTagihan((int) $id);

            DB::commit();
            return apiResponse(true, 'Tagihan berhasil divalidasi.', [
                'tagihan_id' => (int) $id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return apiResponse(false, 'Gagal validasi tagihan.', ['error' => $e->getMessage()], 500);
        }
    }

    public function tagihanPrinted(Request $request, int $id): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $can = $user->hasPermissionTo('tagihan view') || $user->hasPermissionTo('investor view');
        if (!$can) {
            return apiResponse(false, 'Forbidden', ['required_permission' => 'tagihan view|investor view'], 403);
        }

        $tagihan = DB::table('tagihans')->where('id', (int) $id)->first();
        if (!$tagihan) {
            return apiResponse(false, 'Data tagihan tidak ditemukan.', [], 404);
        }

        if ($this->isInvestorUser($user)) {
            $pelangganId = (int) ($tagihan->pelanggan_id ?? 0);
            $allowed = $this->investorResolvePelangganIds((int) $user->id, 'Semua');
            if (empty($pelangganId) || empty($allowed) || !in_array($pelangganId, $allowed, true)) {
                return apiResponse(false, 'Forbidden', [], 403);
            }
        }

        DB::table('tagihans')->where('id', (int) $id)->update([
            'printed_at' => now(),
            'printed_by' => (int) $user->id,
            'updated_at' => now(),
        ]);

        return apiResponse(true, 'Tagihan ditandai sudah dicetak.', [
            'tagihan_id' => (int) $id,
        ]);
    }

    public function odcs(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'odc view')) {
            return $deny;
        }

        $query = DB::table('odcs')
            ->leftJoin('area_coverages', 'odcs.wilayah_odc', '=', 'area_coverages.id')
            ->when(true, function ($q) {
                $allowedAreas = $this->allowedAreaCoverageIds();
                if (!empty($allowedAreas)) {
                    $q->whereIn('odcs.wilayah_odc', $allowedAreas);
                    return;
                }
                $q->whereRaw('1 = 0');
            })
            ->select(
                'odcs.id',
                'odcs.kode_odc',
                'odcs.nomor_port_olt',
                'odcs.warna_tube_fo',
                'odcs.nomor_tiang',
                'odcs.latitude',
                'odcs.longitude',
                'area_coverages.nama as area_nama'
            )
            ->orderByDesc('odcs.id');

        return apiResponse(true, 'Data ODC berhasil diambil.', $this->paginate($query, $request));
    }

    public function odps(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'odp view')) {
            return $deny;
        }

        $query = DB::table('odps')
            ->leftJoin('odcs', 'odps.kode_odc', '=', 'odcs.id')
            ->leftJoin('area_coverages', 'odps.wilayah_odp', '=', 'area_coverages.id')
            ->when(true, function ($q) {
                $allowedAreas = $this->allowedAreaCoverageIds();
                if (!empty($allowedAreas)) {
                    $q->whereIn('odps.wilayah_odp', $allowedAreas);
                    return;
                }
                $q->whereRaw('1 = 0');
            })
            ->select(
                'odps.id',
                'odps.kode_odp',
                'odps.nomor_port_odc',
                'odps.jumlah_port',
                'odps.warna_tube_fo',
                'odps.nomor_tiang',
                'odps.latitude',
                'odps.longitude',
                'odcs.kode_odc',
                'area_coverages.nama as area_nama'
            )
            ->orderByDesc('odps.id');

        return apiResponse(true, 'Data ODP berhasil diambil.', $this->paginate($query, $request));
    }

    public function pppActive(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'active ppp view')) {
            return $deny;
        }

        $routerId = (int) $request->query('router_id', 0);
        $query = DB::table('active_ppps')
            ->leftJoin('settingmikrotiks', 'active_ppps.router_id', '=', 'settingmikrotiks.id')
            ->when($routerId > 0, function ($q) use ($routerId) {
                $q->where('active_ppps.router_id', $routerId);
            })
            ->select('active_ppps.id', 'settingmikrotiks.identitas_router', 'active_ppps.name', 'active_ppps.service', 'active_ppps.caller_id', 'active_ppps.ip_address', 'active_ppps.uptime', 'active_ppps.komentar', 'active_ppps.updated_at')
            ->orderByDesc('id');

        return apiResponse(true, 'Data PPP aktif berhasil diambil.', $this->paginate($query, $request));
    }

    public function pppNonActive(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'non active ppp view')) {
            return $deny;
        }

        $routerId = (int) $request->query('router_id', 0);
        $query = DB::table('secret_ppps')
            ->leftJoin('settingmikrotiks', 'secret_ppps.router_id', '=', 'settingmikrotiks.id')
            ->when($routerId > 0, function ($q) use ($routerId) {
                $q->where('secret_ppps.router_id', $routerId);
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('active_ppps')
                    ->whereColumn('active_ppps.router_id', 'secret_ppps.router_id')
                    ->whereColumn('active_ppps.name', 'secret_ppps.username');
            })
            ->select('secret_ppps.id', 'settingmikrotiks.identitas_router', 'secret_ppps.username', 'secret_ppps.service', 'secret_ppps.profile', 'secret_ppps.last_logout', 'secret_ppps.komentar', 'secret_ppps.updated_at')
            ->orderByDesc('id');

        return apiResponse(true, 'Data PPP non aktif berhasil diambil.', $this->paginate($query, $request));
    }

    public function pppProfiles(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'profile pppoe view')) {
            return $deny;
        }

        $routerId = (int) $request->query('router_id', 0);
        $query = DB::table('profile_pppoes')
            ->leftJoin('settingmikrotiks', 'profile_pppoes.router_id', '=', 'settingmikrotiks.id')
            ->when($routerId > 0, function ($q) use ($routerId) {
                $q->where('profile_pppoes.router_id', $routerId);
            })
            ->select('profile_pppoes.id', 'settingmikrotiks.identitas_router', 'profile_pppoes.name', 'profile_pppoes.local', 'profile_pppoes.remote', 'profile_pppoes.limit', 'profile_pppoes.parent', 'profile_pppoes.updated_at')
            ->orderBy('name');

        return apiResponse(true, 'Data profile PPP berhasil diambil.', $this->paginate($query, $request));
    }

    public function pppSecrets(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'secret ppp view')) {
            return $deny;
        }

        $routerId = (int) $request->query('router_id', 0);
        $query = DB::table('secret_ppps')
            ->leftJoin('settingmikrotiks', 'secret_ppps.router_id', '=', 'settingmikrotiks.id')
            ->when($routerId > 0, function ($q) use ($routerId) {
                $q->where('secret_ppps.router_id', $routerId);
            })
            ->select('secret_ppps.id', 'settingmikrotiks.identitas_router', 'secret_ppps.username', 'secret_ppps.service', 'secret_ppps.profile', 'secret_ppps.status', 'secret_ppps.last_logout', 'secret_ppps.komentar', 'secret_ppps.updated_at')
            ->orderByDesc('id');

        return apiResponse(true, 'Data secret PPP berhasil diambil.', $this->paginate($query, $request));
    }


    public function barangs(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'barang view')) {
            return $deny;
        }

        $query = DB::table('barang')
            ->leftJoin('unit_satuan', 'barang.unit_satuan_id', '=', 'unit_satuan.id')
            ->leftJoin('kategori_barang', 'barang.kategori_barang_id', '=', 'kategori_barang.id')
            ->select(
                'barang.id',
                'barang.kode_barang',
                'barang.nama_barang',
                'barang.stock',
                'unit_satuan.nama_unit_satuan',
                'kategori_barang.nama_kategori_barang',
                DB::raw("(SELECT COALESCE(SUM(bos.qty), 0) FROM barang_owner_stocks bos WHERE bos.barang_id = barang.id AND bos.owner_type = 'investor') as stock_investor"),
                DB::raw("(barang.stock + (SELECT COALESCE(SUM(bos2.qty), 0) FROM barang_owner_stocks bos2 WHERE bos2.barang_id = barang.id AND bos2.owner_type = 'investor')) as stock_total")
            )
            ->orderBy('barang.nama_barang');

        return apiResponse(true, 'Data barang berhasil diambil.', $this->paginate($query, $request));
    }

    public function kategoriBarangs(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'kategori barang view')) {
            return $deny;
        }

        $query = DB::table('kategori_barang')
            ->select('id', 'nama_kategori_barang', 'created_at', 'updated_at')
            ->orderBy('nama_kategori_barang');

        return apiResponse(true, 'Data kategori barang berhasil diambil.', $this->paginate($query, $request));
    }

    public function transaksiStockIn(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'transaksi stock in view')) {
            return $deny;
        }

        $query = DB::table('transaksi as t')
            ->leftJoin('users as u', 't.user_id', '=', 'u.id')
            ->where('t.jenis_transaksi', 'in')
            ->select(
                't.id',
                't.kode_transaksi',
                't.tanggal_transaksi',
                't.keterangan',
                'u.name as dibuat_oleh',
                't.created_at'
            )
            ->orderByDesc('t.id');

        return apiResponse(true, 'Data transaksi stock masuk berhasil diambil.', $this->paginate($query, $request));
    }

    public function transaksiStockOut(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'transaksi stock out view')) {
            return $deny;
        }

        $query = DB::table('transaksi as t')
            ->leftJoin('users as u', 't.user_id', '=', 'u.id')
            ->where('t.jenis_transaksi', 'out')
            ->select(
                't.id',
                't.kode_transaksi',
                't.tanggal_transaksi',
                't.keterangan',
                'u.name as dibuat_oleh',
                't.created_at'
            )
            ->orderByDesc('t.id');

        return apiResponse(true, 'Data transaksi stock keluar berhasil diambil.', $this->paginate($query, $request));
    }

    public function pemasukans(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'pemasukan view')) {
            return $deny;
        }

        $query = DB::table('pemasukans as p')
            ->leftJoin('category_pemasukans as c', 'p.category_pemasukan_id', '=', 'c.id')
            ->select(
                'p.id',
                'p.nominal',
                'p.tanggal',
                'p.keterangan',
                'c.nama_kategori_pemasukan as kategori',
                'p.referense_id',
                'p.created_at'
            )
            ->orderByDesc('p.id');

        return apiResponse(true, 'Data pemasukan berhasil diambil.', $this->paginate($query, $request));
    }

    public function pengeluarans(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'pengeluaran view')) {
            return $deny;
        }

        $query = DB::table('pengeluarans as p')
            ->leftJoin('category_pengeluarans as c', 'p.category_pengeluaran_id', '=', 'c.id')
            ->select(
                'p.id',
                'p.nominal',
                'p.tanggal',
                'p.keterangan',
                'c.nama_kategori_pengeluaran as kategori',
                'p.created_at'
            )
            ->orderByDesc('p.id');

        return apiResponse(true, 'Data pengeluaran berhasil diambil.', $this->paginate($query, $request));
    }

    public function banks(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'bank view')) {
            return $deny;
        }

        $query = DB::table('banks')
            ->select('id', 'nama_bank', 'logo_bank', 'created_at', 'updated_at')
            ->orderBy('nama_bank');

        return apiResponse(true, 'Data bank berhasil diambil.', $this->paginate($query, $request));
    }

    public function bankAccounts(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'bank account view')) {
            return $deny;
        }

        $query = DB::table('bank_accounts as ba')
            ->leftJoin('banks as b', 'ba.bank_id', '=', 'b.id')
            ->select(
                'ba.id',
                'b.nama_bank',
                'b.logo_bank',
                'ba.pemilik_rekening',
                'ba.nomor_rekening',
                'ba.created_at',
                'ba.updated_at'
            )
            ->orderByDesc('ba.id');

        return apiResponse(true, 'Data rekening bank berhasil diambil.', $this->paginate($query, $request));
    }

    public function packageCategories(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'package category view')) {
            return $deny;
        }

        $query = DB::table('package_categories')
            ->select('id', 'nama_kategori', 'keterangan', 'created_at', 'updated_at')
            ->orderBy('nama_kategori');

        return apiResponse(true, 'Data kategori paket berhasil diambil.', $this->paginate($query, $request));
    }

    public function packages(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'package view')) {
            return $deny;
        }

        $query = DB::table('packages as p')
            ->leftJoin('package_categories as pc', 'p.kategori_paket_id', '=', 'pc.id')
            ->select(
                'p.id',
                'p.nama_layanan',
                'p.harga',
                'p.referral_bonus',
                'p.profile',
                'p.is_active',
                'pc.nama_kategori as kategori',
                'p.created_at',
                'p.updated_at'
            )
            ->orderBy('p.nama_layanan');

        return apiResponse(true, 'Data paket berhasil diambil.', $this->paginate($query, $request));
    }

    public function areaCoverages(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'area coverage view')) {
            return $deny;
        }

        $query = DB::table('area_coverages')
            ->select('id', 'kode_area', 'nama', 'tampilkan_register', 'radius', 'alamat', 'keterangan', 'latitude', 'longitude', 'created_at', 'updated_at')
            ->orderBy('kode_area');

        return apiResponse(true, 'Data area coverage berhasil diambil.', $this->paginate($query, $request));
    }

    public function settingMikrotiks(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'settingmikrotik view')) {
            return $deny;
        }

        $query = DB::table('settingmikrotiks')
            ->select('id', 'identitas_router', 'host', 'port', 'username', 'created_at', 'updated_at')
            ->orderBy('identitas_router');

        return apiResponse(true, 'Data setting mikrotik berhasil diambil.', $this->paginate($query, $request));
    }

    public function olts(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'olt view')) {
            return $deny;
        }

        $query = DB::table('olts')
            ->select('id', 'name', 'type', 'host', 'telnet_port', 'snmp_port', 'created_at', 'updated_at')
            ->orderBy('name');

        return apiResponse(true, 'Data OLT berhasil diambil.', $this->paginate($query, $request));
    }

    public function topups(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'topup view')) {
            return $deny;
        }

        $query = DB::table('topups as t')
            ->leftJoin('pelanggans as p', 't.pelanggan_id', '=', 'p.id')
            ->leftJoin('users as u', 't.reviewed_by', '=', 'u.id')
            ->leftJoin('bank_accounts as ba', 't.bank_account_id', '=', 'ba.id')
            ->leftJoin('banks as b', 'ba.bank_id', '=', 'b.id')
            ->select(
                't.id',
                't.no_topup',
                't.tanggal_topup',
                't.nominal',
                't.status',
                't.metode',
                't.metode_topup',
                'p.nama as nama_pelanggan',
                'p.no_layanan',
                'u.name as reviewed_by_name',
                't.tanggal_review',
                'b.nama_bank',
                'ba.nomor_rekening',
                't.created_at'
            )
            ->orderByDesc('t.id');

        return apiResponse(true, 'Data topup berhasil diambil.', $this->paginate($query, $request));
    }

    public function withdraws(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'withdraw view')) {
            return $deny;
        }

        $query = DB::table('withdraws as w')
            ->leftJoin('pelanggans as p', 'w.pelanggan_id', '=', 'p.id')
            ->leftJoin('users as u', 'w.user_approved', '=', 'u.id')
            ->select(
                'w.id',
                'w.nominal_wd',
                'w.status',
                'w.tanggal_wd',
                'w.catatan_user_approved',
                'p.nama as nama_pelanggan',
                'p.no_layanan',
                'u.name as approved_by_name',
                'w.created_at'
            )
            ->orderByDesc('w.id');

        return apiResponse(true, 'Data withdraw berhasil diambil.', $this->paginate($query, $request));
    }

    public function investors(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'investor view')) {
            return $deny;
        }

        $query = DB::table('users')
            ->leftJoin('investor_share_rules as r', function ($join) {
                $join->on('users.id', '=', 'r.user_id')
                    ->where('r.is_aktif', '=', 'Yes');
            })
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where(function ($q) {
                $q->whereNotNull('r.id')
                    ->orWhereRaw('LOWER(roles.name) like ?', ['%investor%'])
                    ->orWhereRaw('LOWER(roles.name) like ?', ['%mitra%']);
            })
            ->select('users.id', 'users.name', 'users.email', 'users.no_wa', DB::raw('COUNT(DISTINCT r.id) as active_rule_count'))
            ->groupBy('users.id', 'users.name', 'users.email', 'users.no_wa')
            ->orderBy('users.name');

        return apiResponse(true, 'Data investor/mitra berhasil diambil.', $this->paginate($query, $request));
    }

    public function investorPayoutRequests(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $canApprove = $user->hasPermissionTo('investor payout approve');
        $canRequest = $user->hasPermissionTo('investor payout request');
        if (!$canApprove && !$canRequest) {
            return apiResponse(false, 'Forbidden', ['required_permission' => 'investor payout approve|investor payout request'], 403);
        }

        $query = DB::table('investor_payout_requests as pr')
            ->leftJoin('users as u', 'pr.user_id', '=', 'u.id')
            ->leftJoin('users as au', 'pr.approved_by', '=', 'au.id')
            ->when(!$canApprove, function ($q) use ($user) {
                $q->where('pr.user_id', (int) $user->id);
            })
            ->select(
                'pr.id',
                'pr.amount',
                'pr.status',
                'pr.requested_at',
                'pr.approved_at',
                'pr.payout_provider',
                'pr.payout_account_name',
                'pr.payout_account_number',
                'u.name as investor_name',
                'u.email as investor_email',
                'au.name as approved_by_name',
                'pr.pengeluaran_id',
                'pr.created_at'
            )
            ->orderByDesc('pr.id');

        return apiResponse(true, 'Data payout request investor berhasil diambil.', $this->paginate($query, $request));
    }

    public function investorDashboard(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'investor view')) {
            return $deny;
        }

        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        if ($this->isInvestorUser($user)) {
            $userId = (int) $user->id;
            $requestedPeriod = trim((string) $request->query('period', now()->format('Y-m')));
            $statusFilter = trim((string) $request->query('status', 'Aktif'));

            $wallet = DB::table('investor_wallets')->where('user_id', $userId)->first();
            $balance = (float) ($wallet->balance ?? 0);

            $rules = $this->investorActiveRules($userId);
            $startPeriods = $rules->pluck('start_period')->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '');
            $minStartPeriod = $startPeriods->isEmpty() ? null : (string) $startPeriods->min();

            $period = $requestedPeriod;
            if ($minStartPeriod !== null && $period !== '' && strcmp($period, $minStartPeriod) < 0) {
                $period = $minStartPeriod;
            }
            $periodOptions = $this->investorPeriodOptions($minStartPeriod);

            $pelangganIds = $this->investorResolvePelangganIds($userId, $statusFilter);

            $customers = [];
            $summary = ['total' => 0, 'paid' => 0, 'unpaid' => 0, 'no_invoice' => 0, 'not_printed' => 0, 'printed' => 0];
            if (!empty($pelangganIds)) {
                $pelanggans = DB::table('pelanggans')
                    ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
                    ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
                    ->select(
                        'pelanggans.id',
                        'pelanggans.nama',
                        'pelanggans.no_layanan',
                        'pelanggans.status_berlangganan',
                        'area_coverages.nama as area_nama',
                        'packages.nama_layanan as paket_nama'
                    )
                    ->whereIn('pelanggans.id', $pelangganIds)
                    ->orderBy('pelanggans.no_layanan')
                    ->get()
                    ->keyBy('id');

                $tagihans = DB::table('tagihans')
                    ->select('id', 'pelanggan_id', 'no_tagihan', 'periode', 'status_bayar', 'tanggal_bayar', 'total_bayar', 'printed_at')
                    ->whereIn('pelanggan_id', $pelangganIds)
                    ->where('periode', $period)
                    ->get()
                    ->keyBy('pelanggan_id');

                $paidStatuses = ['sudah bayar', 'paid', 'lunas'];
                foreach ($pelangganIds as $pid) {
                    $p = $pelanggans[(int) $pid] ?? null;
                    if (!$p) continue;
                    $t = $tagihans[(int) $pid] ?? null;
                    $statusBayar = strtolower(trim((string) ($t->status_bayar ?? '')));
                    $isPaid = $t ? in_array($statusBayar, $paidStatuses, true) : false;
                    $summary['total']++;
                    if ($isPaid) $summary['paid']++; else $summary['unpaid']++;
                    if (!$t) {
                        $summary['no_invoice']++;
                    } else {
                        if (empty($t->printed_at)) {
                            $summary['not_printed']++;
                        } else {
                            $summary['printed']++;
                        }
                    }

                    $customers[] = [
                        'pelanggan_id' => (int) $p->id,
                        'nama' => $p->nama,
                        'no_layanan' => $p->no_layanan,
                        'status_berlangganan' => $p->status_berlangganan,
                        'area_nama' => $p->area_nama,
                        'paket_nama' => $p->paket_nama,
                        'tagihan_id' => $t ? (int) $t->id : null,
                        'no_tagihan' => $t->no_tagihan ?? null,
                        'periode' => $t->periode ?? $period,
                        'status_bayar' => $t->status_bayar ?? null,
                        'tanggal_bayar' => $t->tanggal_bayar ?? null,
                        'total_bayar' => $t->total_bayar ?? null,
                        'printed_at' => $t->printed_at ?? null,
                        'print_status' => !$t ? 'NO_INVOICE' : (empty($t->printed_at) ? 'NOT_PRINTED' : 'PRINTED'),
                    ];
                }
            }

            return apiResponse(true, 'Dashboard investor berhasil diambil.', [
                'mode' => 'investor',
                'balance' => $balance,
                'summary' => $summary,
                'period' => $period,
                'period_options' => $periodOptions,
                'status_filter' => $statusFilter,
                'customers' => $customers,
            ]);
        }

        $query = DB::table('users as u')
            ->leftJoin('investor_wallets as w', 'u.id', '=', 'w.user_id')
            ->leftJoin('investor_share_rules as r', function ($join) {
                $join->on('u.id', '=', 'r.user_id')->where('r.is_aktif', '=', 'Yes');
            })
            ->leftJoin('investor_payout_requests as pr', function ($join) {
                $join->on('u.id', '=', 'pr.user_id')->where('pr.status', '=', 'Pending');
            })
            ->select(
                'u.id',
                'u.name',
                'u.email',
                DB::raw('COALESCE(w.balance, 0) as wallet_balance'),
                DB::raw('COUNT(DISTINCT r.id) as active_rules'),
                DB::raw('COUNT(DISTINCT pr.id) as pending_payout_count'),
                DB::raw('COALESCE(SUM(pr.amount), 0) as pending_payout_amount')
            )
            ->groupBy('u.id', 'u.name', 'u.email', 'w.balance')
            ->orderBy('u.name');

        return apiResponse(true, 'Dashboard investor berhasil diambil.', $this->paginate($query, $request));
    }

    public function investorAdminDashboard(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'investor rule manage')) {
            return $deny;
        }

        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $period = trim((string) $request->query('period', now()->format('Y-m')));
        $minStartPeriod = DB::table('investor_share_rules')
            ->whereNotNull('start_period')
            ->where('start_period', '<>', '')
            ->min('start_period');
        $minStartPeriod = $minStartPeriod ? trim((string) $minStartPeriod) : null;

        $periodOptions = $this->investorPeriodOptions($minStartPeriod);
        if (!in_array($period, $periodOptions, true)) {
            $period = $periodOptions[0] ?? now()->format('Y-m');
        }

        $userIds = array_values(array_unique(array_merge(
            DB::table('investor_share_rules')->pluck('user_id')->map(fn ($v) => (int) $v)->all(),
            DB::table('investor_wallets')->pluck('user_id')->map(fn ($v) => (int) $v)->all(),
            DB::table('investor_payout_requests')->pluck('user_id')->map(fn ($v) => (int) $v)->all(),
            DB::table('investor_earnings')->pluck('user_id')->map(fn ($v) => (int) $v)->all()
        )));

        $users = [];
        if (!empty($userIds)) {
            $users = DB::table('users')
                ->select('id', 'name', 'email')
                ->whereIn('id', $userIds)
                ->orderBy('name')
                ->get();
        }

        $wallets = DB::table('investor_wallets')->select('user_id', 'balance')->get()->keyBy('user_id');

        $rulesAgg = DB::table('investor_share_rules')
            ->select('user_id', DB::raw('COUNT(*) as rules_total'), DB::raw("SUM(CASE WHEN is_aktif='Yes' THEN 1 ELSE 0 END) as rules_active"))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $earnPeriod = DB::table('investor_earnings')
            ->select('user_id', DB::raw('COUNT(*) as earning_count'), DB::raw('SUM(amount) as earning_amount'))
            ->where('periode', $period)
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $earnTotal = DB::table('investor_earnings')
            ->select('user_id', DB::raw('COUNT(*) as earning_total_count'), DB::raw('SUM(amount) as earning_total_amount'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $payoutPending = DB::table('investor_payout_requests')
            ->select('user_id', DB::raw('COUNT(*) as pending_count'), DB::raw('SUM(amount) as pending_amount'))
            ->where('status', 'Pending')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $payoutApprovedPeriod = DB::table('investor_payout_requests')
            ->select('user_id', DB::raw('COUNT(*) as approved_count'), DB::raw('SUM(amount) as approved_amount'))
            ->where('status', 'Approved')
            ->whereNotNull('approved_at')
            ->whereRaw('LEFT(approved_at, 7) = ?', [$period])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $lastActivity = DB::table('investor_wallet_histories')
            ->select('user_id', DB::raw('MAX(created_at) as last_activity_at'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $rows = [];
        $summary = [
            'total_users' => 0,
            'total_balance' => 0.0,
            'earning_period_amount' => 0.0,
            'payout_pending_amount' => 0.0,
            'payout_approved_period_amount' => 0.0,
        ];

        foreach ($users as $u) {
            $uid = (int) $u->id;
            $balance = (float) (data_get($wallets, $uid . '.balance') ?? 0);
            $rulesTotal = (int) (data_get($rulesAgg, $uid . '.rules_total') ?? 0);
            $rulesActive = (int) (data_get($rulesAgg, $uid . '.rules_active') ?? 0);
            $earningCount = (int) (data_get($earnPeriod, $uid . '.earning_count') ?? 0);
            $earningAmount = (float) (data_get($earnPeriod, $uid . '.earning_amount') ?? 0);
            $earningTotalAmount = (float) (data_get($earnTotal, $uid . '.earning_total_amount') ?? 0);
            $pendingCount = (int) (data_get($payoutPending, $uid . '.pending_count') ?? 0);
            $pendingAmount = (float) (data_get($payoutPending, $uid . '.pending_amount') ?? 0);
            $approvedPeriodAmount = (float) (data_get($payoutApprovedPeriod, $uid . '.approved_amount') ?? 0);
            $last = (string) (data_get($lastActivity, $uid . '.last_activity_at') ?? '');

            $rows[] = [
                'id' => $uid,
                'name' => $u->name,
                'email' => $u->email,
                'balance' => $balance,
                'rules_total' => $rulesTotal,
                'rules_active' => $rulesActive,
                'earning_period_count' => $earningCount,
                'earning_period_amount' => $earningAmount,
                'earning_total_amount' => $earningTotalAmount,
                'payout_pending_count' => $pendingCount,
                'payout_pending_amount' => $pendingAmount,
                'payout_approved_period_amount' => $approvedPeriodAmount,
                'last_activity_at' => $last,
            ];

            $summary['total_users']++;
            $summary['total_balance'] += $balance;
            $summary['earning_period_amount'] += $earningAmount;
            $summary['payout_pending_amount'] += $pendingAmount;
            $summary['payout_approved_period_amount'] += $approvedPeriodAmount;
        }

        return apiResponse(true, 'Dashboard investor admin berhasil diambil.', [
            'period' => $period,
            'period_options' => $periodOptions,
            'min_start_period' => $minStartPeriod,
            'summary' => $summary,
            'rows' => $rows,
        ]);
    }

    public function investorPelanggans(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'investor view')) {
            return $deny;
        }

        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $status = trim((string) $request->query('status', 'Aktif'));
        $period = trim((string) $request->query('period', now()->format('Y-m')));
        $pelangganIds = $this->investorResolvePelangganIds((int) $user->id, $status);
        if (empty($pelangganIds)) {
            return apiResponse(true, 'Data pelanggan investor berhasil diambil.', [
                'total' => 0,
                'page' => 1,
                'limit' => (int) $request->query('limit', 20),
                'data' => [],
            ]);
        }

        $query = DB::table('pelanggans')
            ->leftJoin('area_coverages', 'pelanggans.coverage_area', '=', 'area_coverages.id')
            ->leftJoin('packages', 'pelanggans.paket_layanan', '=', 'packages.id')
            ->whereIn('pelanggans.id', $pelangganIds)
            ->select(
                'pelanggans.id',
                'pelanggans.nama',
                'pelanggans.no_layanan',
                'pelanggans.no_wa',
                'pelanggans.status_berlangganan',
                'packages.nama_layanan as paket_nama',
                'area_coverages.nama as area_nama'
            )
            ->orderBy('pelanggans.no_layanan');

        $page = $this->paginate($query, $request);
        $ids = collect($page['data'])->pluck('id')->map(fn ($v) => (int) $v)->all();
        $tagihans = [];
        if (!empty($ids)) {
            $tagihans = DB::table('tagihans')
                ->select('id', 'pelanggan_id', 'no_tagihan', 'periode', 'status_bayar', 'tanggal_bayar', 'total_bayar', 'printed_at')
                ->whereIn('pelanggan_id', $ids)
                ->where('periode', $period)
                ->get()
                ->keyBy('pelanggan_id');
        }

        $data = [];
        foreach ($page['data'] as $row) {
            $pid = (int) $row->id;
            $t = $tagihans[$pid] ?? null;
            $data[] = array_merge((array) $row, [
                'tagihan_id' => $t ? (int) $t->id : null,
                'no_tagihan' => $t->no_tagihan ?? null,
                'periode' => $t->periode ?? $period,
                'status_bayar' => $t->status_bayar ?? null,
                'tanggal_bayar' => $t->tanggal_bayar ?? null,
                'total_bayar' => $t->total_bayar ?? null,
                'printed_at' => $t->printed_at ?? null,
                'print_status' => !$t ? 'NO_INVOICE' : (empty($t->printed_at) ? 'NOT_PRINTED' : 'PRINTED'),
            ]);
        }

        return apiResponse(true, 'Data pelanggan investor berhasil diambil.', [
            'total' => $page['total'],
            'page' => $page['page'],
            'limit' => $page['limit'],
            'data' => $data,
        ]);
    }

    public function investorTagihans(Request $request): JsonResponse
    {
        if ($deny = $this->denyWithoutPermission($request, 'investor view')) {
            return $deny;
        }

        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $statusPelanggan = trim((string) $request->query('status_pelanggan', 'Aktif'));
        $period = trim((string) $request->query('period', now()->format('Y-m')));
        $statusBayar = trim((string) $request->query('status_bayar', ''));

        $pelangganIds = $this->investorResolvePelangganIds((int) $user->id, $statusPelanggan);
        if (empty($pelangganIds)) {
            return apiResponse(true, 'Data tagihan investor berhasil diambil.', [
                'total' => 0,
                'page' => 1,
                'limit' => (int) $request->query('limit', 20),
                'data' => [],
            ]);
        }

        $query = DB::table('tagihans')
            ->leftJoin('pelanggans', 'tagihans.pelanggan_id', '=', 'pelanggans.id')
            ->whereIn('tagihans.pelanggan_id', $pelangganIds)
            ->where('tagihans.periode', $period)
            ->when($statusBayar !== '', function ($q) use ($statusBayar) {
                $q->where('tagihans.status_bayar', $statusBayar);
            })
            ->select(
                'tagihans.id',
                'tagihans.no_tagihan',
                'tagihans.total_bayar',
                'tagihans.periode',
                'tagihans.status_bayar',
                'tagihans.metode_bayar',
                'tagihans.tanggal_bayar',
                'tagihans.printed_at',
                'pelanggans.nama as nama_pelanggan',
                'pelanggans.no_layanan',
                'pelanggans.status_berlangganan'
            )
            ->orderByDesc('tagihans.id');

        return apiResponse(true, 'Data tagihan investor berhasil diambil.', $this->paginate($query, $request));
    }

    public function investorPayoutAccounts(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $canApprove = $user->hasPermissionTo('investor payout approve');
        $canRequest = $user->hasPermissionTo('investor payout request');
        if (!$canApprove && !$canRequest) {
            return apiResponse(false, 'Forbidden', ['required_permission' => 'investor payout approve|investor payout request'], 403);
        }

        $query = DB::table('investor_payout_accounts as a')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
            ->when(!$canApprove, function ($q) use ($user) {
                $q->where('a.user_id', (int) $user->id);
            })
            ->select(
                'a.id',
                'u.name',
                'u.email',
                'a.type',
                'a.provider',
                'a.account_name',
                'a.account_number',
                'a.updated_at'
            )
            ->orderBy('u.name');

        return apiResponse(true, 'Data rekening/e-wallet investor berhasil diambil.', $this->paginate($query, $request));
    }

    public function investorPayoutAccountUpsert(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        $canApprove = $user->hasPermissionTo('investor payout approve');
        $canRequest = $user->hasPermissionTo('investor payout request');
        if (!$canApprove && !$canRequest) {
            return apiResponse(false, 'Forbidden', ['required_permission' => 'investor payout approve|investor payout request'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|string|max:20',
            'provider' => 'nullable|string|max:50',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:100',
            'user_id' => 'nullable|integer|min:1',
        ]);

        $targetUserId = $canApprove && !empty($validated['user_id']) ? (int) $validated['user_id'] : (int) $user->id;

        $existing = DB::table('investor_payout_accounts')->where('user_id', $targetUserId)->first();
        $payload = [
            'type' => trim((string) $validated['type']),
            'provider' => trim((string) ($validated['provider'] ?? '')) ?: null,
            'account_name' => trim((string) $validated['account_name']),
            'account_number' => trim((string) $validated['account_number']),
            'updated_at' => now(),
        ];
        if ($existing) {
            DB::table('investor_payout_accounts')->where('id', (int) $existing->id)->update($payload);
        } else {
            $payload['user_id'] = $targetUserId;
            $payload['created_at'] = now();
            DB::table('investor_payout_accounts')->insert($payload);
        }

        $account = DB::table('investor_payout_accounts')->where('user_id', $targetUserId)->first();
        return apiResponse(true, 'Rekening/E-Wallet berhasil disimpan.', [
            'account' => $account,
        ]);
    }

    public function investorPayoutRequestCreate(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        if (!$user->hasPermissionTo('investor payout request')) {
            return apiResponse(false, 'Forbidden', ['required_permission' => 'investor payout request'], 403);
        }

        if (!$this->isInvestorUser($user)) {
            return apiResponse(false, 'Forbidden', [], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $amount = (float) $validated['amount'];
        $wallet = DB::table('investor_wallets')->where('user_id', (int) $user->id)->first();
        $balance = (float) ($wallet->balance ?? 0);
        if ($amount > $balance) {
            return apiResponse(false, 'Saldo tidak mencukupi.', ['balance' => $balance], 400);
        }

        $account = DB::table('investor_payout_accounts')->where('user_id', (int) $user->id)->first();
        if (!$account) {
            return apiResponse(false, 'Rekening/E-Wallet belum disimpan.', [], 400);
        }

        $now = now();
        $id = (int) DB::table('investor_payout_requests')->insertGetId([
            'user_id' => (int) $user->id,
            'payout_account_id' => (int) ($account->id ?? 0) ?: null,
            'payout_type' => $account->type ?? null,
            'payout_provider' => $account->provider ?? null,
            'payout_account_name' => $account->account_name ?? null,
            'payout_account_number' => $account->account_number ?? null,
            'amount' => $amount,
            'status' => 'Pending',
            'requested_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return apiResponse(true, 'Request payout berhasil dibuat.', [
            'payout_request_id' => $id,
        ]);
    }

    public function investorInventory(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        if (!$user->hasPermissionTo('investor view')) {
            return apiResponse(false, 'Forbidden', ['required_permission' => 'investor view'], 403);
        }

        $ownerUserId = (int) $request->query('owner_user_id', 0);
        $query = DB::table('barang_owner_stocks as bos')
            ->join('barang as b', 'bos.barang_id', '=', 'b.id')
            ->leftJoin('users as u', 'bos.owner_user_id', '=', 'u.id')
            ->where('bos.owner_type', 'investor')
            ->when($this->isInvestorUser($user), function ($q) use ($user) {
                $q->where('bos.owner_user_id', (int) $user->id);
            })
            ->when(!$this->isInvestorUser($user) && $ownerUserId > 0, function ($q) use ($ownerUserId) {
                $q->where('bos.owner_user_id', $ownerUserId);
            })
            ->select(
                'bos.owner_user_id',
                'u.name as owner_name',
                'b.id as barang_id',
                'b.kode_barang',
                'b.nama_barang',
                DB::raw('SUM(bos.qty) as stock_investor'),
                DB::raw('MAX(bos.hpp_unit) as hpp_unit'),
                DB::raw('MAX(bos.harga_jual_unit) as harga_jual_unit')
            )
            ->groupBy('bos.owner_user_id', 'u.name', 'b.id', 'b.kode_barang', 'b.nama_barang')
            ->orderBy('b.nama_barang');

        return apiResponse(true, 'Data inventory investor berhasil diambil.', $this->paginate($query, $request));
    }

    public function investorInventoryDashboard(Request $request): JsonResponse
    {
        $apiKeyError = validateApiKey($request);
        if ($apiKeyError) {
            return $apiKeyError;
        }

        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }

        if (!$user->hasPermissionTo('investor view')) {
            return apiResponse(false, 'Forbidden', ['required_permission' => 'investor view'], 403);
        }

        $ownerUserId = $this->isInvestorUser($user) ? (int) $user->id : (int) $request->query('owner_user_id', 0);
        if ($ownerUserId < 1) {
            return apiResponse(false, 'owner_user_id wajib.', [], 422);
        }

        $stocks = DB::table('barang_owner_stocks')
            ->join('barang', 'barang_owner_stocks.barang_id', '=', 'barang.id')
            ->leftJoin('unit_satuan', 'barang.unit_satuan_id', '=', 'unit_satuan.id')
            ->leftJoin('kategori_barang', 'barang.kategori_barang_id', '=', 'kategori_barang.id')
            ->where('barang_owner_stocks.owner_type', 'investor')
            ->where('barang_owner_stocks.owner_user_id', $ownerUserId)
            ->select(
                'barang.id as barang_id',
                'barang.kode_barang',
                'barang.nama_barang',
                'unit_satuan.nama_unit_satuan',
                'kategori_barang.nama_kategori_barang',
                'barang_owner_stocks.qty',
                'barang_owner_stocks.harga_jual_unit',
                DB::raw('(barang_owner_stocks.qty * barang_owner_stocks.harga_jual_unit) as total_nilai')
            )
            ->orderBy('barang.nama_barang')
            ->get();

        $installAgg = DB::table('transaksi_details as td')
            ->join('transaksi as t', 'td.transaksi_id', '=', 't.id')
            ->join('barang as b', 'td.barang_id', '=', 'b.id')
            ->leftJoin('unit_satuan', 'b.unit_satuan_id', '=', 'unit_satuan.id')
            ->leftJoin('kategori_barang', 'b.kategori_barang_id', '=', 'kategori_barang.id')
            ->where('td.owner_type', 'investor')
            ->where('td.owner_user_id', $ownerUserId)
            ->where('t.jenis_transaksi', 'out')
            ->where('td.purpose', 'install')
            ->groupBy('td.barang_id', 'b.kode_barang', 'b.nama_barang', 'unit_satuan.nama_unit_satuan', 'kategori_barang.nama_kategori_barang')
            ->select(
                'td.barang_id',
                'b.kode_barang',
                'b.nama_barang',
                'unit_satuan.nama_unit_satuan',
                'kategori_barang.nama_kategori_barang',
                DB::raw('SUM(td.jumlah) as qty_install'),
                DB::raw('SUM(td.jumlah * td.harga_jual_unit) as nilai_install')
            )
            ->get()
            ->keyBy('barang_id');

        $returnAgg = DB::table('transaksi_details as td')
            ->join('transaksi as t', 'td.transaksi_id', '=', 't.id')
            ->where('td.owner_type', 'investor')
            ->where('td.owner_user_id', $ownerUserId)
            ->where('t.jenis_transaksi', 'in')
            ->where('td.purpose', 'return_device')
            ->groupBy('td.barang_id')
            ->select(
                'td.barang_id',
                DB::raw('SUM(td.jumlah) as qty_return'),
                DB::raw('SUM(td.jumlah * td.harga_jual_unit) as nilai_return')
            )
            ->get()
            ->keyBy('barang_id');

        $deployed = [];
        foreach ($installAgg as $barangId => $row) {
            $ret = $returnAgg->get((int) $barangId);
            $qty = (int) ($row->qty_install ?? 0) - (int) ($ret->qty_return ?? 0);
            $nilai = (int) ($row->nilai_install ?? 0) - (int) ($ret->nilai_return ?? 0);
            if ($qty <= 0 && $nilai <= 0) {
                continue;
            }
            $deployed[] = [
                'barang_id' => (int) $barangId,
                'kode_barang' => $row->kode_barang,
                'nama_barang' => $row->nama_barang,
                'nama_unit_satuan' => $row->nama_unit_satuan,
                'nama_kategori_barang' => $row->nama_kategori_barang,
                'qty' => $qty,
                'total_nilai' => max(0, $nilai),
            ];
        }
        usort($deployed, function ($a, $b) {
            return strcmp((string) ($a['nama_barang'] ?? ''), (string) ($b['nama_barang'] ?? ''));
        });

        $summary = [
            'stock_total_qty' => (int) $stocks->sum('qty'),
            'stock_total_nilai' => (int) $stocks->sum('total_nilai'),
            'deployed_total_qty' => (int) collect($deployed)->sum('qty'),
            'deployed_total_nilai' => (int) collect($deployed)->sum('total_nilai'),
        ];
        $summary['total_qty'] = $summary['stock_total_qty'] + $summary['deployed_total_qty'];
        $summary['total_nilai'] = $summary['stock_total_nilai'] + $summary['deployed_total_nilai'];

        $owner = DB::table('users')->where('id', $ownerUserId)->select('id', 'name', 'email')->first();

        return apiResponse(true, 'Dashboard inventory investor berhasil diambil.', [
            'owner' => $owner,
            'summary' => $summary,
            'stocks' => $stocks,
            'deployed' => $deployed,
        ]);
    }

    private function buildUserPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'no_wa' => $user->no_wa,
            'avatar_url' => !empty($user->avatar) ? asset('storage/uploads/avatars/' . $user->avatar) : null,
        ];
    }

    private function getPermissionNames(User $user): array
    {
        return $user->getAllPermissions()->pluck('name')->values()->all();
    }

    private function buildMenuByPermissions(array $permissions): array
    {
        $tree = $this->buildMenuTree($permissions, false);
        $menus = [];
        foreach ($tree as $g) {
            $menus[] = [
                'group' => $g['label'],
                'items' => array_map(function ($it) {
                    return ['key' => $it['key'], 'label' => $it['label']];
                }, $g['items']),
            ];
        }
        return $menus;
    }

    private function buildMenuTree(array $permissions, bool $includeLocked): array
    {
        $hasAny = function (array $required) use ($permissions): bool {
            if (empty($required)) {
                return true;
            }
            foreach ($required as $perm) {
                if (in_array($perm, $permissions, true)) {
                    return true;
                }
            }
            return false;
        };

        $config = (array) config('admin_menu.groups', []);
        $out = [];

        foreach ($config as $group) {
            $itemsOut = [];
            foreach (($group['items'] ?? []) as $item) {
                $required = (array) ($item['required_permissions'] ?? []);
                $allowed = $hasAny($required);
                if (!$includeLocked && !$allowed) {
                    continue;
                }
                $itemsOut[] = [
                    'key' => (string) ($item['key'] ?? ''),
                    'label' => (string) ($item['label'] ?? ''),
                    'icon' => (string) ($item['icon'] ?? 'folder'),
                    'endpoint' => $item['endpoint'] ?? null,
                    'allowed' => $allowed,
                    'locked' => !$allowed,
                    'required_permissions' => $required,
                ];
            }
            if (empty($itemsOut)) {
                continue;
            }
            $out[] = [
                'key' => (string) ($group['key'] ?? ''),
                'label' => (string) ($group['label'] ?? ''),
                'icon' => (string) ($group['icon'] ?? 'folder'),
                'allowed' => true,
                'items' => $itemsOut,
            ];
        }

        return $out;
    }

    private function denyWithoutPermission(Request $request, string $permission): ?JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return apiResponse(false, 'Unauthorized', [], 401);
        }
        if (!$user->hasPermissionTo($permission)) {
            return apiResponse(false, 'Forbidden', ['required_permission' => $permission], 403);
        }
        return null;
    }

    private function paginate($query, Request $request): array
    {
        $limit = max(1, min((int) $request->query('limit', 20), 100));
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $limit;
        $total = (clone $query)->count();
        $data = $query->offset($offset)->limit($limit)->get();

        return [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $data,
        ];
    }
}
