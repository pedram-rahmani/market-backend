<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Warranty;
use Illuminate\Http\Request;

class WarrantyController extends Controller
{
    public function index()
    {
        return response()->json(Warranty::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'duration_months' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $warranty = Warranty::create($validated);

        return response()->json($warranty, 201);
    }

    public function show(Warranty $warranty)
    {
        return response()->json($warranty);
    }

    public function update(Request $request, Warranty $warranty)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'duration_months' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $warranty->update($validated);

        return response()->json($warranty);
    }

    public function destroy(Warranty $warranty)
    {
        $warranty->delete();

        return response()->json(['message' => 'گارانتی با موفقیت حذف شد.'], 200);
    }
}
