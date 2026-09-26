<?php

return [
    // Maximum number of stored conversation messages sent to Gemini per request.
    'history_limit' => (int) env('CHAT_HISTORY_LIMIT', 20),
];
