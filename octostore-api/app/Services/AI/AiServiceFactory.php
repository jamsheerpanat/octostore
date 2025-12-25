<?php

namespace App\Services\AI;

use App\Services\AI\Providers\OpenAiProvider;
use App\Services\AI\Providers\RuleBasedAiProvider;

class AiServiceFactory
{
    public static function make(): AiProviderInterface
    {
        // Could be config driven: config('services.ai.provider')
        $provider = env('AI_PROVIDER', 'rule_based');
        
        return match($provider) {
            'openai' => new OpenAiProvider(),
            default => new RuleBasedAiProvider(),
        };
    }
}
