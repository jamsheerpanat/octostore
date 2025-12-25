<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProviderInterface;
use Illuminate\Support\Str;

class RuleBasedAiProvider implements AiProviderInterface
{
    public function naturalLanguageToFilters(string $query): array
    {
        $filters = [];
        $query = strtolower($query);
        
        // Mock Rules
        if (Str::contains($query, ['cheap', 'under 50', 'budget'])) {
             $filters['price_max'] = 50;
        }
        
        if (Str::contains($query, ['shoes', 'sneakers', 'boots'])) {
            $filters['collection'] = 'Footwear'; 
        }
        
        if (Str::contains($query, ['iphone', 'android', 'phone'])) {
            $filters['category'] = 'Electronics';
        }

        return $filters;
    }

    public function summarizeReviews(array $reviews): string
    {
        // Mock Implementation: Just grab the first sentence of highly voted ones or random
        if (empty($reviews)) return "No reviews available.";
        
        $count = count($reviews);
        return "Customers generally like this product. Based on $count reviews.";
    }

    public function recommendByIntent(string $intent): array
    {
        // Mock: Returns static tag suggestions based on keywords
        $intent = strtolower($intent);
        
        if (Str::contains($intent, ['summer', 'beach'])) {
            return ['keywords' => ['shorts', 't-shirts', 'sandals']];
        }
        
        if (Str::contains($intent, ['winter', 'cold'])) {
             return ['keywords' => ['jacket', 'hoodie', 'boots']];
        }
        
        return ['keywords' => ['popular']];
    }
}
