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
        $category = $request->query('category');

        $query = DB::table('sellers')->where('status', 'approved')->where('is_active', true);

        if ($category && $category !== 'Semua') {
            $query->where('category', 'like', '%' . $category . '%');
        }

        if ($lat && $lng) {
            $sellers = $query->whereNotNull('latitude')->whereNotNull('longitude')
                ->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance_km", [(float)$lat, (float)$lng, (float)$lat])
                ->orderBy('distance_km')->get();
        } else {
            $sellers = $query->orderByDesc('id')->get();
        }

        foreach ($sellers as $s) {
            $s->products = DB::table('seller_products')->where('seller_id', $s->id)->get();
            $s->likes_count = DB::table('seller_reviews')->where('seller_id', $s->id)->where('is_like', true)->count();
            $s->comments = DB::table('seller_reviews')
                ->join('users', 'seller_reviews.user_id', '=', 'users.id')
                ->where('seller_id', $s->id)->whereNotNull('comment')
                ->select('users.name', 'seller_reviews.comment', 'seller_reviews.created_at')
                ->orderByDesc('seller_reviews.id')->limit(5)->get();
        }

        return view('sellers.nearby', compact('sellers', 'lat', 'lng', 'category'));
    }
}
