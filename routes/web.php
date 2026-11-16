<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Digital\DigitalOrderController;
use App\Http\Controllers\Executive\BudgetController;
use App\Http\Controllers\Executive\ExecutiveDashboardController;
use App\Http\Controllers\FieldSales\SfaController;
use App\Http\Controllers\Finance\FinanceReportController;
use App\Http\Controllers\Finance\InterCompanyController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Master\BagianController;
use App\Http\Controllers\Master\BrandReportController;
use App\Http\Controllers\Master\CustomerMasterController;
use App\Http\Controllers\Master\DiscountFixedController;
use App\Http\Controllers\Master\KemasController;
use App\Http\Controllers\Master\StoreMasterController;
use App\Http\Controllers\Master\SupplierMasterController;
use App\Http\Controllers\Procurement\PurchaseOrderController;
use App\Http\Controllers\Procurement\RopAlertController;
use App\Http\Controllers\PivotReportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Retail\DailySettlementController;
use App\Http\Controllers\Retail\KasController;
use App\Http\Controllers\Retail\OperatingExpenseController;
use App\Http\Controllers\Retail\StoreOrderController;
use App\Http\Controllers\Retail\ReturnPurchaseController;
use App\Http\Controllers\Retail\ReturnSaleController;
use App\Http\Controllers\Retail\StockAdjustmentController;
use App\Http\Controllers\Retail\StockOpnameController;
use App\Http\Controllers\Master\PaketController;
use App\Http\Controllers\Master\UserMasterController;
use App\Http\Controllers\Retail\InventoryController;
use App\Http\Controllers\Retail\PayableController;
use App\Http\Controllers\Retail\ReceivableController;
use App\Http\Controllers\Retail\PointExchangeController;
use App\Http\Controllers\Retail\SaleDeletionHistoryController;
use App\Http\Controllers\Retail\SaleVoidRequestController;
use App\Http\Controllers\Master\CrewMasterController;
use App\Http\Controllers\Master\DiscountPromoController;
use App\Http\Controllers\Master\MemberMasterController;
use App\Http\Controllers\Sales\SaleDraftController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\Utility\MaintenanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/context/branch', [DashboardController::class, 'switchBranch'])->name('context.branch');

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{id}', [ProductController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
        Route::post('/search', [ProductController::class, 'search'])->name('search');
        Route::get('/export/csv', [ProductController::class, 'export'])->name('export');
    });

    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('index');
        Route::get('/create', [SaleController::class, 'create'])->name('create');
        Route::post('/', [SaleController::class, 'store'])->name('store');
        Route::post('/search-product', [SaleController::class, 'searchProduct'])->name('search-product');
        Route::post('/search-customer', [SaleController::class, 'searchCustomer'])->name('search-customer');
        Route::post('/search-member', [SaleController::class, 'searchMember'])->name('search-member');
        Route::post('/search-crew', [SaleController::class, 'searchCrew'])->name('search-crew');
        Route::post('/calculate-discount', [SaleController::class, 'calculateDiscount'])->name('calculate-discount');
        Route::get('/drafts', [SaleDraftController::class, 'index'])->name('drafts.index');
        Route::post('/drafts', [SaleDraftController::class, 'store'])->name('drafts.store');
        Route::get('/drafts/{draft}', [SaleDraftController::class, 'show'])->name('drafts.show');
        Route::delete('/drafts/{draft}', [SaleDraftController::class, 'destroy'])->name('drafts.destroy');
        Route::get('/paket/{paket}/lines', [SaleController::class, 'loadPaket'])->name('paket-lines');
        Route::get('/void-requests', [SaleVoidRequestController::class, 'index'])->name('void-requests.index');
        Route::get('/deletion-history', [SaleDeletionHistoryController::class, 'index'])->name('deletion-history');
        Route::get('/{id}/print', [SaleController::class, 'print'])->name('print');
        Route::post('/{id}/thermal', [SaleController::class, 'thermal'])->name('thermal');
        Route::get('/{id}', [SaleController::class, 'show'])->name('show');
        Route::post('/{sale}/void-request', [SaleVoidRequestController::class, 'store'])->name('void-request');
        Route::post('/void-requests/{requestId}/approve', [SaleVoidRequestController::class, 'approve'])->name('void-approve');
    });

    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseController::class, 'create'])->name('create');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
        Route::post('/search-supplier', [PurchaseController::class, 'searchSupplier'])->name('search-supplier');
        Route::post('/search-product', [PurchaseController::class, 'searchProduct'])->name('search-product');
        Route::get('/{id}/edit', [PurchaseController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PurchaseController::class, 'update'])->name('update');
        Route::get('/{id}/print', [PurchaseController::class, 'print'])->name('print');
        Route::get('/{id}', [PurchaseController::class, 'show'])->name('show');
    });

    Route::prefix('master')->name('master.')->group(function () {
        Route::get('/kemas', [KemasController::class, 'index'])->name('kemas.index');
        Route::post('/kemas', [KemasController::class, 'store'])->name('kemas.store');
        Route::put('/kemas/{kema}', [KemasController::class, 'update'])->name('kemas.update');
        Route::delete('/kemas/{kema}', [KemasController::class, 'destroy'])->name('kemas.destroy');

        Route::get('/suppliers', [SupplierMasterController::class, 'index'])->name('suppliers.index');
        Route::post('/suppliers', [SupplierMasterController::class, 'store'])->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [SupplierMasterController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierMasterController::class, 'destroy'])->name('suppliers.destroy');

        Route::get('/customers', [CustomerMasterController::class, 'index'])->name('customers.index');
        Route::post('/customers', [CustomerMasterController::class, 'store'])->name('customers.store');
        Route::put('/customers/{customer}', [CustomerMasterController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerMasterController::class, 'destroy'])->name('customers.destroy');

        Route::get('/stores', [StoreMasterController::class, 'index'])->name('stores.index');
        Route::put('/stores/{branch}', [StoreMasterController::class, 'update'])->name('stores.update');

        Route::get('/bagian', [BagianController::class, 'index'])->name('bagian.index');
        Route::post('/bagian', [BagianController::class, 'store'])->name('bagian.store');
        Route::put('/bagian/{bagian}', [BagianController::class, 'update'])->name('bagian.update');
        Route::delete('/bagian/{bagian}', [BagianController::class, 'destroy'])->name('bagian.destroy');

        Route::get('/paket', [PaketController::class, 'index'])->name('paket.index');
        Route::post('/paket', [PaketController::class, 'store'])->name('paket.store');
        Route::get('/paket/{paket}', [PaketController::class, 'show'])->name('paket.show');
        Route::put('/paket/{paket}', [PaketController::class, 'update'])->name('paket.update');
        Route::delete('/paket/{paket}', [PaketController::class, 'destroy'])->name('paket.destroy');
        Route::post('/paket/{paket}/lines', [PaketController::class, 'storeLine'])->name('paket.lines.store');
        Route::delete('/paket-lines/{line}', [PaketController::class, 'destroyLine'])->name('paket.lines.destroy');

        Route::get('/discount-fixed', [DiscountFixedController::class, 'index'])->name('discount-fixed.index');
        Route::post('/discount-fixed', [DiscountFixedController::class, 'store'])->name('discount-fixed.store');
        Route::put('/discount-fixed/{discountFixed}', [DiscountFixedController::class, 'update'])->name('discount-fixed.update');
        Route::delete('/discount-fixed/{discountFixed}', [DiscountFixedController::class, 'destroy'])->name('discount-fixed.destroy');

        Route::get('/brand-report', [BrandReportController::class, 'index'])->name('brand-report.index');
        Route::post('/brand-report', [BrandReportController::class, 'store'])->name('brand-report.store');
        Route::put('/brand-report/{brandReport}', [BrandReportController::class, 'update'])->name('brand-report.update');
        Route::delete('/brand-report/{brandReport}', [BrandReportController::class, 'destroy'])->name('brand-report.destroy');

        Route::get('/discount-promo', [DiscountPromoController::class, 'index'])->name('discount-promo.index');
        Route::post('/discount-promo', [DiscountPromoController::class, 'store'])->name('discount-promo.store');
        Route::post('/discount-promo/search-products', [DiscountPromoController::class, 'searchProducts'])->name('discount-promo.search-products');
        Route::put('/discount-promo/{discountPromo}', [DiscountPromoController::class, 'update'])->name('discount-promo.update');
        Route::delete('/discount-promo/{discountPromo}', [DiscountPromoController::class, 'destroy'])->name('discount-promo.destroy');

        Route::get('/members', [MemberMasterController::class, 'index'])->name('members.index');
        Route::get('/members/export', [MemberMasterController::class, 'export'])->name('members.export');
        Route::get('/members/print', [MemberMasterController::class, 'print'])->name('members.print');
        Route::post('/members', [MemberMasterController::class, 'store'])->name('members.store');
        Route::put('/members/{member}', [MemberMasterController::class, 'update'])->name('members.update');
        Route::delete('/members/{member}', [MemberMasterController::class, 'destroy'])->name('members.destroy');

        Route::get('/crew', [CrewMasterController::class, 'index'])->name('crew.index');
        Route::post('/crew', [CrewMasterController::class, 'store'])->name('crew.store');
        Route::put('/crew/{crew}', [CrewMasterController::class, 'update'])->name('crew.update');
        Route::delete('/crew/{crew}', [CrewMasterController::class, 'destroy'])->name('crew.destroy');

        Route::get('/users', [UserMasterController::class, 'index'])->name('users.index');
        Route::post('/users', [UserMasterController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserMasterController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserMasterController::class, 'destroy'])->name('users.destroy');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/sales-by-product', [ReportController::class, 'salesByProduct'])->name('sales-by-product');
        Route::get('/sales-by-customer', [ReportController::class, 'salesByCustomer'])->name('sales-by-customer');
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('/purchases-by-supplier', [ReportController::class, 'purchasesBySupplier'])->name('purchases-by-supplier');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/sales-profit', [ReportController::class, 'salesProfit'])->name('sales-profit');
        Route::get('/sales-profit/print', [ReportController::class, 'salesProfitPrint'])->name('sales-profit.print');
        Route::get('/receivable-collections', [ReportController::class, 'receivableProfit'])->name('receivable-collections');
        Route::get('/sales-by-section', [ReportController::class, 'salesBySection'])->name('sales-by-section');
        Route::get('/sales-by-payment', [ReportController::class, 'salesByPayment'])->name('sales-by-payment');
        Route::get('/sales-by-brand', [ReportController::class, 'salesByBrand'])->name('sales-by-brand');
        Route::get('/promo-summary', [ReportController::class, 'promoSummary'])->name('promo-summary');
        Route::get('/ar-ap', [ReportController::class, 'arAp'])->name('ar-ap');
        Route::get('/ar-ap/print', [ReportController::class, 'arApPrint'])->name('ar-ap.print');
        Route::get('/activity', [ReportController::class, 'activity'])->name('activity');
        Route::get('/activity/print', [ReportController::class, 'activityPrint'])->name('activity.print');
        Route::get('/recap', [ReportController::class, 'recap'])->name('recap');
        Route::get('/recap/print', [ReportController::class, 'recapPrint'])->name('recap.print');
        Route::get('/inventory-monthly', [ReportController::class, 'inventoryMonthly'])->name('inventory-monthly');
        Route::get('/member-sales', [ReportController::class, 'memberSales'])->name('member-sales');
        Route::get('/crew-sales', [ReportController::class, 'crewSales'])->name('crew-sales');
        Route::get('/pivot', [PivotReportController::class, 'index'])->name('pivot');
        Route::post('/pivot/favorites', [PivotReportController::class, 'storeFavorite'])->name('pivot.favorites.store');
        Route::delete('/pivot/favorites/{favorite}', [PivotReportController::class, 'destroyFavorite'])->name('pivot.favorites.destroy');
    });

    Route::prefix('retail')->name('retail.')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/legacy-stock', [InventoryController::class, 'legacyStock'])->name('legacy-stock.index');
        Route::get('/kas', [KasController::class, 'index'])->name('kas.index');
        Route::post('/kas', [KasController::class, 'store'])->name('kas.store');
        Route::get('/kas/print', [KasController::class, 'print'])->name('kas.print');
        Route::get('/expenses', [OperatingExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [OperatingExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/expenses/{expense}', [OperatingExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::get('/orders', [StoreOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [StoreOrderController::class, 'create'])->name('orders.create');
        Route::get('/orders/catalog', [StoreOrderController::class, 'catalog'])->name('orders.catalog');
        Route::get('/orders/search', [StoreOrderController::class, 'search'])->name('orders.search');
        Route::post('/orders/lines', [StoreOrderController::class, 'saveLines'])->name('orders.lines');
        Route::delete('/orders/lines/{lineId}', [StoreOrderController::class, 'destroyLine'])->name('orders.lines.destroy');
        Route::post('/orders/new-item', [StoreOrderController::class, 'newItem'])->name('orders.new-item');
        Route::get('/orders/sheet/{id}', [StoreOrderController::class, 'sheet'])->name('orders.sheet');
        Route::get('/orders/excel/{noPo}', [StoreOrderController::class, 'excel'])->name('orders.excel');
        Route::post('/orders', [StoreOrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/{orderId}/submit', [StoreOrderController::class, 'submit'])->name('orders.submit');
        Route::post('/orders/{orderId}/link-po', [StoreOrderController::class, 'linkPo'])->name('orders.link-po');
        Route::get('/orders/print/{noPo}', [StoreOrderController::class, 'print'])->name('orders.print');
        Route::get('/orders/lookup-product', [StoreOrderController::class, 'lookupProduct'])->name('orders.lookup-product');
        Route::get('/receivables', [ReceivableController::class, 'index'])->name('receivables.index');
        Route::post('/receivables/{sale}/pay', [ReceivableController::class, 'pay'])->name('receivables.pay');
        Route::get('/receivables/payments/{paymentId}/print', [ReceivableController::class, 'printPayment'])->name('receivables.payment.print');
        Route::get('/payables', [PayableController::class, 'index'])->name('payables.index');
        Route::post('/payables/{purchase}/pay', [PayableController::class, 'pay'])->name('payables.pay');
        Route::get('/payables/payments/{paymentId}/print', [PayableController::class, 'printPayment'])->name('payables.payment.print');
        Route::get('/returns/sales', [ReturnSaleController::class, 'index'])->name('returns.sales');
        Route::get('/returns/sales/lookup', [ReturnSaleController::class, 'lookupSale'])->name('returns.sales.lookup');
        Route::get('/returns/sales/search-items', [ReturnSaleController::class, 'searchSoldItems'])->name('returns.sales.search-items');
        Route::post('/returns/sales', [ReturnSaleController::class, 'store'])->name('returns.sales.store');
        Route::get('/returns/sales/print/{noRetur}', [ReturnSaleController::class, 'print'])->name('returns.sales.print');
        Route::get('/returns/purchases', [ReturnPurchaseController::class, 'index'])->name('returns.purchases');
        Route::get('/returns/purchases/lookup', [ReturnPurchaseController::class, 'lookupPurchase'])->name('returns.purchases.lookup');
        Route::get('/returns/purchases/search-lots', [ReturnPurchaseController::class, 'searchLots'])->name('returns.purchases.search-lots');
        Route::get('/returns/purchases/search-suppliers', [ReturnPurchaseController::class, 'searchSuppliers'])->name('returns.purchases.search-suppliers');
        Route::post('/returns/purchases', [ReturnPurchaseController::class, 'store'])->name('returns.purchases.store');
        Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
        Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
        Route::get('/opname', [StockOpnameController::class, 'index'])->name('opname.index');
        Route::get('/opname/catalog', [StockOpnameController::class, 'catalog'])->name('opname.catalog');
        Route::get('/opname/progress', [StockOpnameController::class, 'progress'])->name('opname.progress');
        Route::get('/opname/notes', [StockOpnameController::class, 'notes'])->name('opname.notes');
        Route::post('/opname/notes/{lineId}', [StockOpnameController::class, 'updateNote'])->name('opname.notes.update');
        Route::post('/opname/adjust', [StockOpnameController::class, 'adjust'])->name('opname.adjust');
        Route::get('/opname/history', [StockOpnameController::class, 'history'])->name('opname.history');
        Route::post('/opname', [StockOpnameController::class, 'store'])->name('opname.store');
        Route::post('/opname/{opnameId}/approve', [StockOpnameController::class, 'approve'])->name('opname.approve');
        Route::get('/settlements', [DailySettlementController::class, 'index'])->name('settlements.index');
        Route::post('/settlements', [DailySettlementController::class, 'store'])->name('settlements.store');
        Route::post('/settlements/{settlement}/approve', [DailySettlementController::class, 'approve'])->name('settlements.approve');
        Route::get('/points/exchange', [PointExchangeController::class, 'index'])->name('points.index');
        Route::post('/points/exchange', [PointExchangeController::class, 'store'])->name('points.store');
        Route::post('/points/search-member', [PointExchangeController::class, 'searchMember'])->name('points.search-member');
        Route::get('/points/history', [PointExchangeController::class, 'history'])->name('points.history');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/trial-balance', [FinanceReportController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/income-statement', [FinanceReportController::class, 'incomeStatement'])->name('income-statement');
        Route::get('/balance-sheet', [FinanceReportController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/inter-company', [InterCompanyController::class, 'index'])->name('inter-company.index');
        Route::post('/inter-company', [InterCompanyController::class, 'store'])->name('inter-company.store');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/str', [StockTransferController::class, 'index'])->name('str.index');
        Route::get('/str/create', [StockTransferController::class, 'create'])->name('str.create');
        Route::post('/str', [StockTransferController::class, 'store'])->name('str.store');
        Route::post('/str/{str}/approve', [StockTransferController::class, 'approve'])->name('str.approve');
        Route::post('/str/{str}/receive', [StockTransferController::class, 'receive'])->name('str.receive');
    });

    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('/po', [PurchaseOrderController::class, 'index'])->name('po.index');
        Route::get('/po/create', [PurchaseOrderController::class, 'create'])->name('po.create');
        Route::get('/po/search', [PurchaseOrderController::class, 'search'])->name('po.search');
        Route::post('/po', [PurchaseOrderController::class, 'store'])->name('po.store');
        Route::get('/rop', [RopAlertController::class, 'index'])->name('rop.index');
        Route::post('/rop/refresh', [RopAlertController::class, 'refresh'])->name('rop.refresh');
    });

    Route::prefix('digital')->name('digital.')->group(function () {
        Route::get('/orders', [DigitalOrderController::class, 'index'])->name('orders.index');
        Route::post('/orders/import', [DigitalOrderController::class, 'importSheet'])->name('orders.import');
        Route::post('/orders', [DigitalOrderController::class, 'store'])->name('orders.store');
        Route::post('/orders/{order}/ship', [DigitalOrderController::class, 'ship'])->name('orders.ship');
        Route::post('/orders/{order}/complete', [DigitalOrderController::class, 'complete'])->name('orders.complete');
    });

    Route::prefix('fieldsales')->name('fieldsales.')->group(function () {
        Route::get('/outlets', [SfaController::class, 'outlets'])->name('outlets.index');
        Route::post('/outlets', [SfaController::class, 'storeOutlet'])->name('outlets.store');
        Route::post('/orders', [SfaController::class, 'createOrder'])->name('orders.store');
        Route::post('/orders/{order}/deliver', [SfaController::class, 'deliver'])->name('orders.deliver');
        Route::post('/payments', [SfaController::class, 'recordPayment'])->name('payments.store');
    });

    Route::prefix('executive')->name('executive.')->group(function () {
        Route::get('/dashboard', [ExecutiveDashboardController::class, 'index'])->name('dashboard');
        Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
        Route::get('/budgets/create', [BudgetController::class, 'create'])->name('budgets.create');
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::get('/budgets/{budget}', [BudgetController::class, 'show'])->name('budgets.show');
        Route::get('/budgets/{budget}/edit', [BudgetController::class, 'edit'])->name('budgets.edit');
        Route::put('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
        Route::get('/budgets/{budget}/print', [BudgetController::class, 'print'])->name('budgets.print');
        Route::post('/budgets/{budget}/submit', [BudgetController::class, 'submit'])->name('budgets.submit');
        Route::post('/budgets/{budget}/validate', [BudgetController::class, 'validateBudget'])->name('budgets.validate');
        Route::post('/budgets/{budget}/approve', [BudgetController::class, 'approve'])->name('budgets.approve');
        Route::post('/budgets/{budget}/notes', [BudgetController::class, 'storeNote'])->name('budgets.notes');
        Route::post('/budgets/{budget}/actual', [BudgetController::class, 'actual'])->name('budgets.actual');
    });

    Route::prefix('utility')->name('utility.')->group(function () {
        Route::get('/', [MaintenanceController::class, 'index'])->name('index');
        Route::post('/business-date', [MaintenanceController::class, 'updateBusinessDate'])->name('business-date');
        Route::post('/backup', [MaintenanceController::class, 'backup'])->name('backup');
        Route::get('/barcode', [MaintenanceController::class, 'barcode'])->name('barcode');
        Route::post('/barcode/pick', [MaintenanceController::class, 'barcodePick'])->name('barcode.pick');
        Route::post('/barcode/reset', [MaintenanceController::class, 'barcodeReset'])->name('barcode.reset');
        Route::post('/barcode/print', [MaintenanceController::class, 'barcodePrint'])->name('barcode.print');
        Route::get('/expired', [MaintenanceController::class, 'expired'])->name('expired');
        Route::post('/expired', [MaintenanceController::class, 'updateExpiry'])->name('expired.update');
        Route::get('/dead-stock', [MaintenanceController::class, 'deadStock'])->name('dead-stock');
        Route::get('/dead-stock/print', [MaintenanceController::class, 'deadStockPrint'])->name('dead-stock.print');
        Route::get('/data-check', [MaintenanceController::class, 'dataCheck'])->name('data-check');
        Route::get('/set-bagian', [MaintenanceController::class, 'setBagian'])->name('set-bagian');
        Route::post('/set-bagian', [MaintenanceController::class, 'applyBagian'])->name('set-bagian.apply');
        Route::get('/settings', [MaintenanceController::class, 'settings'])->name('settings');
        Route::post('/settings', [MaintenanceController::class, 'saveSettings'])->name('settings.save');
        Route::get('/price-compare', [MaintenanceController::class, 'priceCompare'])->name('price-compare');
    });
});
