<?php
return [
    'routes' => [
        // Page routes
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        
        // Application controller routes (HTML views)
        ['name' => 'applications#index', 'url' => '/applications', 'verb' => 'GET'],
        ['name' => 'applications#create', 'url' => '/applications', 'verb' => 'POST'],
        
        // Invoice controller routes (HTML views)
        ['name' => 'invoices#index', 'url' => '/invoices', 'verb' => 'GET'],
        ['name' => 'invoices#create', 'url' => '/invoices', 'verb' => 'POST'],
        
        // Integration status routes
        ['name' => 'application_api#integrationStatus', 'url' => '/api/integration/status', 'verb' => 'GET'],
        ['name' => 'application_api#listMembers', 'url' => '/api/integration/members', 'verb' => 'GET'],
        
        // API routes
        ['name' => 'application_api#index', 'url' => '/api/applications', 'verb' => 'GET'],
        ['name' => 'application_api#listApplicationsPaginated', 'url' => '/api/applications_paginated', 'verb' => 'GET'],
        ['name' => 'application_api#show', 'url' => '/api/applications/{id}', 'verb' => 'GET'],
        ['name' => 'application_api#create', 'url' => '/api/applications', 'verb' => 'POST'],
        ['name' => 'application_api#update', 'url' => '/api/applications/{id}', 'verb' => 'PUT'],
        ['name' => 'application_api#destroy', 'url' => '/api/applications/{id}', 'verb' => 'DELETE'],
        ['name' => 'application_api#approve', 'url' => '/api/applications/{id}/approve', 'verb' => 'POST'],
        ['name' => 'application_api#reject', 'url' => '/api/applications/{id}/reject', 'verb' => 'POST'],
        ['name' => 'application_api#createInvoice', 'url' => '/api/applications/{id}/invoice', 'verb' => 'POST'],
    ],
];
