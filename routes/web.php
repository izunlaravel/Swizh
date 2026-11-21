<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/sellers', [\App\Http\Controllers\AdminSellerController::class, 'index'])->name('sellers.index');
    Route::post('/sellers', [\App\Http\Controllers\AdminSellerController::class, 'store'])->name('sellers.store');
    Route::patch('/sellers/{id}', [\App\Http\Controllers\AdminSellerController::class, 'updatePercentage'])->name('sellers.update');
    Route::post('/simulate-transaction', [\App\Http\Controllers\AdminSellerController::class, 'simulateTransaction'])->name('sellers.simulate');
});
Route::middleware(["auth"])->prefix("admin")->name("admin.")->group(function () { Route::get("/sellers", [\App\Http\Controllers\AdminSellerController::class, "index"])->name("sellers.index"); Route::post("/sellers", [\App\Http\Controllers\AdminSellerController::class, "store"])->name("sellers.store"); Route::patch("/sellers/{id}", [\App\Http\Controllers\AdminSellerController::class, "updatePercentage"])->name("sellers.update"); Route::post("/simulate-transaction", [\App\Http\Controllers\AdminSellerController::class, "simulateTransaction"])->name("sellers.simulate"); });
