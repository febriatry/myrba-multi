<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\BannerManagementController;
use App\Http\Controllers\Api\InformasiManagementController;
use App\Http\Controllers\Api\TagihanController;
use App\Http\Controllers\Api\TiketAduanController;
use App\Http\Controllers\Api\WithdrawController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\BankController;
use App\Http\Controllers\Api\SettingWebController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\TopupController;
use App\Http\Controllers\Api\DebugController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\InvestorController;

Route::group(['middleware' => 'api.key'], function () {
    Route::prefix('admin')->group(function () {
        Route::post('/auth/login', [AdminController::class, 'login']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/auth/me', [AdminController::class, 'me']);
            Route::post('/auth/logout', [AdminController::class, 'logout']);
            Route::get('/menus', [AdminController::class, 'menus']);
            Route::get('/menu-tree', [AdminController::class, 'menuTree']);
            Route::get('/badges', [AdminController::class, 'badges']);
            Route::post('/fcm-token', [AdminController::class, 'storeFcmToken']);
            Route::put('/profile', [AdminController::class, 'updateProfile']);
            Route::post('/profile/avatar', [AdminController::class, 'updateAvatar']);
            Route::get('/request-pelanggan', [AdminController::class, 'requestPelanggan']);
            Route::get('/pelanggans', [AdminController::class, 'pelanggans']);
            Route::post('/pelanggans/{id}/status', [AdminController::class, 'pelangganUpdateStatus']);
            Route::post('/pelanggans/{id}/materials/approve', [AdminController::class, 'pelangganApproveMaterials']);
            Route::post('/pelanggans/{id}/install/approve', [AdminController::class, 'pelangganApproveInstall']);
            Route::get('/tiket-aduans', [AdminController::class, 'tiketAduans']);
            Route::get('/informasi-management', [AdminController::class, 'informasiManagement']);
            Route::get('/tagihans', [AdminController::class, 'tagihans']);
            Route::post('/tagihans/{id}/bayar', [AdminController::class, 'tagihanBayar']);
            Route::get('/odcs', [AdminController::class, 'odcs']);
            Route::get('/odps', [AdminController::class, 'odps']);
            Route::get('/ppp/active', [AdminController::class, 'pppActive']);
            Route::get('/ppp/non-active', [AdminController::class, 'pppNonActive']);
            Route::get('/ppp/profiles', [AdminController::class, 'pppProfiles']);
            Route::get('/ppp/secrets', [AdminController::class, 'pppSecrets']);
            Route::get('/barangs', [AdminController::class, 'barangs']);
            Route::get('/kategori-barangs', [AdminController::class, 'kategoriBarangs']);
            Route::get('/transaksi/stock-in', [AdminController::class, 'transaksiStockIn']);
            Route::get('/transaksi/stock-out', [AdminController::class, 'transaksiStockOut']);
            Route::get('/pemasukans', [AdminController::class, 'pemasukans']);
            Route::get('/pengeluarans', [AdminController::class, 'pengeluarans']);
            Route::get('/banks', [AdminController::class, 'banks']);
            Route::get('/bank-accounts', [AdminController::class, 'bankAccounts']);
            Route::get('/package-categories', [AdminController::class, 'packageCategories']);
            Route::get('/packages', [AdminController::class, 'packages']);
            Route::get('/area-coverages', [AdminController::class, 'areaCoverages']);
            Route::get('/setting-mikrotiks', [AdminController::class, 'settingMikrotiks']);
            Route::get('/olts', [AdminController::class, 'olts']);
            Route::get('/topups', [AdminController::class, 'topups']);
            Route::get('/withdraws', [AdminController::class, 'withdraws']);
            Route::get('/investors', [AdminController::class, 'investors']);
            Route::get('/investor-payout-requests', [AdminController::class, 'investorPayoutRequests']);
            Route::post('/investor-payout-requests', [AdminController::class, 'investorPayoutRequestCreate']);
            Route::get('/investor/dashboard', [AdminController::class, 'investorDashboard']);
            Route::get('/investor/admin-dashboard', [AdminController::class, 'investorAdminDashboard']);
            Route::get('/investor/payout-accounts', [AdminController::class, 'investorPayoutAccounts']);
            Route::post('/investor/payout-accounts', [AdminController::class, 'investorPayoutAccountUpsert']);
            Route::get('/investor/inventory', [AdminController::class, 'investorInventory']);
            Route::get('/investor/inventory-dashboard', [AdminController::class, 'investorInventoryDashboard']);
            Route::get('/investor/pelanggans', [AdminController::class, 'investorPelanggans']);
            Route::get('/investor/tagihans', [AdminController::class, 'investorTagihans']);
            Route::post('/tagihans/{id}/validasi', [AdminController::class, 'tagihanValidasi']);
            Route::post('/tagihans/{id}/printed', [AdminController::class, 'tagihanPrinted']);
            Route::get('/tiket-aduans', [AdminController::class, 'tiketAduans']);
            Route::get('/informasi-management', [AdminController::class, 'informasiManagement']);
            Route::get('/tagihans', [AdminController::class, 'tagihans']);
            Route::get('/odcs', [AdminController::class, 'odcs']);
            Route::get('/odps', [AdminController::class, 'odps']);
            Route::get('/ppp/active', [AdminController::class, 'pppActive']);
            Route::get('/ppp/non-active', [AdminController::class, 'pppNonActive']);
        });
    });

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/detail-pelanggan/{id}', [AuthController::class, 'getDetailPelangganById']);
        Route::post('/update-pelanggan/{id}', [AuthController::class, 'updatePelanggan']);
    });

    Route::prefix('password')->group(function () {
        Route::post('/request-token', [ForgotPasswordController::class, 'requestToken']);
        Route::post('/reset', [ForgotPasswordController::class, 'resetPassword']);
    });

    Route::prefix('tiket')->group(function () {
        Route::post('/create', [TiketAduanController::class, 'create']);
        Route::post('/update/{id}', [TiketAduanController::class, 'update']);
        Route::delete('/delete/{id}', [TiketAduanController::class, 'delete']);
        Route::get('/detail/{id}', [TiketAduanController::class, 'getById']);
        Route::get('/pelanggan/{pelanggan_id}', [TiketAduanController::class, 'getByPelanggan']);
        Route::post('/update-status/{id}', [TiketAduanController::class, 'updateStatus']);
    });

    Route::prefix('banner-management')->group(function () {
        Route::get('/', [BannerManagementController::class, 'getAll']);
    });

    Route::prefix('informasi-management')->group(function () {
        Route::get('/', [InformasiManagementController::class, 'getAll']);
        Route::get('/detail/{id}', [InformasiManagementController::class, 'getById']);
        Route::get('/search', [InformasiManagementController::class, 'searchByJudul']);
    });

    Route::prefix('tagihan')->group(function () {
        Route::get('/pelanggan/{pelanggan_id}', [TagihanController::class, 'getByPelanggan']);
        Route::get('/detail/{id}', [TagihanController::class, 'getById']);
        Route::get('/payment-methods', [TagihanController::class, 'getPaymentMethods']);
        Route::post('/pay-with-saldo/{tagihan_id}', [TagihanController::class, 'payWithSaldo']);
        Route::post('/pay-with-method/{tagihan_id}', [TagihanController::class, 'payWithMethod']);
        Route::get('/transaction-detail/{reference}', [TagihanController::class, 'getTransactionDetail']);
        Route::get('/search', [TagihanController::class, 'search']);
    });

    Route::prefix('balance')->group(function () {
        Route::get('/history-pelanggan/{pelanggan_id}', [BalanceController::class, 'getHistoricalBalanceByPelanggan']);
    });

    Route::prefix('withdraw')->group(function () {
        Route::get('/history/{pelanggan_id}', [WithdrawController::class, 'getByPelanggan']);
        Route::post('/create', [WithdrawController::class, 'create']);
        Route::post('/update/{id}', [WithdrawController::class, 'update']);
        Route::delete('/delete/{id}', [WithdrawController::class, 'delete']);
    });

    Route::prefix('payment')->group(function () {
        Route::get('/banks', [BankController::class, 'getBankAccounts']);
    });

    Route::prefix('topup')->group(function () {
        Route::get('/history/{pelanggan_id}', [TopupController::class, 'getHistory']);
        Route::post('/manual', [TopupController::class, 'createManual']);
        Route::post('/tripay', [TopupController::class, 'createTripay']);
        Route::post('/manual/update/{id}', [TopupController::class, 'updateManual']);
        Route::delete('/manual/delete/{id}', [TopupController::class, 'deleteManual']);
    });

    // Route untuk Pengaturan Website
    Route::prefix('settings')->group(function () {
        Route::get('/public', [SettingWebController::class, 'getPublicSettings']);
    });

    Route::prefix('client')->group(function () {
        Route::get('/dashboard', [ClientController::class, 'dashboard']);
        Route::get('/history', [ClientController::class, 'history']);
        Route::get('/account', [ClientController::class, 'account']);
        Route::get('/banners', [ClientController::class, 'banners']);
        Route::get('/infos', [ClientController::class, 'infos']);
        Route::post('/fcm-token', [FcmTokenController::class, 'store']);
    });
    Route::prefix('investor')->middleware('auth:sanctum')->group(function () {
        Route::get('/wallet', [InvestorController::class, 'wallet']);
        Route::get('/wallet/history', [InvestorController::class, 'walletHistory']);
        Route::post('/payout/request', [InvestorController::class, 'createPayoutRequest']);
    });
});

Route::get('/debug/api-key', [DebugController::class, 'apiKeyCheck']);
