<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingPlanController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SubscriptionBillController;
use App\Http\Controllers\Api\UptimeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (mobile app)
|--------------------------------------------------------------------------
|
| Versioned JSON API used by the Flutter app in mobile/. Token auth via Sanctum:
| POST auth/login returns a bearer token; every other route needs it.
|
*/

Route::prefix('v1')->name('api.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');

        Route::get('products', [ProductController::class, 'index'])->name('products.index');

        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::post('invoices/{invoice}/transition', [InvoiceController::class, 'transition'])->name('invoices.transition');
        Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])->name('invoices.payments.store');
        Route::delete('payments/{payment}', [InvoiceController::class, 'destroyPayment'])->name('payments.destroy');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

        // Operations: uptime monitoring, subscription bills, recurring billing plans
        Route::get('uptime', [UptimeController::class, 'index'])->name('uptime.index');
        Route::get('uptime/{endpoint}', [UptimeController::class, 'show'])->name('uptime.show');
        Route::post('uptime/{endpoint}/check', [UptimeController::class, 'check'])->name('uptime.check');
        Route::get('subscriptions', [SubscriptionBillController::class, 'index'])->name('subscriptions.index');
        Route::post('subscriptions/{subscription}/mark-paid', [SubscriptionBillController::class, 'markPaid'])->name('subscriptions.mark-paid');
        Route::get('billing-plans', [BillingPlanController::class, 'index'])->name('billing-plans.index');
        Route::get('billing-plans/{billingPlan}', [BillingPlanController::class, 'show'])->name('billing-plans.show');
        Route::post('billing-plans/{billingPlan}/generate', [BillingPlanController::class, 'generate'])->name('billing-plans.generate');
        Route::post('billing-plans/{billingPlan}/status', [BillingPlanController::class, 'setStatus'])->name('billing-plans.status');
    });
});
