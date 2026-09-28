<?php

return [
    // Maximum number of stored conversation messages sent to Gemini per request.
    'history_limit' => (int) env('CHAT_HISTORY_LIMIT', 20),
    // Keep stored chat history bounded even when a session remains active.
    'stored_messages_limit' => max(2, (int) env('CHAT_STORED_MESSAGES_LIMIT', 100)),
    // Per-provider-call timeout. With MaxSteps(3), one chat request is bounded to 3 calls.
    'provider_timeout' => min(60, max(1, (int) env('CHAT_PROVIDER_TIMEOUT', 30))),
    // Manual chat retention window; pruning is dry-run unless --apply is supplied.
    'retention_days' => (int) env('CHAT_RETENTION_DAYS', 90),
];
