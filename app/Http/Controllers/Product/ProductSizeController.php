<?php

namespace App\Http\Controllers;

use App\Models\Product\ProductSize;
use Illuminate\Http\Request;

class ProductSizeController extends Controller
{
    // Display all sizes (possibly for use in a different interface)
    public function index()
    {
        return ProductSize::all();
    }

    public function create()
    {
        return response()->json(['message' => 'Create size form not needed']);
    }

    // Store a new size
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $size = ProductSize::create($request->all());

        return response()->json($size, 201);
    }

    public function edit(ProductSize $size)
    {
        return response()->json($size);
    }

    public function update(Request $request, ProductSize $size)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $size->update($request->all());

        return response()->json($size);
    }

    public function destroy(ProductSize $size)
    {
        $size->delete();

        return response()->json(['message' => 'Size deleted successfully']);
    }
}
