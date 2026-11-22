<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    public function getConfig(): array
    {
        $s = DB::table('app_settings')->pluck('value', 'key');
        return [
            'active_gateway' => $s['pg_active_gateway'] ?? 'midtrans', // midtrans | xendit
            'is_production' => ($s['pg_is_production'] ?? '0') === '1',
            'midtrans_server_key' => $s['midtrans_server_key'] ?? '',
            'midtrans_client_key' => $s['midtrans_client_key'] ?? '',
            'xendit_secret_key' => $s['xendit_secret_key'] ?? '',
            'xendit_callback_token' => $s['xendit_callback_token'] ?? '',
        ];
    }

    /**
     * Buat Tagihan QRIS Dinamis via Midtrans (Tahap Awal) atau Xendit
     */
    public function createQrisCharge(int $buyerId, int $sellerId, int $amountRp, int $nonSpAmountRp = 0): array
    {
        $cfg = $this->getConfig();
        $gateway = $cfg['active_gateway'];
        $orderId = 'SWZ-PG-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
        $buyer = DB::table('users')->where('id', $buyerId)->firstOrFail();

        $qrisOutput = null;
        $rawResponse = ['mode' => 'sandbox_simulation'];

        if ($gateway === 'midtrans' && !empty($cfg['midtrans_server_key'])) {
            $baseUrl = $cfg['is_production']
                ? 'https://midtrans.com'
                : 'https://midtrans.com';

            $response = Http::withBasicAuth($cfg['midtrans_server_key'], '')
                ->post($baseUrl, [
                    'payment_type' => 'qris',
                    'transaction_details' => [
                        'order_id' => $orderId,
                        'gross_amount' => $amountRp,
                    ],
                    'customer_details' => [
                        'first_name' => $buyer->name,
                        'email' => $buyer->email,
                        'phone' => $buyer->phone ?? '08120000000',
                    ],
                ]);

            $rawResponse = $response->json() ?? [];
            $qrisOutput = $rawResponse['actions']['url'] ?? ($rawResponse['qr_string'] ?? null);
        } elseif ($gateway === 'xendit' && !empty($cfg['xendit_secret_key'])) {
            $response = Http::withBasicAuth($cfg['xendit_secret_key'], '')
                ->post('https://xendit.co', [
                    'reference_id' => $orderId,
                    'type' => 'DYNAMIC',
                    'currency' => 'IDR',
                    'amount' => $amountRp,
                ]);

            $rawResponse = $response->json() ?? [];
            $qrisOutput = $rawResponse['qr_string'] ?? null;
        }

        DB::table('payment_gateway_logs')->insert([
            'order_id' => $orderId,
            'gateway' => $gateway,
            'buyer_id' => $buyerId,
            'seller_id' => $sellerId,
            'amount_rp' => $amountRp,
            'non_sp_amount_rp' => $nonSpAmountRp,
            'qris_string_or_url' => $qrisOutput,
            'status' => 'pending',
            'raw_response' => json_encode($rawResponse),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'order_id' => $orderId,
            'gateway' => $gateway,
            'qris_url_or_string' => $qrisOutput,
            'has_live_key' => ($gateway === 'midtrans' && !empty($cfg['midtrans_server_key'])) || ($gateway === 'xendit' && !empty($cfg['xendit_secret_key'])),
        ];
    }

    /**
     * Eksekusi Settlement Lunas: Hitung SP & Teruskan Dana Bersih ke Seller
     */
    public function settlePaidOrder(string $orderId, SwizhPointService $spService): array
    {
        $log = DB::table('payment_gateway_logs')->where('order_id', $orderId)->firstOrFail();
        if ($log->status === 'paid') {
            return ['already_paid' => true];
        }

        $result = $spService->recordTransaction(
            $log->buyer_id,
            $log->seller_id,
            $log->amount_rp,
            'qris_gateway',
            $log->non_sp_amount_rp
        );

        // Hitung dana bersih hak Seller = Nominal Belanja - Kewajiban SP (65.5%)
        $spObligationRp = (int) round($result['sp_earned'] * 0.655);
        $netTransferToSeller = max(0, $log->amount_rp - $spObligationRp);

        $seller = DB::table('sellers')->where('id', $log->seller_id)->first();
        $payoutStatus = !empty($seller->bank_info) ? 'auto_disbursed' : 'pending_bank_info';

        // Catat ke wallet / payout seller
        DB::table('users')->where('id', $seller->user_id)->increment('wallet_balance', $netTransferToSeller);

        DB::table('payment_gateway_logs')->where('id', $log->id)->update([
            'status' => 'paid',
            'payout_status' => $payoutStatus,
            'updated_at' => now(),
        ]);

        $spService->sendNotification(
            $seller->user_id,
            '💵 Pembayaran QRIS Masuk & Diteruskan!',
            "Transaksi Rp " . number_format($log->amount_rp, 0, ',', '.') . " lunas. Dana bersih Rp " . number_format($netTransferToSeller, 0, ',', '.') . " otomatis diteruskan ke akun Seller Anda."
        );

        return $result;
    }
}
