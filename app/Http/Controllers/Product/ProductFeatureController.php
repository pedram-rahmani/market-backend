<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductFeature;
use App\Models\Product\ProductSpecification;
use Illuminate\Http\Request;

class ProductFeatureController extends Controller
{
    public function getFeaturesByCategory($categoryId)
    {
        $features = ProductFeature::where('category_id', $categoryId)->get();

        return response()->json(['features' => $features]);
    }

    public function storeFeature(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title'       => 'required|string|max:255',
            'name'        => 'nullable|string|max:255',
        ]);

        $feature = ProductFeature::create([
            'category_id' => $request->category_id,
            'title'       => $request->title,
            'name'        => $request->name,
        ]);

        return response()->json([
            'message' => 'ویژگی با موفقیت ثبت شد',
            'data'    => $feature
        ], 201);
    }

    public function store(Request $request, $productId)
    {
        $request->validate([
            'feature_id' => 'required|exists:product_features,id',
            'value'      => 'required|string|max:255'
        ]);

        $spec = ProductSpecification::create([
            'product_id' => $productId,
            'feature_id' => $request->feature_id,
            'value'      => $request->value,
        ]);

        return response()->json([
            'message' => 'مشخصات با موفقیت ثبت شد',
            'data'    => $spec
        ], 201);
    }
}
