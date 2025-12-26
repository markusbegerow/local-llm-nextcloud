<?php
/**
 * Route configuration for Local LLM app
 */

return [
    'routes' => [
        // Page routes
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

        // Chat API routes
        ['name' => 'chat#sendMessage', 'url' => '/api/chat', 'verb' => 'POST'],
        ['name' => 'chat#getConversations', 'url' => '/api/conversations', 'verb' => 'GET'],
        ['name' => 'chat#getConversation', 'url' => '/api/conversations/{id}', 'verb' => 'GET'],
        ['name' => 'chat#getMessages', 'url' => '/api/conversations/{id}/messages', 'verb' => 'GET'],
        ['name' => 'chat#deleteConversation', 'url' => '/api/conversations/{id}', 'verb' => 'DELETE'],
        ['name' => 'chat#clearMessages', 'url' => '/api/conversations/{id}/clear', 'verb' => 'POST'],
        ['name' => 'chat#updateConversation', 'url' => '/api/conversations/{id}', 'verb' => 'PUT'],

        // Config API routes
        ['name' => 'config#getConfigs', 'url' => '/api/configs', 'verb' => 'GET'],
        ['name' => 'config#getConfig', 'url' => '/api/configs/{id}', 'verb' => 'GET'],
        ['name' => 'config#createConfig', 'url' => '/api/configs', 'verb' => 'POST'],
        ['name' => 'config#updateConfig', 'url' => '/api/configs/{id}', 'verb' => 'PUT'],
        ['name' => 'config#deleteConfig', 'url' => '/api/configs/{id}', 'verb' => 'DELETE'],
        ['name' => 'config#testConnection', 'url' => '/api/configs/{id}/test', 'verb' => 'POST'],
        ['name' => 'config#setDefault', 'url' => '/api/configs/{id}/default', 'verb' => 'POST'],
    ]
];
