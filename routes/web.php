<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminSellerController;
use App\Http\Controllers\MemberSellerController;
use App\Http\Controllers\QrisPaymentController;
use App\Http\Controllers\NearbySellerController;
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

    Route::post('/seller/apply', [MemberSellerController::class, 'apply'])->name('member.seller.apply');
    Route::post('/seller/request-sp', [MemberSellerController::class, 'requestSpChange'])->name('member.seller.request_sp');
    Route::post('/seller/location', [MemberSellerController::class, 'updateLocation'])->name('member.seller.location');

    Route::get('/qris/scan', [QrisPaymentController::class, 'scanPage'])->name('qris.scan');
    Route::post('/qris/pay', [QrisPaymentController::class, 'processPayment'])->name('qris.pay');
    Route::get('/tenants/nearby', [NearbySellerController::class, 'index'])->name('sellers.nearby');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/sellers', [AdminSellerController::class, 'index'])->name('sellers.index');
        Route::post('/sellers/{id}/approve', [AdminSellerController::class, 'approveSeller'])->name('sellers.approve');
        Route::patch('/sellers/{id}/approve-sp', [AdminSellerController::class, 'approveSpChange'])->name('sellers.approve_sp');
        Route::post('/simulate-transaction', [AdminSellerController::class, 'simulateTransaction'])->name('sellers.simulate');
        Route::post('/run-closing', [AdminSellerController::class, 'runClosing'])->name('closing.run');
        Route::post('/update-promo', [AdminSellerController::class, 'updateLeaderPromo'])->name('promo.update');
    });
});

require __DIR__.'/auth.php';
