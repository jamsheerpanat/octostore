<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        return BrandResource::collection(Brand::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'nullable|url',
            'logo' => 'nullable|image|max:2048',
        ]);

        $brand = new Brand($validated);
        $brand->slug = Str::slug($validated['name']);

        if ($request->hasFile('logo')) {
            $tenantId = app()->bound('tenant') ? app('tenant')->id : 'common';
            $brand->logo_path = $request->file('logo')->store("tenants/{$tenantId}/brands", 'public');
        }

        $brand->save();

        return new BrandResource($brand);
    }
}
