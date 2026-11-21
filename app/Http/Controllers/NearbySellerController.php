<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NearbySellerController extends Controller
{
    public function index(Request $request)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if ($lat && $lng) {
            $sellers = DB::table('sellers')
                ->where('status', 'approved')
                ->where('is_active', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->selectRaw("id, store_name, sp_percentage, latitude, longitude,
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance_km",
                    [(float) $lat, (float) $lng, (float) $lat]
                )
                ->orderBy('distance_km')
                ->get();
        } else {
            // Jika GPS belum dikirim, tampilkan semua tenant aktif yang sudah punya lokasi GPS
            $sellers = DB::table('sellers')
                ->where('status', 'approved')
                ->where('is_active', true)
                ->select('id', 'store_name', 'sp_percentage', 'latitude', 'longitude')
                ->orderByDesc('id')
                ->get();
        }

        return view('sellers.nearby', compact('sellers', 'lat', 'lng'));
    }
}
