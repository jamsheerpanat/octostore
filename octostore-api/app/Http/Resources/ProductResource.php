<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'price_starting_at' => (float) $this->variants->min('price'), // Dynamic summary
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'images' => $this->images->map(fn($img) => [
                'id' => $img->id,
                'url' => url($img->image_path),
                'is_thumbnail' => $img->is_thumbnail,
                'sort_order' => $img->sort_order,
            ]),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'tags' => $this->tags->pluck('name'),
        ];
    }
}
