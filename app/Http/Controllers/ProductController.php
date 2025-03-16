<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Traits\ApiResponse;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    use ApiResponse;

    private const CACHE_KEY_COLLECTION = 'products';
    private const CACHE_KEY_SINGLE = 'product';
    private const CACHE_TTL = 3600; // 1 hour
    private const PER_PAGE = 10;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', self::PER_PAGE);
            $search = $request->input('search');

            $cacheKey = self::CACHE_KEY_COLLECTION . ":{$page}:{$perPage}:{$search}";

            $products = Cache::remember(
                $cacheKey,
                self::CACHE_TTL,
                function () use ($perPage, $search) {
                    return Product::when($search, function ($query, $search) {
                            return $query->where('name', 'like', "%{$search}%");
                        })
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage);
                }
            );

            return $this->successResponse(
                ProductResource::collection($products),
                $products
            );
        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch products');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $product = Product::create($request->validated());
            $this->clearProductCache();

            return $this->createdResponse(new ProductResource($product));
        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            return $this->errorResponse('Failed to create product');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->getProductFromCache($id);
            return $this->successResponse(new ProductResource($product));
        } catch (\Exception $e) {
            Log::error('Error fetching product: ' . $e->getMessage());
            return $this->errorResponse('Product not found', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->update($request->validated());
            $this->clearProductCache();

            return $this->successResponse(new ProductResource($product));
        } catch (\Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            return $this->errorResponse('Failed to update product');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            $this->clearProductCache();

            return $this->noContentResponse();
        } catch (\Exception $e) {
            Log::error('Error deleting product: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete product');
        }
    }

    /**
     * Get product from cache or database
     */
    private function getProductFromCache(string $id): Product
    {
        return Cache::remember(
            self::CACHE_KEY_SINGLE . ":{$id}",
            self::CACHE_TTL,
            fn() => Product::findOrFail($id)
        );
    }

    /**
     * Clear product related cache
     */
    private function clearProductCache(): void
    {
        Cache::flush();
    }
}
