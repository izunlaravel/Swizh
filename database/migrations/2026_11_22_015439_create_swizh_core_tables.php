<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'wallet_balance')) {
                $table->bigInteger('wallet_balance')->default(0)->after('referred_by');
            }
            if (!Schema::hasColumn('users', 'is_seller')) {
                $table->boolean('is_seller')->default(false)->after('wallet_balance');
            }
            if (!Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('is_seller');
            }
        });

        if (!Schema::hasTable('sellers')) {
            Schema::create('sellers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('store_name');
                $table->decimal('sp_percentage', 5, 2)->default(10.00);
                $table->string('qris_payload')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sp_transactions')) {
            Schema::create('sp_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_code')->unique();
                $table->foreignId('buyer_id')->constrained('users');
                $table->foreignId('seller_id')->constrained('sellers');
                $table->bigInteger('transaction_amount');
                $table->bigInteger('sp_earned');
                $table->string('period_month', 7)->index();
                $table->enum('status', ['pending', 'paid', 'cancelled'])->default('paid');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bonus_ledgers')) {
            Schema::create('bonus_ledgers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('sp_transaction_id')->nullable()->constrained('sp_transactions')->nullOnDelete();
                $table->enum('bonus_type', [
                    'direct_40',
                    'ref_member_10',
                    'ref_seller_05',
                    'leader_share_5',
                    'reward_10'
                ]);
                $table->bigInteger('amount_rp');
                $table->string('period_month', 7)->index();
                $table->enum('status', ['pending', 'approved', 'expired'])->default('pending');
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_ledgers');
        Schema::dropIfExists('sp_transactions');
        Schema::dropIfExists('sellers');
    }
};
