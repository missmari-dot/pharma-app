<?php

return [
    'ordonnances' => [
        'path' => 'uploads/ordonnances',
        'max_size' => 5120, // 5MB
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png']
    ],
    'produits' => [
        'path' => 'uploads/produits',
        'max_size' => 2048, // 2MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'webp']
    ]
];