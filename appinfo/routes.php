<?php
return [
    'routes' => [
        ['name' => 'page#main', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#zipDrop', 'url' => '/zipDrop', 'verb' => 'GET'],
        ['name' => 'page#getFile', 'url' => '/getFile', 'verb' => 'GET'],
        ['name' => 'page#getFileMetadata', 'url' => '/getFileMetadata', 'verb' => 'GET']
    ]
];
