<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminSellerController;
use App\Http\Controllers\MemberSellerController;
use App\Http\Controllers\QrisPaymentController;
use App\Http\Controllers\NearbySellerController;
use App\Http\Controllers\WithdrawController;
use Illuminate\Support\Facades\Route;

Route::get('/terms', function () { return view('terms'); })->name('terms');
Route::get('/', function () { return view('welcome'); });
Route::get('/dashboard', function () { return view('dashboard'); })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/photo', [MemberSellerController::class, 'updateProfilePhoto'])->name('member.profile.photo');

    Route::post('/seller/apply', [MemberSellerController::class, 'apply'])->name('member.seller.apply');
    Route::post('/seller/request-sp', [MemberSellerController::class, 'requestSpChange'])->name('member.seller.request_sp');
    Route::post('/seller/update-profile', [MemberSellerController::class, 'updateSellerProfile'])->name('member.seller.update_profile');
    Route::post('/seller/deposit', [MemberSellerController::class, 'topupCashDeposit'])->name('member.seller.deposit');
    Route::post('/seller/products', [MemberSellerController::class, 'uploadProduct'])->name('member.seller.product_upload');
    Route::delete('/seller/products/{id}', [MemberSellerController::class, 'deleteProduct'])->name('member.seller.product_delete');
    Route::post('/seller/{sellerId}/review', [MemberSellerController::class, 'postReview'])->name('seller.review');
    Route::post('/inbox/send', [MemberSellerController::class, 'sendInbox'])->name('member.inbox.send');

    Route::get('/qris/scan', [QrisPaymentController::class, 'scanPage'])->name('qris.scan');
    Route::post('/qris/pay', [QrisPaymentController::class, 'processPayment'])->middleware('throttle:15,1')->name('qris.pay');
    Route::get('/tenants/nearby', [NearbySellerController::class, 'index'])->name('sellers.nearby');
    Route::post('/withdraw', [WithdrawController::class, 'store'])->middleware('throttle:5,1')->name('withdraw.store');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/sellers', [AdminSellerController::class, 'index'])->name('sellers.index');
        Route::post('/sellers/{id}/approve', [AdminSellerController::class, 'approveSeller'])->name('sellers.approve');
        Route::patch('/sellers/{id}/approve-sp', [AdminSellerController::class, 'approveSpChange'])->name('sellers.approve_sp');
        Route::post('/sellers/{id}/approve-non-sp', [AdminSellerController::class, 'approveNonSpNotes'])->name('sellers.approve_non_sp');
        Route::post('/withdrawals/{id}', [AdminSellerController::class, 'processWithdraw'])->name('withdrawals.process');
        Route::post('/broadcast-promo', [AdminSellerController::class, 'storePromoBroadcast'])->name('promo.broadcast');
        Route::post('/simulate-transaction', [AdminSellerController::class, 'simulateTransaction'])->name('sellers.simulate');
        Route::post('/run-closing', [AdminSellerController::class, 'runClosing'])->name('closing.run');
        Route::post('/update-promo', [AdminSellerController::class, 'updateLeaderPromo'])->name('promo.update');
        Route::post('/update-logo', [AdminSellerController::class, 'updateLogo'])->name('logo.update');
    });
});

require __DIR__.'/auth.php';
