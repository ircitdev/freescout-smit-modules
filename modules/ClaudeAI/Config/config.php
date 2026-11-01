<?php

return [
    // Claude API
    'api_key'       => env('CLAUDE_API_KEY', ''),
    'model'         => env('CLAUDE_MODEL', 'claude-haiku-4-5-20251001'),
    'max_tokens'    => env('CLAUDE_MAX_TOKENS', 1024),
    'temperature'   => env('CLAUDE_TEMPERATURE', 0.3),

    // Features
    'auto_reply'    => env('CLAUDE_AUTO_REPLY', false),       // Auto-reply to new tickets
    'operator_hint' => env('CLAUDE_OPERATOR_HINT', true),     // Show AI hint in sidebar
    'auto_classify' => env('CLAUDE_AUTO_CLASSIFY', true),     // Auto-classify ticket scenario

    // Billing integration
    'billing_url'   => env('CLAUDE_BILLING_URL', ''),         // Smit Billing REST API URL
    'billing_token' => env('CLAUDE_BILLING_TOKEN', ''),       // Billing API token

    // Knowledge base
    'kb_url'        => env('CLAUDE_KB_URL', ''),              // FreeScout KB URL
    'use_kb'        => env('CLAUDE_USE_KB', true),            // Include KB articles in context

    // Limits
    'max_auto_replies'    => env('CLAUDE_MAX_AUTO_REPLIES', 3),   // Max auto-replies per conversation
    'require_review'      => env('CLAUDE_REQUIRE_REVIEW', true),  // Draft mode — operator reviews before sending
    'escalation_keywords' => 'оператор,человек,живой,менеджер,позвоните,перезвоните',
];
