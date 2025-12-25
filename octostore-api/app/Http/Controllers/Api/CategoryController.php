<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        // Cache the full tree structure
        $categories = Cache::remember('categories_tree', 600, function () {
            return Category::whereNull('parent_id')
                ->with('children')
                ->get();
        });

        return CategoryResource::collection($categories);
    }

    public function show($id)
    {
        $category = Category::with('children')->findOrFail($id);
        return new CategoryResource($category);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $slug = Str::slug($validated['name']);

        // Handle uniqueness collision logic if needed, simplied here
        $count = Category::where('slug', 'like', "{$slug}%")->count();
        if ($count > 0)
            $slug .= "-{$count}";

        $category = new Category($validated);
        $category->slug = $slug;

        if ($request->hasFile('image')) {
            $tenantId = app()->bound('tenant') ? app('tenant')->id : 'common';
            $path = $request->file('image')->store("tenants/{$tenantId}/categories", 'public');
            $category->image_path = $path;
        }

        $category->save();

        Cache::forget('categories_tree');

        return new CategoryResource($category);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if (isset($validated['name'])) {
            $category->slug = Str::slug($validated['name']);
        }

        if ($request->hasFile('image')) {
            $tenantId = app()->bound('tenant') ? app('tenant')->id : 'common';
            $path = $request->file('image')->store("tenants/{$tenantId}/categories", 'public');
            $category->image_path = $path;
        }

        $category->update($validated);
        Cache::forget('categories_tree');

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        Cache::forget('categories_tree');
        return response()->noContent();
    }
}
