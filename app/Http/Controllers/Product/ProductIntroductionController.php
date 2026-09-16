<?php

namespace App\Http\Controllers;

use App\Models\Product\Product;
use App\Models\Product\ProductIntroduction;
use Illuminate\Http\Request;

class ProductIntroductionController extends Controller
{

    public function index(Product $product)
    {
        return $product->introduction()
            ->orderBy('sort_order')
            ->get();
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:paragraph,list,heading',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['product_id'] = $product->id;

        $section = ProductIntroduction::create($validated);

        return response()->json([
            'message' => 'بخش معرفی با موفقیت ایجاد شد.',
            'section' => $section,
        ], 201);
    }


    public function show(Product $product, ProductIntroduction $section)
    {
        // اطمینان از اینکه این بخش متعلق به همان محصول است
        if ($section->product_id !== $product->id) {
            return response()->json(['error' => 'این بخش متعلق به محصول انتخاب‌شده نیست.'], 404);
        }

        return response()->json($section);
    }


    public function update(Request $request, Product $product, ProductIntroduction $section)
    {
        if ($section->product_id !== $product->id) {
            return response()->json(['error' => 'این بخش متعلق به محصول انتخاب‌شده نیست.'], 404);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:paragraph,list,heading',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $section->update($validated);

        return response()->json([
            'message' => 'بخش معرفی با موفقیت بروزرسانی شد.',
            'section' => $section,
        ]);
    }


    public function destroy(Product $product, ProductIntroduction $section)
    {
        if ($section->product_id !== $product->id) {
            return response()->json(['error' => 'این بخش متعلق به محصول انتخاب‌شده نیست.'], 404);
        }

        $section->delete();

        return response()->json(['message' => 'بخش معرفی با موفقیت حذف شد.']);
    }
}
