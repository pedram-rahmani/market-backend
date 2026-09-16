<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\Product\Category;
use App\Models\Product\ProductFeature;
use App\Models\Product\ProductColor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
{
    try {
        $query = Product::with([
            'colors',
            'introductions',
            'category',
            'specifications.feature',
            'warranties'
        ]);

        // Filter active products only unless requested otherwise
        if (!$request->has('include_inactive')) {
            $query->where('is_active', true);
        }

        // Category filter
        if ($request->has('category')) {
            $slug = $request->query('category');
            $query->whereHas('category', function ($q) use ($slug) {
                $q->where('slug', $slug)
                  ->orWhere('name', $slug);
            });
        }

        // Keyword search filter (matches name or brand)
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        // Brand filter (supports comma-separated string or array)
        if ($request->filled('brands')) {
            $brands = is_array($request->brands) ? $request->brands : explode(',', $request->brands);
            $query->whereIn('brand', array_map('trim', $brands));
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->query('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->query('max_price'));
        }

        // In-stock products filter
        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        }

        // Discounted products filter
        if ($request->boolean('has_discount')) {
            $query->whereNotNull('discount')->where('discount', '>', 0);
        }

        // Sorting logic
        $sort = $request->query('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'popular':
                $query->orderBy('rate', 'desc');
                break;
            case 'bestselling':
                $query->orderBy('sales_count', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        $products = $query->get();

        return response()->json(['products' => $products]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->with(['colors', 'introductions', 'category', 'specifications.feature', 'warranties'])
            ->firstOrFail();

        return response()->json(['product' => $product]);
    }

    public function edit($id)
    {
        $product = Product::with('warranties')->findOrFail($id);

        $specs = \App\Models\Product\ProductSpecification::where('product_id', $id)->get();

        $debug = $specs->map(function ($s) {
            return [
                'id' => $s->id,
                'feature_id' => $s->feature_id,
                'feature_data' => $s->feature
            ];
        });

        return response()->json([
            'product' => $product,
            'debug' => $debug
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Product::class);
        $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'slug' => 'required|string|unique:products,slug',
            'sku' => 'nullable|string|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'sales_count' => 'nullable|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'discount_starts_at' => 'nullable|date',
            'discount_expires_at' => 'nullable|date|after_or_equal:discount_starts_at',
            'is_active' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($request) {
            $data = $request->only([
                'name', 'brand', 'slug', 'sku', 'price', 'stock', 'sales_count',
                'discount', 'discount_starts_at', 'discount_expires_at',
                'category_id', 'is_active', 'featured'
            ]);

            $data['slug'] = Str::slug($request->name, '-');
            $data['stock'] = $request->input('stock', 0);
            $data['sales_count'] = $request->input('sales_count', 0);
            $data['is_active'] = $request->has('is_active') ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : true;
            $data['featured'] = $request->has('featured') ? filter_var($request->featured, FILTER_VALIDATE_BOOLEAN) : false;

            if ($request->has('options')) {
                $data['options'] = is_string($request->options)
                    ? json_decode($request->options, true)
                    : $request->options;
            }

            if ($request->hasFile('img')) {
                $data['img'] = $request->file('img')->store('products', 'public');
            }

            $product = Product::create($data);

            if ($request->has('introduction_blocks')) {
                $blocks = json_decode($request->introduction_blocks, true);
                if (is_array($blocks)) {
                    $product->introductions()->createMany($blocks);
                }
            }

            if ($request->has('colors')) {
                $colors = json_decode($request->colors, true);
                if (is_array($colors)) {
                    $uniqueColors = collect($colors)->unique('name')->toArray();
                    $product->colors()->createMany($uniqueColors);
                }
            }

            if ($request->has('specifications')) {
                $specs = json_decode($request->specifications, true);
                $this->saveSpecifications($product, $specs, $request->category_id);
            }

            if ($request->has('warranties')) {
                $warranties = is_string($request->warranties) ? json_decode($request->warranties, true) : $request->warranties;
                if (is_array($warranties)) {
                    $formattedWarranties = [];
                    foreach ($warranties as $w) {
                        $warrantyId = $w['warranty_id'] ?? $w['id'] ?? null;
                        if ($warrantyId) {
                            $formattedWarranties[$warrantyId] = [
                                'price' => $w['price'] ?? 0,
                                'is_default' => filter_var($w['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                            ];
                        }
                    }
                    if (!empty($formattedWarranties)) {
                        $product->warranties()->attach($formattedWarranties);
                    }
                }
            }

            return response()->json(['message' => 'Product created!', 'product' => $product], 201);
        });
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'slug' => 'required|string|unique:products,slug,' . $id,
            'sku' => 'nullable|string|unique:products,sku,' . $id,
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'sales_count' => 'nullable|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'discount_starts_at' => 'nullable|date',
            'discount_expires_at' => 'nullable|date|after_or_equal:discount_starts_at',
            'is_active' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($request, $product) {
            $updateData = $request->only([
                'name', 'brand', 'slug', 'sku', 'price', 'stock', 'sales_count',
                'discount', 'discount_starts_at', 'discount_expires_at',
                'category_id', 'is_active', 'featured'
            ]);

            $updateData['slug'] = Str::slug($request->name, '-');

            if ($request->has('is_active')) {
                $updateData['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            }
            if ($request->has('featured')) {
                $updateData['featured'] = filter_var($request->featured, FILTER_VALIDATE_BOOLEAN);
            }

            if ($request->has('options')) {
                $updateData['options'] = is_string($request->options)
                    ? json_decode($request->options, true)
                    : $request->options;
            }

            $product->update($updateData);

            if ($request->hasFile('img')) {
                if ($product->img) {
                    Storage::disk('public')->delete($product->img);
                }
                $product->img = $request->file('img')->store('products', 'public');
                $product->save();
            }

            if ($request->has('introduction_blocks')) {
                $product->introductions()->delete();
                $blocks = json_decode($request->introduction_blocks, true);
                if (is_array($blocks)) {
                    $product->introductions()->createMany($blocks);
                }
            }

            if ($request->has('colors')) {
                $product->colors()->delete();
                $colors = json_decode($request->colors, true);

                if (is_array($colors)) {
                    $uniqueColors = collect($colors)->unique('name');

                    foreach ($uniqueColors as $color) {
                        $product->colors()->create([
                            'name' => $color['name'],
                            'hex' => $color['hex']
                        ]);
                    }
                }
            }

            if ($request->has('specifications')) {
                $product->specifications()->delete();
                $specs = json_decode($request->specifications, true);
                $this->saveSpecifications($product, $specs, $request->category_id);
            }

            if ($request->has('warranties')) {
                $warranties = is_string($request->warranties) ? json_decode($request->warranties, true) : $request->warranties;
                if (is_array($warranties)) {
                    $formattedWarranties = [];
                    foreach ($warranties as $w) {
                        $warrantyId = $w['warranty_id'] ?? $w['id'] ?? null;
                        if ($warrantyId) {
                            $formattedWarranties[$warrantyId] = [
                                'price' => $w['price'] ?? 0,
                                'is_default' => filter_var($w['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                            ];
                        }
                    }
                    $product->warranties()->sync($formattedWarranties);
                } else {
                    $product->warranties()->sync([]);
                }
            } else {
                $product->warranties()->sync([]);
            }

            return response()->json(['message' => 'Product updated successfully']);
        });
    }

    private function saveSpecifications($product, $specs, $categoryId)
    {
        if (!is_array($specs)) return;

        foreach ($specs as $spec) {
            $featureId = $spec['feature_id'] ?? null;
            $value = $spec['value'] ?? null;

            if (empty($value)) continue;

            if (!is_numeric($featureId)) {
                $featureTitle = trim($featureId);

                if (empty($featureTitle)) continue;

                $newFeature = ProductFeature::firstOrCreate(
                    ['title' => $featureTitle, 'category_id' => $categoryId],
                    ['category_id' => $categoryId]
                );
                $featureId = $newFeature->id;
            }

            $product->specifications()->create([
                'feature_id' => $featureId,
                'value' => $value
            ]);
        }
    }

    public function destroy(Product $product)
    {
        return DB::transaction(function () use ($product) {
            if ($product->img) Storage::disk('public')->delete($product->img);
            $product->colors()->delete();
            $product->specifications()->delete();
            $product->introductions()->delete();
            $product->warranties()->detach();
            $product->delete();
            return response()->json(['message' => 'Deleted!']);
        });
    }

    public function getCategories()
    {
        $categories = Category::whereNull('parent_id')->with('children')->get();
        return response()->json(['categories' => $categories]);
    }

    public function getFilters(Request $request)
    {
        $categoryId = $request->query('category_id');

        $query = Product::query()->where('is_active', true);
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // brands
        $brands = (clone $query)->whereNotNull('brand')
            ->select('brand')
            ->distinct()
            ->pluck('brand');

        // features
        $features = ProductFeature::when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->with(['specifications' => fn($q) => $q->select('feature_id', 'value')->distinct()])
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'title' => $f->title,
                'values' => $f->specifications->pluck('value')->unique()->values()
            ]);

        // colors
        $colors = ProductColor::whereHas('product', fn($q) => $q->when($categoryId, fn($p) => $p->where('category_id', $categoryId)))
            ->select('name', 'hex')->distinct()->get();

        return response()->json([
            'brands' => $brands,
            'features' => $features,
            'colors' => $colors
        ]);
    }
}
