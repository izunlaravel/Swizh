<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pembaruan Tabel Users (No HP Unique, Info Bank/Gateway Withdraw, T&C)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->unique()->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'bank_name')) {
                $table->string('bank_name', 50)->nullable()->after('wallet_balance');
                $table->string('bank_account_number', 50)->nullable()->after('bank_name');
                $table->string('bank_account_name', 100)->nullable()->after('bank_account_number');
            }
            if (!Schema::hasColumn('users', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable()->after('remember_token');
            }
        });

        // 2. Pembaruan Tabel Sellers (Kategori, Alamat, Deposit Cash, Catatan Non-SP, Akun Gateway)
        Schema::table('sellers', function (Blueprint $table) {
            if (!Schema::hasColumn('sellers', 'category')) {
                $table->string('category', 100)->default('Lainnya')->after('store_name');
                $table->text('address')->nullable()->after('category');
                $table->bigInteger('cash_deposit_balance')->default(0)->after('sp_percentage'); // Minimal 100.000 untuk terima Cash
                $table->text('non_sp_notes')->nullable()->after('cash_deposit_balance'); // Daftar produk tidak kena SP
                $table->text('requested_non_sp_notes')->nullable()->after('non_sp_notes'); // Menunggu approve Admin
                $table->string('gateway_account_id')->nullable()->after('requested_non_sp_notes'); // ID Sub-account Midtrans/Xendit
                $table->string('bank_info')->nullable()->after('gateway_account_id');
            }
        });

        // 3. Tambah metode pembayaran di sp_transactions (qris, wallet, cash_deposit)
        Schema::table('sp_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('sp_transactions', 'payment_method')) {
                $table->string('payment_method', 30)->default('qris_gateway')->after('sp_earned');
            }
        });

        // 4. Tabel Produk Unggulan Seller (Maksimal 5 foto @5MB)
        Schema::create('seller_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->string('product_name');
            $table->string('product_type')->nullable();
            $table->bigInteger('price_rp')->default(0);
            $table->string('photo_path');
            $table->boolean('is_sp_eligible')->default(true);
            $table->timestamps();
        });

        // 5. Tabel Permohonan Withdraw Member (Saldo > 50.000, 08.00-10.00 WIB)
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->bigInteger('amount_rp'); // Nominal diminta
            $table->bigInteger('admin_fee_rp')->default(2500); // Biaya payment gateway ditanggung member
            $table->bigInteger('net_amount_rp'); // Nominal bersih diterima
            $table->string('bank_name');
            $table->string('bank_account_number');
            $table->string('bank_account_name');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('admin_notes')->nullable();
            $table->timestamps();
        });

        // 6. Tabel Like & Komentar Pembeli pada Seller
        Schema::create('seller_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_like')->default(true);
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        // 7. Tabel Notifikasi Aplikasi (Untuk Member, Upline Member, Upline Seller)
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // 8. Tabel Inbox Saran (Member & Seller ke Admin)
        Schema::create('feedback_inboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('sender_role', 20)->default('member');
            $table->string('subject');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // 9. Tabel Promo & Hadiah Broadcast Admin
        Schema::create('promo_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->bigInteger('min_personal_sp')->default(0);
            $table->integer('min_active_referrals')->default(0);
            $table->bigInteger('min_group_sp')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_broadcasts');
        Schema::dropIfExists('feedback_inboxes');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('seller_reviews');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('seller_products');
    }
};
