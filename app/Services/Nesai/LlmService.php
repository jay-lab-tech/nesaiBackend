<?php
namespace App\Services\Nesai;
class LlmService { public function isConfigured(): bool { return filled(config('services.llm.key')); } }
