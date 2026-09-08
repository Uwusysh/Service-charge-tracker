<?php

use App\Http\Controllers\ApportionmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetLineController;
use App\Http\Controllers\BudgetMonitorController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServiceChargeYearController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('portfolio.index')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/login/demo', [LoginController::class, 'demo'])->name('login.demo');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');

    Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('buildings', [BuildingController::class, 'index'])->name('buildings.index');
    Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('monitors', [BudgetMonitorController::class, 'index'])->name('monitors.index');
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');

    Route::middleware('role:administrator,block_manager')->group(function () {
        Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('buildings/create', [BuildingController::class, 'create'])->name('buildings.create');
        Route::post('buildings', [BuildingController::class, 'store'])->name('buildings.store');
        Route::get('budgets/create', [BudgetController::class, 'create'])->name('budgets.create');
        Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    });

    Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show')->whereNumber('client');
    Route::get('buildings/{building}', [BuildingController::class, 'show'])->name('buildings.show')->whereNumber('building');
    Route::get('schedules/{schedule}', [ScheduleController::class, 'show'])->name('schedules.show')->whereNumber('schedule');
    Route::get('budgets/{budget}', [BudgetController::class, 'show'])->name('budgets.show')->whereNumber('budget');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show')->whereNumber('invoice');
    Route::get('invoice-files/{invoiceFile}/download', [InvoiceController::class, 'download'])
        ->name('invoice-files.download')->whereNumber('invoiceFile');
    Route::get('monitors/{budget}', [BudgetMonitorController::class, 'show'])->name('monitors.show')->whereNumber('budget');

    Route::middleware('role:administrator,block_manager')->group(function () {
        Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit')->whereNumber('client');
        Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update')->whereNumber('client');
        Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy')->whereNumber('client');

        Route::get('buildings/{building}/edit', [BuildingController::class, 'edit'])->name('buildings.edit')->whereNumber('building');
        Route::put('buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update')->whereNumber('building');
        Route::delete('buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy')->whereNumber('building');

        Route::get('buildings/{building}/units/create', [UnitController::class, 'create'])->name('units.create')->whereNumber('building');
        Route::post('buildings/{building}/units', [UnitController::class, 'store'])->name('units.store')->whereNumber('building');
        Route::get('units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit')->whereNumber('unit');
        Route::put('units/{unit}', [UnitController::class, 'update'])->name('units.update')->whereNumber('unit');
        Route::delete('units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy')->whereNumber('unit');

        Route::get('buildings/{building}/years/create', [ServiceChargeYearController::class, 'create'])->name('years.create')->whereNumber('building');
        Route::post('buildings/{building}/years', [ServiceChargeYearController::class, 'store'])->name('years.store')->whereNumber('building');
        Route::get('years/{serviceChargeYear}/edit', [ServiceChargeYearController::class, 'edit'])->name('years.edit')->whereNumber('serviceChargeYear');
        Route::put('years/{serviceChargeYear}', [ServiceChargeYearController::class, 'update'])->name('years.update')->whereNumber('serviceChargeYear');

        Route::get('buildings/{building}/schedules/create', [ScheduleController::class, 'create'])->name('schedules.create')->whereNumber('building');
        Route::post('buildings/{building}/schedules', [ScheduleController::class, 'store'])->name('schedules.store')->whereNumber('building');
        Route::get('schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit')->whereNumber('schedule');
        Route::put('schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update')->whereNumber('schedule');
        Route::post('schedules/{schedule}/apportionment/equal', [ApportionmentController::class, 'applyEqual'])->name('apportionment.equal')->whereNumber('schedule');
        Route::post('schedules/{schedule}/apportionment/manual', [ApportionmentController::class, 'updateManual'])->name('apportionment.manual')->whereNumber('schedule');

        Route::post('budgets/{budget}/copy', [BudgetController::class, 'copy'])->name('budgets.copy')->whereNumber('budget');
        Route::post('budgets/{budget}/mark-ready', [BudgetController::class, 'markReady'])->name('budgets.mark-ready')->whereNumber('budget');
        Route::post('budgets/{budget}/lines', [BudgetLineController::class, 'store'])->name('budget-lines.store')->whereNumber('budget');
        Route::put('budget-lines/{budgetLine}', [BudgetLineController::class, 'update'])->name('budget-lines.update')->whereNumber('budgetLine');
        Route::delete('budget-lines/{budgetLine}', [BudgetLineController::class, 'destroy'])->name('budget-lines.destroy')->whereNumber('budgetLine');

        Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit')->whereNumber('supplier');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update')->whereNumber('supplier');

        Route::post('invoices/{invoice}/submit', [InvoiceController::class, 'submit'])->name('invoices.submit')->whereNumber('invoice');
    });

    Route::middleware('role:administrator,block_manager,accountant')->group(function () {
        Route::get('budgets/{budget}/edit', [BudgetController::class, 'edit'])->name('budgets.edit')->whereNumber('budget');
        Route::put('budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update')->whereNumber('budget');
    });

    Route::middleware('role:administrator,accountant')->group(function () {
        Route::post('budgets/{budget}/finalise', [BudgetController::class, 'finalise'])->name('budgets.finalise')->whereNumber('budget');
        Route::post('budgets/{budget}/archive', [BudgetController::class, 'archive'])->name('budgets.archive')->whereNumber('budget');
        Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve')->whereNumber('invoice');
        Route::post('invoices/{invoice}/reject', [InvoiceController::class, 'reject'])->name('invoices.reject')->whereNumber('invoice');
        Route::post('invoices/{invoice}/reverse', [InvoiceController::class, 'reverse'])->name('invoices.reverse')->whereNumber('invoice');
        Route::get('budgets/{budget}/export/excel', [ExportController::class, 'excel'])->name('exports.excel')->whereNumber('budget');
        Route::get('budgets/{budget}/export/pdf', [ExportController::class, 'pdf'])->name('exports.pdf')->whereNumber('budget');
    });

    Route::middleware('role:administrator')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});
