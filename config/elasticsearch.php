<?php

return [
    'host' => env('ELASTICSEARCH_HOST', 'http://localhost:9200'),
    'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'haadishop'),
    'enabled' => env('ELASTICSEARCH_ENABLED', false),
];

