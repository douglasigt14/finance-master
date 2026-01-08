<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CardsController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtorsController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\TransactionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Cards
    Route::resource('cards', CardsController::class);

    // Categories
    Route::resource('categories', CategoriesController::class);

    // Debtors
    Route::resource('debtors', DebtorsController::class);

    // Transactions
    Route::resource('transactions', TransactionsController::class);
    Route::post('/transactions/{id}/mark-paid', [TransactionsController::class, 'markAsPaid'])->name('transactions.mark-paid');
    Route::post('/transactions/{id}/mark-unpaid', [TransactionsController::class, 'markAsUnpaid'])->name('transactions.mark-unpaid');

    // Invoices
    Route::get('/invoices', [InvoicesController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/card/{cardId}', [InvoicesController::class, 'index'])->name('invoices.card');
    Route::get('/invoices/card/{cardId}/{month}/{year}', [InvoicesController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/card/{cardId}/{month}/{year}/mark-paid', [InvoicesController::class, 'markAsPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/card/{cardId}/{month}/{year}/mark-unpaid', [InvoicesController::class, 'markAsUnpaid'])->name('invoices.mark-unpaid');
    Route::post('/invoices/card/{cardId}/{month}/{year}/recalculate', [InvoicesController::class, 'recalculate'])->name('invoices.recalculate');
});
