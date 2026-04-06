<?php

use App\Http\Controllers\BrandingSettingsController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientRepositoryController;
use App\Http\Controllers\ClientServerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonitoredEndpointController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PricingPresetController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubscriptionBillController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Clients
    Route::resource('clients', ClientController::class);
    Route::get('/clients/{client}/statement', [ClientController::class, 'statement'])->name('clients.statement');
    Route::get('/clients/{client}/statement/download', [ClientController::class, 'statementDownload'])->name('clients.statement.download');
    Route::post('/clients/{client}/presets', [PricingPresetController::class, 'store'])->name('presets.store');
    Route::put('/clients/{client}/presets/{preset}', [PricingPresetController::class, 'update'])->name('presets.update');
    Route::delete('/clients/{client}/presets/{preset}', [PricingPresetController::class, 'destroy'])->name('presets.destroy');

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::get('/invoices-download-all', [InvoiceController::class, 'downloadAll'])->name('invoices.download-all');
    Route::post('/invoices/{invoice}/transition', [InvoiceController::class, 'transition'])->name('invoices.transition');
    Route::post('/invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::post('/invoices/{invoice}/apply-preset', [InvoiceController::class, 'applyPreset'])->name('invoices.apply-preset');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/pdf/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf.download');

    // Payments
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    // Client Repositories
    Route::post('/clients/{client}/repositories', [ClientRepositoryController::class, 'store'])->name('repositories.store');
    Route::delete('/clients/{client}/repositories/{repository}', [ClientRepositoryController::class, 'destroy'])->name('repositories.destroy');
    Route::get('/api/github/repos', [ClientRepositoryController::class, 'githubRepos'])->name('api.github.repos');
    Route::get('/api/github/branches', [ClientRepositoryController::class, 'githubBranches'])->name('api.github.branches');

    // Client Servers (SSH)
    Route::post('/clients/{client}/servers', [ClientServerController::class, 'store'])->name('servers.store');
    Route::delete('/clients/{client}/servers/{server}', [ClientServerController::class, 'destroy'])->name('servers.destroy');
    Route::post('/api/test-ssh', [ClientServerController::class, 'testConnection'])->name('api.test-ssh');

    // Reports
    Route::resource('reports', ReportController::class);
    Route::post('/reports/{report}/generate', [ReportController::class, 'generate'])->name('reports.generate');
    Route::post('/reports/{report}/regenerate', [ReportController::class, 'regenerate'])->name('reports.regenerate');
    Route::post('/reports/{report}/transition', [ReportController::class, 'transition'])->name('reports.transition');
    Route::post('/reports/{report}/send', [ReportController::class, 'send'])->name('reports.send');
    Route::put('/reports/{report}/summary', [ReportController::class, 'updateSummary'])->name('reports.update-summary');
    Route::put('/reports/{report}/server-summary', [ReportController::class, 'updateServerSummary'])->name('reports.update-server-summary');
    Route::post('/reports/{report}/link-invoice', [ReportController::class, 'linkInvoice'])->name('reports.link-invoice');
    Route::post('/reports/{report}/unlink-invoice', [ReportController::class, 'unlinkInvoice'])->name('reports.unlink-invoice');
    Route::get('/reports/{report}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    Route::get('/reports/{report}/pdf/download', [ReportController::class, 'downloadPdf'])->name('reports.pdf.download');
    Route::get('/api/clients/{client}/invoices', [ReportController::class, 'clientInvoices'])->name('api.client.invoices');

    // Uptime Monitoring
    Route::resource('uptime', MonitoredEndpointController::class);
    Route::post('/uptime/{endpoint}/check', [MonitoredEndpointController::class, 'check'])->name('uptime.check');

    // Subscriptions
    Route::resource('subscriptions', SubscriptionBillController::class)->except(['show']);
    Route::post('/subscriptions/{subscription}/mark-paid', [SubscriptionBillController::class, 'markPaid'])->name('subscriptions.mark-paid');

    // Admin only routes
    Route::middleware(['can:manage-settings'])->group(function () {
        Route::get('/settings/branding', [BrandingSettingsController::class, 'edit'])->name('settings.branding');
        Route::put('/settings/branding', [BrandingSettingsController::class, 'update'])->name('settings.branding.update');
    });

    Route::middleware(['can:manage-users'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
