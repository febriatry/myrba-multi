<?php

use App\Http\Controllers\AuditKeuanganController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PlatformOwnerDashboardController;
use App\Http\Controllers\PlatformSettingsController;
use App\Http\Controllers\PlatformTenantSuperAdminController;
use App\Http\Controllers\PlatformWaUsageController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\TenantAdminDashboardController;
use App\Http\Controllers\TenantPaymentController;
use App\Http\Controllers\TenantPlanController;
use App\Http\Controllers\TenantPlatformController;
use App\Http\Controllers\TenantSuperAdminToolsController;
use App\Http\Controllers\TenantWaController;
use App\Http\Controllers\WaTunggakanBroadcastController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/github', [\App\Http\Controllers\GitHubWebhookController::class, 'handle'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.github.handle');
Route::get('/webhooks/github', function () {
    return response()->json(['status' => true, 'message' => 'github webhook reachable']);
});
Route::get('/app-config', [\App\Http\Controllers\AppConfigController::class, 'index'])->name('app.config');
Route::controller(\App\Http\Controllers\IvosightWebhookController::class)->group(function () {
    Route::get('/webhooks/ivosight', 'verify')->name('webhooks.ivosight.verify');
    Route::post('/webhooks/ivosight', 'handle')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
        ->name('webhooks.ivosight.handle');
});

// Callback Payment Tripay
Route::controller(App\Http\Controllers\TripayCallbackController::class)->group(function () {
    Route::post('/handle', 'handle')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
        ->name('handle');
});

Route::get('invoice/signed/{id}', [TagihanController::class, 'invoiceSigned'])
    ->name('invoice.signed')
    ->middleware('signed');

// FRONT END - LANDING PAGE Public
Route::controller(App\Http\Controllers\Frontend\WebController::class)->group(function () {
    Route::get('/', 'index')->name('website');
    Route::get('/cekTagihan', 'index')->name('cekTagihan');
    Route::get('/bayar/{tagihan_id}/{metode}', 'bayar')->name('bayar');
    Route::get('/detailBayar/{id}', 'detailBayar')->name('detailBayar');
    Route::get('/syarat-ketentuan', 'syaratKetentuan')->name('syarat-ketentuan');
    Route::get('/r/{code}', 'referralRedirect')->name('referral.redirect');
    Route::get('/daftar', 'daftar')->name('daftar');
    Route::post('/daftar', 'daftarStore')->name('daftar.store');
});

// PANEL ADMIN
Route::middleware(['auth', 'web'])->group(function () {
    Route::get('/tenant/dashboard', [TenantAdminDashboardController::class, 'index'])->name('tenant.dashboard');
    Route::get('/tenant/sync-permissions', [TenantSuperAdminToolsController::class, 'syncPermissions'])->name('tenant.sync-permissions');
    Route::middleware(['tenant.feature:whatsapp'])->group(function () {
        Route::get('/tenant/wa-settings', [TenantWaController::class, 'settings'])->name('tenant.wa.settings');
        Route::post('/tenant/wa-settings', [TenantWaController::class, 'updateSettings'])->name('tenant.wa.settings.update');
        Route::get('/tenant/wa-report', [TenantWaController::class, 'report'])->name('tenant.wa.report');
    });
    Route::get('/tenant/payment-settings', [TenantPaymentController::class, 'settings'])->name('tenant.payment.settings')->middleware('tenant.feature:payment_gateway');
    Route::post('/tenant/payment-settings', [TenantPaymentController::class, 'updateSettings'])->name('tenant.payment.settings.update')->middleware('tenant.feature:payment_gateway');

    Route::prefix('platform')->name('platform.')->middleware(['platform.team', 'role:Platform Owner'])->group(function () {
        Route::get('/', [PlatformOwnerDashboardController::class, 'index'])->name('dashboard');
        Route::resource('tenants', TenantPlatformController::class)->except(['show']);
        Route::get('tenants/{tenant}/super-admins', [PlatformTenantSuperAdminController::class, 'index'])->name('tenants.super-admins.index');
        Route::post('tenants/{tenant}/super-admins', [PlatformTenantSuperAdminController::class, 'store'])->name('tenants.super-admins.store');
        Route::delete('tenants/{tenant}/super-admins/{userId}', [PlatformTenantSuperAdminController::class, 'destroy'])->name('tenants.super-admins.destroy');
        Route::resource('plans', TenantPlanController::class)->except(['show']);
        Route::get('wa-usage', [PlatformWaUsageController::class, 'index'])->name('wa-usage.index');
        Route::get('settings', [PlatformSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings/tripay', [PlatformSettingsController::class, 'updateTripay'])->name('settings.update.tripay');
        Route::post('settings/wa', [PlatformSettingsController::class, 'updateWa'])->name('settings.update.wa');
        Route::post('settings/wa-templates', [PlatformSettingsController::class, 'updateWaTemplates'])->name('settings.update.wa-templates');
    });

    Route::get('/profile', App\Http\Controllers\ProfileController::class)->name('profile');
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/dashboard', 'index')->name('dashboard');
        Route::get('/dashboard/finance-monthly', 'financeMonthly')->name('dashboard.financeMonthly');
        Route::get('/dashboard/invoice-status-monthly', 'invoiceStatusMonthly')->name('dashboard.invoiceStatusMonthly');
    });
    Route::middleware(['platform.team', 'role:Platform Operator|Platform Owner'])->group(function () {
        Route::get('/wa-config', [\App\Http\Controllers\WaConfigController::class, 'index'])->name('wa-config.index');
        Route::post('/wa-config/toggle-status', [\App\Http\Controllers\WaConfigController::class, 'toggleStatus'])->name('wa-config.toggle-status');
        Route::post('/wa-config/test-connection', [\App\Http\Controllers\WaConfigController::class, 'testConnection'])->name('wa-config.test-connection');
        Route::post('/wa-config/sync-templates', [\App\Http\Controllers\WaConfigController::class, 'syncTemplates'])->name('wa-config.sync-templates');
        Route::post('/wa-config/template-mappings', [\App\Http\Controllers\WaConfigController::class, 'storeMapping'])->name('wa-config.template-mappings.store');
        Route::put('/wa-config/template-mappings/{id}', [\App\Http\Controllers\WaConfigController::class, 'updateMapping'])->name('wa-config.template-mappings.update');
        Route::delete('/wa-config/template-mappings/{id}', [\App\Http\Controllers\WaConfigController::class, 'destroyMapping'])->name('wa-config.template-mappings.destroy');
        Route::get('/wa-status-logs', [\App\Http\Controllers\WaStatusLogController::class, 'index'])->name('wa-status-logs.index');
        Route::get('/wa-status-logs/export-csv', [\App\Http\Controllers\WaStatusLogController::class, 'exportCsv'])->name('wa-status-logs.export-csv');
    });
    Route::get('/wa-hub', [\App\Http\Controllers\WaHubController::class, 'index'])
        ->name('wa-hub.index')
        ->middleware('tenant.feature:whatsapp');
    Route::get('/wa-blast', function () {
        return redirect()->route('sendnotifs.index');
    })->name('wa-blast.index')->middleware('tenant.feature:whatsapp');
    Route::get('/wa-tunggakan', [WaTunggakanBroadcastController::class, 'index'])->name('wa-tunggakan.index')->middleware('tenant.feature:whatsapp');
    Route::get('/wa-tunggakan/data', [WaTunggakanBroadcastController::class, 'data'])->name('wa-tunggakan.data')->middleware('tenant.feature:whatsapp');
    Route::post('/wa-tunggakan/send', [WaTunggakanBroadcastController::class, 'send'])->name('wa-tunggakan.send')->middleware('tenant.feature:whatsapp');
    Route::resource('banks', App\Http\Controllers\BankController::class)->middleware('tenant.feature:finance');
    Route::resource('bank-accounts', App\Http\Controllers\BankAccountController::class)->middleware('tenant.feature:finance');
    Route::resource('package-categories', App\Http\Controllers\PackageCategoryController::class)->middleware('tenant.feature:layanan');
    Route::resource('packages', App\Http\Controllers\PackageController::class)->middleware('tenant.feature:layanan');
    Route::resource('area-coverages', App\Http\Controllers\AreaCoverageController::class)->middleware('tenant.feature:layanan');
    Route::get('/layanan-hub', [\App\Http\Controllers\LayananHubController::class, 'index'])->name('layanan-hub.index')->middleware('tenant.feature:layanan');
    Route::resource('profile-pppoes', App\Http\Controllers\ProfilePppoeController::class)->middleware('tenant.feature:pppoe');
    Route::resource('active-ppps', App\Http\Controllers\ActivePppController::class)->middleware('tenant.feature:pppoe');
    Route::resource('non-active-ppps', App\Http\Controllers\ActiveNonPppController::class)->middleware('tenant.feature:pppoe');
    Route::controller(App\Http\Controllers\ActivePppController::class)->middleware('tenant.feature:pppoe')->group(function () {
        Route::get('monitoring', 'monitoring')->name('monitoring');
    });
    Route::controller(App\Http\Controllers\SecretPppController::class)->middleware('tenant.feature:pppoe')->group(function () {
        Route::put('enableSecret/{id}', 'enable')->name('secret-ppps.enable');
        Route::put('disableSecret/{id}/{name}', 'disable')->name('secret-ppps.disable');
        Route::delete('deleteSecret/{id}/{name}', 'deleteSecret')->name('secret-ppps.deleteSecret');
    });
    Route::resource('hotspotprofiles', App\Http\Controllers\HotspotprofileController::class)->middleware('tenant.feature:hotspot');
    Route::controller(App\Http\Controllers\HotspotprofileController::class)->middleware('tenant.feature:hotspot')->group(function () {
        Route::delete('deleteProfile/{id}/{name}', 'deleteProfile')->name('hotspotprofiles.deleteProfile');
    });

    Route::resource('secret-ppps', App\Http\Controllers\SecretPppController::class)->middleware('tenant.feature:pppoe');
    Route::get('/pppoe-hub', [\App\Http\Controllers\PppoeHubController::class, 'index'])->name('pppoe-hub.index')->middleware('tenant.feature:pppoe');
    Route::resource('logs', App\Http\Controllers\LogController::class)->middleware('tenant.feature:network');
    Route::resource('activity-logs', App\Http\Controllers\ActivityLogController::class)->only(['index'])->middleware('tenant.feature:settings');
    Route::get('/utilities-hub', [\App\Http\Controllers\UtilitiesHubController::class, 'index'])->name('utilities-hub.index')->middleware('tenant.feature:settings');
    Route::resource('dhcps', App\Http\Controllers\DhcpController::class)->middleware('tenant.feature:network');
    Route::resource('interfaces', App\Http\Controllers\InterfaceController::class)->middleware('tenant.feature:network');
    Route::resource('statics', App\Http\Controllers\StaticController::class)->middleware('tenant.feature:network');
    Route::resource('settingmikrotiks', App\Http\Controllers\SettingmikrotikController::class)->middleware('tenant.feature:network');

    Route::resource('statusrouters', App\Http\Controllers\StatusrouterController::class)->middleware('tenant.feature:network');
    Route::get('/router-hub', [\App\Http\Controllers\RouterHubController::class, 'index'])->name('router-hub.index')->middleware('tenant.feature:network');
    Route::controller(App\Http\Controllers\StatusrouterController::class)->middleware('tenant.feature:network')->group(function () {
        Route::get('reboot', 'reboot')->name('reboot');
    });
    Route::resource('hotspotactives', App\Http\Controllers\HotspotactiveController::class)->middleware('tenant.feature:hotspot');
    Route::resource('hotspotusers', App\Http\Controllers\HotspotuserController::class)->middleware('tenant.feature:hotspot');
    Route::get('/hotspot-hub', [\App\Http\Controllers\HotspotHubController::class, 'index'])->name('hotspot-hub.index')->middleware('tenant.feature:hotspot');
    Route::get('/network-hub', [\App\Http\Controllers\NetworkHubController::class, 'index'])->name('network-hub.index')->middleware('tenant.feature:network');
    Route::get('/cms-hub', [\App\Http\Controllers\CmsHubController::class, 'index'])->name('cms-hub.index')->middleware('tenant.feature:cms');
    Route::get('/settings-hub', [\App\Http\Controllers\SettingsHubController::class, 'index'])->name('settings-hub.index')->middleware('tenant.feature:settings');
    Route::controller(App\Http\Controllers\HotspotuserController::class)->middleware('tenant.feature:hotspot')->group(function () {
        Route::put('enableHotspot/{id}', 'enable')->name('hotspotusers.enable');
        Route::put('disableHotspot/{id}/{user}', 'disable')->name('hotspotusers.disable');
        Route::put('resetHotspot/{id}', 'reset')->name('hotspotusers.reset');
        Route::delete('deleteHotspot/{id}/{user}', 'deleteHotspot')->name('hotspotusers.delete');
        Route::get('deleteByComment', 'deleteByComment')->name('hotspotusers.deleteByComment');
        Route::get('cetakVoucher', 'cetakVoucher')->name('hotspotusers.cetakVoucher');
    });
    Route::resource('odcs', App\Http\Controllers\OdcController::class)->middleware('tenant.feature:layanan');
    Route::resource('odps', App\Http\Controllers\OdpController::class)->middleware('tenant.feature:layanan');
    Route::resource('pelanggans', App\Http\Controllers\PelangganController::class)->middleware('tenant.feature:pelanggan');
    Route::get('/pelanggans-request', [App\Http\Controllers\PelangganController::class, 'requestIndex'])
        ->name('pelanggans-request.index')
        ->middleware('tenant.feature:pelanggan');
    Route::get('/pelanggans-request/data', [App\Http\Controllers\PelangganController::class, 'requestData'])
        ->name('pelanggans-request.data')
        ->middleware('tenant.feature:pelanggan');
    Route::get('/pelanggans-request/{id}/materials', [App\Http\Controllers\PelangganController::class, 'requestMaterials'])
        ->name('pelanggans-request.materials')
        ->middleware('tenant.feature:pelanggan');
    Route::post('/pelanggans-request/{id}/materials', [App\Http\Controllers\PelangganController::class, 'requestMaterialsStore'])
        ->name('pelanggans-request.materials.store')
        ->middleware('tenant.feature:pelanggan');
    Route::post('/pelanggans-request/{id}/materials/approve', [App\Http\Controllers\PelangganController::class, 'requestMaterialsApprove'])
        ->name('pelanggans-request.materials.approve')
        ->middleware('tenant.feature:pelanggan');

    Route::get('/pelanggan-hub', [App\Http\Controllers\PelangganHubController::class, 'index'])
        ->name('pelanggan-hub.index')
        ->middleware('tenant.feature:pelanggan');
    Route::controller(App\Http\Controllers\PelangganController::class)->group(function () {
        Route::get('cetakSurat/{id}', 'cetakSurat')->name('pelanggans.cetakSurat');
        Route::get('setToExpired/{id}/{user_pppoe}', 'setToExpired')->name('pelanggans.setToExpired');
        Route::get('setNonToExpired/{id}/{user_pppoe}', 'setNonToExpired')->name('pelanggans.setNonToExpired');
        Route::get('setToExpiredStatic/{id}/{user_static}', 'setToExpiredStatic')
            ->name('pelanggans.setToExpiredStatic');
        Route::get('setNonToExpiredStatic/{id}/{user_static}', 'setNonToExpiredStatic')
            ->name('pelanggans.setNonToExpiredStatic');
        Route::post('pelanggans/{id}/genieacs/link', 'genieacsLink')->name('pelanggans.genieacs.link');
        Route::post('pelanggans/{id}/genieacs/refresh', 'genieacsRefresh')->name('pelanggans.genieacs.refresh');
        Route::post('pelanggans/{id}/genieacs/reboot', 'genieacsReboot')->name('pelanggans.genieacs.reboot');
        Route::post('pelanggans/{id}/genieacs/unlink', 'genieacsUnlink')->name('pelanggans.genieacs.unlink');
        Route::get('pelanggans/{id}/genieacs/search', 'genieacsSearch')->name('pelanggans.genieacs.search');
        Route::get('getTableArea/{id}', 'getTableArea')->name('api.getTableArea');
        Route::get('getTableOdc/{id}', 'getTableOdc')->name('api.getTableOdc');
        Route::get('getTableOdp/{id}', 'getTableOdp')->name('api.getTableOdp');
        Route::get('api/pelanggan/search', 'searchPelanggan')->name('api.search_pelanggan');
        Route::get('api/pelanggan/estimasi', 'estimasiPendapatan')->name('api.pelanggan.estimasi');
    });
    Route::get('api/barang/search', [App\Http\Controllers\BarangController::class, 'search'])->name('api.search_barang');

    Route::get('pelanggans/{pelanggan}/return-device', [App\Http\Controllers\PelangganDeviceReturnController::class, 'create'])
        ->name('pelanggans.return-device.create')
        ->middleware(['auth', 'permission:pelanggan return device']);
    Route::post('pelanggans/{pelanggan}/return-device', [App\Http\Controllers\PelangganDeviceReturnController::class, 'store'])
        ->name('pelanggans.return-device.store')
        ->middleware(['auth', 'permission:pelanggan return device']);
    Route::get('pelanggans/{pelanggan}/return-device/{return}', [App\Http\Controllers\PelangganDeviceReturnController::class, 'show'])
        ->name('pelanggans.return-device.show')
        ->middleware(['auth', 'permission:pelanggan return device view']);
    Route::post('pelanggans/{pelanggan}/return-device/{return}/cancel', [App\Http\Controllers\PelangganDeviceReturnController::class, 'cancel'])
        ->name('pelanggans.return-device.cancel')
        ->middleware(['auth', 'permission:pelanggan return device cancel']);
    Route::get('pelanggans/return-device/bulk', [App\Http\Controllers\PelangganDeviceReturnBulkController::class, 'create'])
        ->name('pelanggans.return-device.bulk.create')
        ->middleware(['auth', 'permission:pelanggan return device bulk']);
    Route::post('pelanggans/return-device/bulk', [App\Http\Controllers\PelangganDeviceReturnBulkController::class, 'store'])
        ->name('pelanggans.return-device.bulk.store')
        ->middleware(['auth', 'permission:pelanggan return device bulk']);

    Route::get('apiodc/{id}', [App\Http\Controllers\OdcController::class, 'odc'])->name('api.odc');
    Route::get('apiodp/{id}', [App\Http\Controllers\OdpController::class, 'odp'])->name('api.odp');
    Route::get('getPort/{id}', [App\Http\Controllers\OdpController::class, 'getPort'])->name('api.getPort');
    Route::get('getProfile/{id}', [App\Http\Controllers\OdpController::class, 'getProfile'])->name('api.getProfile');
    Route::get('getStatic/{id}', [App\Http\Controllers\OdpController::class, 'getStatic'])->name('api.getStatic');
    Route::get('/pemasukans/summary', [\App\Http\Controllers\PemasukanController::class, 'summary'])->name('pemasukans.summary')->middleware('tenant.feature:finance');
    Route::resource('pemasukans', App\Http\Controllers\PemasukanController::class)->middleware('tenant.feature:finance');
    Route::get('/pengeluarans/summary', [\App\Http\Controllers\PengeluaranController::class, 'summary'])->name('pengeluarans.summary')->middleware('tenant.feature:finance');
    Route::resource('pengeluarans', App\Http\Controllers\PengeluaranController::class)->middleware('tenant.feature:finance');
    Route::get('finance-income', [App\Http\Controllers\FinanceIncomeHubController::class, 'index'])->name('finance-income.index')->middleware('tenant.feature:finance');
    Route::get('finance-expense', [App\Http\Controllers\FinanceExpenseHubController::class, 'index'])->name('finance-expense.index')->middleware('tenant.feature:finance');
    Route::get('finance-bank', [App\Http\Controllers\FinanceBankHubController::class, 'index'])->name('finance-bank.index')->middleware('tenant.feature:finance');
    Route::get('finance-report', [App\Http\Controllers\FinanceReportHubController::class, 'index'])->name('finance-report.index')->middleware('tenant.feature:finance');
    Route::get('finance-hub', [App\Http\Controllers\FinanceHubController::class, 'index'])->name('finance-hub.index')->middleware('tenant.feature:finance');
    Route::get('finance-setors', [App\Http\Controllers\FinanceSetorController::class, 'index'])->name('finance-setors.index')->middleware('tenant.feature:finance');
    Route::get('finance-setors/create', [App\Http\Controllers\FinanceSetorController::class, 'create'])->name('finance-setors.create')->middleware('tenant.feature:finance');
    Route::post('finance-setors', [App\Http\Controllers\FinanceSetorController::class, 'store'])->name('finance-setors.store')->middleware('tenant.feature:finance');
    Route::post('finance-setors/{id}/approve', [App\Http\Controllers\FinanceSetorController::class, 'approve'])->name('finance-setors.approve')->middleware('tenant.feature:finance');
    Route::post('finance-setors/{id}/reject', [App\Http\Controllers\FinanceSetorController::class, 'reject'])->name('finance-setors.reject')->middleware('tenant.feature:finance');
    Route::get('finance-setors/{id}/export-pdf', [App\Http\Controllers\FinanceSetorController::class, 'exportPdf'])->name('finance-setors.exportPdf')->middleware('tenant.feature:finance');
    // Summary route MUST be defined before resource to avoid conflict with 'tagihans/{tagihan}'
    Route::get('/tagihans/summary', [App\Http\Controllers\TagihanController::class, 'summary'])->name('tagihans.summary')->middleware('tenant.feature:finance');
    Route::resource('tagihans', App\Http\Controllers\TagihanController::class)->middleware('tenant.feature:finance');
    Route::get('/public/tagihan', [App\Http\Controllers\TagihanController::class, 'publicTagihan'])->name('public.tagihan');
    Route::controller(App\Http\Controllers\TagihanController::class)->middleware('tenant.feature:finance')->group(function () {
        Route::get('invoice/{id}', 'invoice')->name('invoice.pdf');
        Route::get('invoice/print/{id}', 'invoice')->name('invoice.print');
        Route::get('invoice/escpos/{id}', 'invoiceEscpos')->name('invoice.escpos');
        Route::post('/bayarTagihan', 'bayarTagihan')->name('bayarTagihan');
        Route::post('/validasiTagihan', 'validasiTagihan')->name('validasiTagihan');
        Route::post('/sendTagihanWa/{id}', 'sendTagihanWa')->name('sendTagihanWa');
        Route::post('/tagihans/sendWa', 'sendWa')->name('tagihans.sendWa');
        Route::get('/sendInvoice/{id}', 'sendInvoice')->name('sendInvoice');
    });
    Route::controller(LaporanController::class)->middleware('tenant.feature:finance')->group(function () {
        Route::get('/pelanggan-data', 'getPelangganData')->name('laporans.pelangganData');
        Route::get('laporans/export-pdf', 'exportPdf')->name('laporans.exportPdf');
        Route::get('laporans/export-kas', 'exportKas')->name('laporans.exportKas');
    });
    Route::resource('laporans', App\Http\Controllers\LaporanController::class)->middleware('tenant.feature:finance');
    Route::controller(AuditKeuanganController::class)->middleware('tenant.feature:audit')->group(function () {
        Route::get('/audit-keuangan', 'index')->name('audit-keuangan.index');
        Route::get('/audit-keuangan/summary-area', 'summaryArea')->name('audit-keuangan.summary-area');
        Route::get('/audit-keuangan/pelanggan-tunggak', 'pelangganTunggak')->name('audit-keuangan.pelanggan-tunggak');
        Route::get('/audit-keuangan/missing-tagihan', 'missingTagihan')->name('audit-keuangan.missing-tagihan');
        Route::get('/audit-keuangan/wa-status', 'waStatus')->name('audit-keuangan.wa-status');
        Route::get('/audit-keuangan/export/summary-area', 'exportSummaryArea')->name('audit-keuangan.export.summary-area');
        Route::get('/audit-keuangan/export/pelanggan-tunggak', 'exportPelangganTunggak')->name('audit-keuangan.export.pelanggan-tunggak');
        Route::get('/audit-keuangan/export/missing-tagihan', 'exportMissingTagihan')->name('audit-keuangan.export.missing-tagihan');
        Route::get('/audit-keuangan/export/wa-status', 'exportWaStatus')->name('audit-keuangan.export.wa-status');
        Route::get('/audit-keuangan/export/summary-area/excel', 'exportSummaryAreaExcel')->name('audit-keuangan.export.summary-area.excel');
        Route::get('/audit-keuangan/export/pelanggan-tunggak/excel', 'exportPelangganTunggakExcel')->name('audit-keuangan.export.pelanggan-tunggak.excel');
        Route::get('/audit-keuangan/export/missing-tagihan/excel', 'exportMissingTagihanExcel')->name('audit-keuangan.export.missing-tagihan.excel');
        Route::get('/audit-keuangan/export/wa-status/excel', 'exportWaStatusExcel')->name('audit-keuangan.export.wa-status.excel');
        Route::get('/audit-keuangan/export/summary-area/pdf', 'exportSummaryAreaPdf')->name('audit-keuangan.export.summary-area.pdf');
        Route::get('/audit-keuangan/export/pelanggan-tunggak/pdf', 'exportPelangganTunggakPdf')->name('audit-keuangan.export.pelanggan-tunggak.pdf');
        Route::get('/audit-keuangan/export/missing-tagihan/pdf', 'exportMissingTagihanPdf')->name('audit-keuangan.export.missing-tagihan.pdf');
        Route::get('/audit-keuangan/export/wa-status/pdf', 'exportWaStatusPdf')->name('audit-keuangan.export.wa-status.pdf');
    });
    Route::resource('sendnotifs', App\Http\Controllers\SendnotifController::class);
    Route::controller(App\Http\Controllers\SendnotifController::class)->group(function () {
        Route::post('/kirim_pesan', 'kirim_pesan')->name('kirim_pesan');
    });
    Route::resource('active-statics', App\Http\Controllers\ActiveStaticController::class)->middleware('auth');
    Route::resource('non-active-statics', App\Http\Controllers\NonActiveStaticController::class)->middleware('auth');
    // WA session (QR) dihapus; gunakan WhatsApp Broadcast (Ivosight)

    // Stok Masuk
    Route::get('transaksi-stock-in/export-pdf', [\App\Http\Controllers\TransaksiStockInController::class, 'exportPdf'])->name('transaksi-stock-in.exportPdf');
    Route::get('transaksi-stock-in/{transaksi}/export-item-pdf', [\App\Http\Controllers\TransaksiStockInController::class, 'exportItemPdf'])->name('transaksi-stock-in.exportItemPdf');
    Route::resource('transaksi-stock-in', \App\Http\Controllers\TransaksiStockInController::class)->except(['destroy'])->parameters(['transaksi-stock-in' => 'transaksi']);
    Route::delete('transaksi-stock-in/{transaksi}', [\App\Http\Controllers\TransaksiStockInController::class, 'destroy'])->name('transaksi-stock-in.destroy');

    // Stok Keluar
    Route::get('transaksi-stock-out/owner-stock', [\App\Http\Controllers\TransaksiStockOutController::class, 'ownerStock'])->name('transaksi-stock-out.owner-stock');
    Route::get('transaksi-stock-out/export-pdf', [\App\Http\Controllers\TransaksiStockOutController::class, 'exportPdf'])->name('transaksi-stock-out.exportPdf');
    Route::get('transaksi-stock-out/{transaksi}/export-item-pdf', [\App\Http\Controllers\TransaksiStockOutController::class, 'exportItemPdf'])->name('transaksi-stock-out.exportItemPdf');
    Route::resource('transaksi-stock-out', \App\Http\Controllers\TransaksiStockOutController::class)->except(['destroy'])->parameters(['transaksi-stock-out' => 'transaksi']);
    Route::delete('transaksi-stock-out/{transaksi}', [\App\Http\Controllers\TransaksiStockOutController::class, 'destroy'])->name('transaksi-stock-out.destroy');

    Route::controller(App\Http\Controllers\LaporanBarangController::class)
        ->prefix('laporan-barang')
        ->name('laporan-barang.') // <-- TAMBAHKAN BARIS INI
        ->middleware('permission:laporan barang view') // Pindahkan middleware dasar ke sini
        ->group(function () {
            Route::get('/', 'index')->name('index'); // Sekarang akan menjadi 'laporan-barang.index'
            Route::get('/export-excel', 'exportExcel')->name('exportExcel')->middleware('permission:laporan barang export');
        });
});

Route::middleware(['auth', 'web'])->group(function () {
    Route::resource('users', App\Http\Controllers\UserController::class)->middleware('tenant.feature:settings');
    Route::resource('roles', App\Http\Controllers\RoleAndPermissionController::class)->middleware('tenant.feature:settings');
    Route::resource('vouchers', App\Http\Controllers\VoucherController::class)->middleware('tenant.feature:settings');
    Route::post('/pelanggans/update-generate-tagihan', [PelangganController::class, 'updateGenerateTagihan'])
        ->name('pelanggans.update_generate_tagihan');
    Route::resource('category-pemasukans', App\Http\Controllers\CategoryPemasukanController::class)->middleware(['auth', 'tenant.feature:finance']);
    Route::resource('category-pengeluarans', App\Http\Controllers\CategoryPengeluaranController::class)->middleware(['auth', 'tenant.feature:finance']);
    Route::controller(App\Http\Controllers\TagihanController::class)->group(function () {
        Route::get('invoice/{id}', 'invoice')->name('invoice.pdf');
    });
});

Route::resource('unit-satuans', App\Http\Controllers\UnitSatuanController::class)->middleware(['auth', 'tenant.feature:inventory']);
Route::resource('kategori-barangs', App\Http\Controllers\KategoriBarangController::class)->middleware(['auth', 'tenant.feature:inventory']);
Route::resource('barangs', App\Http\Controllers\BarangController::class)->middleware(['auth', 'tenant.feature:inventory']);
Route::get('inventory-master', [App\Http\Controllers\InventoryMasterHubController::class, 'index'])
    ->name('inventory-master.index')
    ->middleware(['auth', 'tenant.feature:inventory']);
Route::get('inventory-transactions', [App\Http\Controllers\InventoryTransactionHubController::class, 'index'])
    ->name('inventory-transactions.index')
    ->middleware(['auth', 'tenant.feature:inventory']);
Route::get('inventory-hub', [App\Http\Controllers\InventoryHubController::class, 'index'])
    ->name('inventory-hub.index')
    ->middleware(['auth', 'tenant.feature:inventory']);
Route::resource('setting-webs', App\Http\Controllers\SettingWebController::class)->middleware(['auth', 'tenant.feature:settings']);

Route::resource('tiket-aduans', App\Http\Controllers\TiketAduanController::class)->middleware(['auth', 'tenant.feature:cms']);

Route::resource('config-pesan-notifs', App\Http\Controllers\ConfigPesanNotifController::class)->middleware(['auth', 'tenant.feature:whatsapp']);
Route::resource('banner-managements', App\Http\Controllers\BannerManagementController::class)->middleware(['auth', 'tenant.feature:cms']);
Route::resource('informasi-managements', App\Http\Controllers\InformasiManagementController::class)->middleware(['auth', 'tenant.feature:cms']);
Route::resource('balance-histories', App\Http\Controllers\BalanceHistoryController::class)->only(['index'])->middleware(['auth', 'tenant.feature:investor']);

Route::resource('withdraws', App\Http\Controllers\WithdrawController::class)->middleware('tenant.feature:investor');
Route::post('withdraws/{withdraw}/approve', [App\Http\Controllers\WithdrawController::class, 'approve'])->name('withdraws.approve');

Route::resource('topups', App\Http\Controllers\TopupController::class)->middleware(['auth', 'tenant.feature:finance']);
Route::post('topups/approve', [App\Http\Controllers\TopupController::class, 'approve'])->name('topups.approve')->middleware('auth');

Route::resource('olts', App\Http\Controllers\OltController::class)->middleware(['auth', 'tenant.feature:olt']);

Route::get('/investor', [App\Http\Controllers\InvestorController::class, 'index'])
    ->name('investor.index')
    ->middleware(['auth', 'permission:investor view', 'tenant.feature:investor']);

Route::get('/investor-admin', [App\Http\Controllers\InvestorAdminDashboardController::class, 'index'])
    ->name('investor-admin.index')
    ->middleware(['auth', 'permission:investor rule manage', 'tenant.feature:investor']);
Route::get('/investor-inventory', [App\Http\Controllers\InvestorInventoryController::class, 'index'])
    ->name('investor-inventory.index')
    ->middleware(['auth', 'permission:investor view', 'tenant.feature:investor']);

Route::get('/mikrotik-automation', [App\Http\Controllers\MikrotikAutomationController::class, 'index'])
    ->name('mikrotik-automation.index')
    ->middleware(['auth', 'permission:mikrotik automation view', 'tenant.feature:network']);
Route::post('/mikrotik-automation/settings', [App\Http\Controllers\MikrotikAutomationController::class, 'saveSettings'])
    ->name('mikrotik-automation.settings')
    ->middleware(['auth', 'permission:mikrotik automation manage', 'tenant.feature:network']);
Route::post('/mikrotik-automation/run-now', [App\Http\Controllers\MikrotikAutomationController::class, 'runNow'])
    ->name('mikrotik-automation.run-now')
    ->middleware(['auth', 'permission:mikrotik automation execute']);
Route::post('/mikrotik-automation/manual-execute', [App\Http\Controllers\MikrotikAutomationController::class, 'manualExecute'])
    ->name('mikrotik-automation.manual-execute')
    ->middleware(['auth', 'permission:mikrotik automation execute']);
Route::get('/mikrotik-automation/logs', [App\Http\Controllers\MikrotikAutomationController::class, 'logs'])
    ->name('mikrotik-automation.logs')
    ->middleware(['auth', 'permission:mikrotik automation log view']);

Route::get('/audit-pelanggan', [App\Http\Controllers\AuditPelangganController::class, 'index'])
    ->name('audit-pelanggan.index')
    ->middleware(['auth', 'permission:audit pelanggan view', 'tenant.feature:audit']);
Route::get('/audit-pelanggan/export/pdf', [App\Http\Controllers\AuditPelangganController::class, 'exportPdf'])
    ->name('audit-pelanggan.export.pdf')
    ->middleware(['auth', 'permission:audit pelanggan export', 'tenant.feature:audit']);

Route::get('investor-share-rules/{id}/customers', [App\Http\Controllers\InvestorShareRuleController::class, 'customers'])
    ->name('investor-share-rules.customers')
    ->middleware(['auth', 'permission:investor rule manage']);
Route::post('investor-share-rules/{id}/customers', [App\Http\Controllers\InvestorShareRuleController::class, 'customersUpdate'])
    ->name('investor-share-rules.customers.update')
    ->middleware(['auth', 'permission:investor rule manage']);
Route::get('investor-share-rules/{id}/backfill', [App\Http\Controllers\InvestorShareRuleController::class, 'backfill'])
    ->name('investor-share-rules.backfill')
    ->middleware(['auth', 'permission:investor rule manage']);
Route::post('investor-share-rules/{id}/backfill', [App\Http\Controllers\InvestorShareRuleController::class, 'backfillRun'])
    ->name('investor-share-rules.backfill.run')
    ->middleware(['auth', 'permission:investor rule manage']);
Route::resource('investor-share-rules', App\Http\Controllers\InvestorShareRuleController::class)->middleware(['auth', 'permission:investor rule manage']);

Route::get('investor-payout-requests', [App\Http\Controllers\InvestorPayoutApprovalController::class, 'index'])
    ->name('investor-payout-requests.index')
    ->middleware(['auth', 'permission:investor payout approve']);
Route::post('investor-payout-requests/{id}/approve', [App\Http\Controllers\InvestorPayoutApprovalController::class, 'approve'])
    ->name('investor-payout-requests.approve')
    ->middleware(['auth', 'permission:investor payout approve']);

Route::get('investor-payouts', [App\Http\Controllers\InvestorPayoutRequestController::class, 'index'])
    ->name('investor-payouts.index')
    ->middleware(['auth', 'permission:investor payout request']);
Route::post('investor-payouts', [App\Http\Controllers\InvestorPayoutRequestController::class, 'store'])
    ->name('investor-payouts.store')
    ->middleware(['auth', 'permission:investor payout request']);

Route::get('investor-payout-account', [App\Http\Controllers\InvestorPayoutAccountController::class, 'edit'])
    ->name('investor-payout-account.index')
    ->middleware(['auth', 'permission:investor payout request']);
Route::post('investor-payout-account', [App\Http\Controllers\InvestorPayoutAccountController::class, 'update'])
    ->name('investor-payout-account.update')
    ->middleware(['auth', 'permission:investor payout request']);

Route::get('/investor-hub', [App\Http\Controllers\InvestorHubController::class, 'index'])
    ->name('investor-hub.index')
    ->middleware('auth');

Route::resource('hr-employees', App\Http\Controllers\HrEmployeeController::class)->except(['show'])
    ->middleware(['auth', 'permission:attendance manage']);
Route::resource('hr-jabatans', App\Http\Controllers\HrJabatanController::class)->except(['show'])
    ->middleware(['auth', 'permission:attendance manage']);
Route::resource('hr-work-schemes', App\Http\Controllers\HrWorkSchemeController::class)->except(['show'])
    ->middleware(['auth', 'permission:attendance manage']);
Route::post('hr-work-schemes/{hr_work_scheme}/weekend-off/{day}', [App\Http\Controllers\HrWorkSchemeController::class, 'weekendOff'])
    ->name('hr-work-schemes.weekend-off')
    ->middleware(['auth', 'permission:attendance manage']);
Route::resource('hr-shifts', App\Http\Controllers\HrShiftController::class)->except(['show'])
    ->middleware(['auth', 'permission:attendance manage']);
Route::resource('hr-shift-rosters', App\Http\Controllers\HrShiftRosterController::class)->except(['show'])
    ->middleware(['auth', 'permission:attendance manage']);
Route::resource('hr-attendances', App\Http\Controllers\HrAttendanceController::class)
    ->middleware(['auth', 'permission:attendance view|attendance manage']);
Route::post('hr-attendances/{hr_attendance}/approve', [App\Http\Controllers\HrAttendanceController::class, 'approve'])
    ->name('hr-attendances.approve')
    ->middleware(['auth', 'permission:attendance manage']);
Route::post('hr-attendances/{hr_attendance}/reject', [App\Http\Controllers\HrAttendanceController::class, 'reject'])
    ->name('hr-attendances.reject')
    ->middleware(['auth', 'permission:attendance manage']);
Route::get('hr-overtime-approvals', [App\Http\Controllers\HrOvertimeApprovalController::class, 'index'])
    ->name('hr-overtime-approvals.index')
    ->middleware(['auth', 'permission:attendance manage']);
Route::post('hr-overtime-approvals/{session}/approve', [App\Http\Controllers\HrOvertimeApprovalController::class, 'approve'])
    ->name('hr-overtime-approvals.approve')
    ->middleware(['auth', 'permission:attendance manage']);
Route::post('hr-overtime-approvals/{session}/reject', [App\Http\Controllers\HrOvertimeApprovalController::class, 'reject'])
    ->name('hr-overtime-approvals.reject')
    ->middleware(['auth', 'permission:attendance manage']);
Route::post('hr-attendances/{hr_attendance}/tracks/import', [App\Http\Controllers\HrAttendanceController::class, 'importTracks'])
    ->name('hr-attendances.tracks.import')
    ->middleware(['auth', 'permission:attendance manage']);
Route::post('hr-attendances/{hr_attendance}/tracks/clear', [App\Http\Controllers\HrAttendanceController::class, 'clearTracks'])
    ->name('hr-attendances.tracks.clear')
    ->middleware(['auth', 'permission:attendance manage']);
Route::get('hr-attendances/tracks/sample.csv', [App\Http\Controllers\HrAttendanceController::class, 'sampleCsv'])
    ->name('hr-attendances.tracks.sample')
    ->middleware(['auth', 'permission:attendance view|attendance manage']);
Route::post('hr-attendances/{hr_attendance}/notes', [App\Http\Controllers\HrAttendanceController::class, 'addNote'])
    ->name('hr-attendances.notes.store')
    ->middleware(['auth', 'permission:attendance manage']);
Route::delete('hr-attendances/{hr_attendance}/notes/{note}', [App\Http\Controllers\HrAttendanceController::class, 'deleteNote'])
    ->name('hr-attendances.notes.destroy')
    ->middleware(['auth', 'permission:attendance manage']);

Route::get('hr-attendances-live', [App\Http\Controllers\HrAttendanceLiveController::class, 'index'])
    ->name('hr-attendances-live.index')
    ->middleware(['auth', 'permission:attendance view|attendance manage']);
Route::get('hr-attendances-live/data', [App\Http\Controllers\HrAttendanceLiveController::class, 'data'])
    ->name('hr-attendances-live.data')
    ->middleware(['auth', 'permission:attendance view|attendance manage']);
Route::get('hr-attendances-live/session/{session}', [App\Http\Controllers\HrAttendanceLiveController::class, 'session'])
    ->name('hr-attendances-live.session')
    ->middleware(['auth', 'permission:attendance view|attendance manage']);

Route::resource('hr-attendance-sites', App\Http\Controllers\HrAttendanceSiteController::class)->except(['show'])
    ->middleware(['auth', 'permission:attendance manage']);
Route::resource('hr-holidays', App\Http\Controllers\HrHolidayController::class)->except(['show'])
    ->middleware(['auth', 'permission:attendance manage']);

Route::resource('hr-operational-dailies', App\Http\Controllers\HrOperationalDailyController::class)->except(['show', 'create'])
    ->middleware(['auth', 'permission:attendance payroll']);
Route::resource('hr-operational-rules', App\Http\Controllers\HrOperationalRuleController::class)->except(['show', 'create'])
    ->middleware(['auth', 'permission:attendance payroll']);
Route::get('hr-operational', [App\Http\Controllers\HrOperationalHubController::class, 'index'])
    ->name('hr-operational.index')
    ->middleware(['auth', 'permission:attendance payroll']);
Route::resource('hr-sanctions', App\Http\Controllers\HrSanctionController::class)->except(['show', 'create'])
    ->middleware(['auth', 'permission:attendance payroll']);

Route::resource('hr-payroll-periods', App\Http\Controllers\HrPayrollPeriodController::class)->only(['index', 'create', 'store', 'show'])
    ->middleware(['auth', 'permission:attendance payroll']);
Route::post('hr-payroll-periods/{hr_payroll_period}/generate', [App\Http\Controllers\HrPayrollPeriodController::class, 'generate'])
    ->name('hr-payroll-periods.generate')
    ->middleware(['auth', 'permission:attendance payroll']);
Route::post('hr-payroll-periods/{hr_payroll_period}/lock', [App\Http\Controllers\HrPayrollPeriodController::class, 'lock'])
    ->name('hr-payroll-periods.lock')
    ->middleware(['auth', 'permission:attendance payroll']);
Route::get('hr-payroll-periods/{hr_payroll_period}/export-pdf', [App\Http\Controllers\HrPayrollPeriodController::class, 'exportPdf'])
    ->name('hr-payroll-periods.export-pdf')
    ->middleware(['auth', 'permission:attendance payroll']);
Route::post('hr-payroll-periods/{hr_payroll_period}/post-keuangan', [App\Http\Controllers\HrPayrollPeriodController::class, 'postToFinance'])
    ->name('hr-payroll-periods.post-keuangan')
    ->middleware(['auth', 'permission:attendance payroll']);

Route::resource('hr-deductions', App\Http\Controllers\HrDeductionController::class)->except(['show', 'create'])
    ->middleware(['auth', 'permission:attendance payroll']);
Route::get('hr-potongan', [App\Http\Controllers\HrPotonganHubController::class, 'index'])
    ->name('hr-potongan.index')
    ->middleware(['auth', 'permission:attendance payroll']);

Route::get('hr-hub', [App\Http\Controllers\HrHubController::class, 'index'])
    ->name('hr-hub.index')
    ->middleware(['auth', 'permission:attendance view|attendance manage|attendance payroll']);

Route::resource('hr-kasbons', App\Http\Controllers\HrKasbonController::class)->only(['index', 'store', 'show', 'destroy'])
    ->middleware(['auth', 'permission:attendance payroll']);
Route::post('hr-kasbons/{hr_kasbon}/repayments', [App\Http\Controllers\HrKasbonController::class, 'addRepayment'])
    ->name('hr-kasbons.repayments.store')
    ->middleware(['auth', 'permission:attendance payroll']);
