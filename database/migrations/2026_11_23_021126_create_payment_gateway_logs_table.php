<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('gateway', 20); // midtrans atau xendit
            $table->foreignId('buyer_id')->constrained('users');
            $table->foreignId('seller_id')->constrained('sellers');
            $table->bigInteger('amount_rp');
            $table->bigInteger('non_sp_amount_rp')->default(0);
            $table->text('qris_string_or_url')->nullable();
            $table->enum('status', ['pending', 'paid', 'expired', 'failed'])->default('pending');
            $table->string('payout_status', 30)->default('waiting'); // waiting, sent, manual_required
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_logs');
    }
};
