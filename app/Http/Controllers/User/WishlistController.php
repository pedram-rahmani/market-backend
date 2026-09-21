<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\User\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wishlists = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->with('product')
            ->latest()
            ->get();

        return response()->json([
            'items' => $wishlists->pluck('product'),
        ]);
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        $wishlist = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            $isFavorite = false;
        } else {
            Wishlist::create([
                'user_id' => $request->user()->id,
                'product_id' => $product->id,
            ]);
            $isFavorite = true;
        }

        return response()->json([
            'is_favorite' => $isFavorite,
            'message' => $isFavorite
                ? 'محصول به علاقه‌مندی‌ها اضافه شد.'
                : 'محصول از علاقه‌مندی‌ها حذف شد.',
        ]);
    }

    public function status(Request $request, Product $product): JsonResponse
    {
        return response()->json([
            'is_favorite' => Wishlist::query()
                ->where('user_id', $request->user()->id)
                ->where('product_id', $product->id)
                ->exists(),
        ]);
    }
}
