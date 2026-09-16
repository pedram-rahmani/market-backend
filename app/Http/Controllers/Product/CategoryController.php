<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Category;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        //$this->authorize('viewAny', Category::class);
        $categories = Category::all();
        return response()->json(['data' => $categories]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Category::class);
        $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $data = $request->only('name', 'slug', 'parent_id');

        if ($request->filled('parent_id')) {
            $parent = Category::find($request->parent_id);
            $data['level'] = $parent->level + 1;
        } else {
            $data['level'] = 1;
        }

        $category = Category::create($data);

        return response()->json(['data' => $category], 201);
    }

    // Show a specific category
    public function show($id)
    {
        $category = Category::findOrFail($id);

        return response()->json(['data' => $category]);
    }

    // Update an existing category
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $this->authorize('update', $category);
        $data = $request->only('name', 'slug', 'parent_id');

        if ($request->has('parent_id') && $request->parent_id) {
            $parent = Category::find($request->parent_id);
            $data['level'] = $parent->level + 1;
        } else {
            $data['level'] = 1;
        }

        $category->update($data);
        return response()->json(['data' => $category]);
    }

    // Delete a category
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $this->authorize('delete', $category);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
