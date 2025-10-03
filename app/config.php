<?php
return [
    'app' => [
        'name' => 'Origin Driving School Management System',
        'baseUrl' => '',
        'timezone' => 'Australia/Melbourne',
        'environment' => 'development',
        'upload_dir' => __DIR__ . '/../uploads/documents/',
        'allowed_upload_types' => ['pdf','doc','docx','jpg','jpeg','png'],
        'max_upload_size' => 5242880
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'origin_driving_school',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ]
];