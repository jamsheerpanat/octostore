<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProviderInterface;

class OpenAiProvider implements AiProviderInterface
{
    // Placeholder for future implementation
    public function naturalLanguageToFilters(string $query): array
    {
        // Call OpenAI API -> parse JSON response
        return [];
    }

    public function summarizeReviews(array $reviews): string
    {
         return "Summary generating via OpenAI...";
    }

    public function recommendByIntent(string $intent): array
    {
        return [];
    }
}
