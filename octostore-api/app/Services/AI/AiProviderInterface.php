<?php

namespace App\Services\AI;

interface AiProviderInterface
{
    /**
     * Parse natural language query into filters.
     * 
     * @param string $query User's search query
     * @return array structured filters e.g. ['price_max' => 50, 'category' => 'Shoes']
     */
    public function naturalLanguageToFilters(string $query): array;

    /**
     * Generate a summary of multiple reviews.
     * 
     * @param array $reviews List of review texts
     * @return string Summary
     */
    public function summarizeReviews(array $reviews): string;

    /**
     * Recommend products/tags based on user intent description.
     * 
     * @param string $intent User description "I need something for a summer beach party"
     * @return array List of product IDs or tags
     */
    public function recommendByIntent(string $intent): array;
}
