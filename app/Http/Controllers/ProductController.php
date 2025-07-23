<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $query = Product::query();

        // Поиск по названию
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        // Фильтрация по категории
        if ($request->has('category_id')) {
            $categoryId = $request->input('category_id');
            $query->where('category_id', $categoryId);
        }

        // Сортировка по названию
        if ($request->has('sort')) {
            $sortField = $request->input('sort');
            $sortDirection = $request->input('direction', 'asc'); // По умолчанию сортировка по возрастанию
            $query->orderBy($sortField, $sortDirection);
        }

        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }
}