<?php

use Illuminate\Support\Facades\Schedule;

// Jalankan tutup buku otomatis setiap tanggal 1 pukul 00:05 WIB
Schedule::command('swizh:monthly-closing')
    ->monthlyOn(1, '00:05')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
