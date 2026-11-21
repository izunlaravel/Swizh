<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Panel Persetujuan Admin Swizh</h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 underline">← Ke Dashboard</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-green-100 border border-green-400 text-green-800 rounded-md text-sm font-medium">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-indigo-600">
                    <p class="text-xs text-gray-500 uppercase">Total SP Global ({{ $period }})</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($globalSpMonth, 0, ',', '.') }} SP</p>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm border-l-4 border-green-600">
                    <p class="text-xs text-gray-500 uppercase">Pool Bonus Leader 5% ({{ $period }})</p>
                    <p class="text-2xl font-bold text-green-700 mt-1">Rp {{ number_format($leaderPoolRp, 0, ',', '.') }}</p>

cd /workspaces/Swizh && cat << 'EOF' > routes/web.php
<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminSellerController;
use App\Http\Controllers\MemberSellerController;
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

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/sellers', [AdminSellerController::class, 'index'])->name('sellers.index');
        Route::post('/sellers/{id}/approve', [AdminSellerController::class, 'approveSeller'])->name('sellers.approve');
        Route::patch('/sellers/{id}/approve-sp', [AdminSellerController::class, 'approveSpChange'])->name('sellers.approve_sp');
        Route::post('/simulate-transaction', [AdminSellerController::class, 'simulateTransaction'])->name('sellers.simulate');
    });
});

require __DIR__.'/auth.php';
