<?php

namespace App\Services\Nesai;

class LlmService
{
    public function isConfigured(): bool
    {
        return filled(config('ai.providers.gemini.key')) || filled(config('services.llm.key'));
    }
}
