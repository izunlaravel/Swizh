<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('value');
            $table->timestamps();
        });

        // Default: Promo 3 bulan pertama aktif (Syarat SP Grup = 1.000.000, berlaku s/d 3 bulan dari sekarang)
        DB::table('app_settings')->insert([
            ['key' => 'leader_promo_active', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'leader_promo_until_month', 'value' => now()->addMonths(2)->format('Y-m'), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'leader_promo_group_sp', 'value' => '1000000', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'leader_normal_group_sp', 'value' => '10000000', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
