<?php

return [
    'catalog:general:display:items_per_page' => [
        'type' => 'integer',
        'default_value' => '12',
        'secret' => false,
        'storefront' => true,
        'scopes' => ['global', 'store'],
        'constraints' => [
            ['type' => 'required'],
            ['type' => 'min', 'options' => ['min' => 1]],
            ['type' => 'max', 'options' => ['max' => 100]],
        ],
    ],
    'catalog:general:display:sort_order' => [
        'type' => 'enum',
        'default_value' => 'name_asc',
        'secret' => false,
        'storefront' => true,
        'scopes' => ['global', 'store'],
        'constraints' => [
            ['type' => 'choice', 'options' => ['choices' => ['name_asc', 'name_desc', 'price_asc', 'price_desc']]],
        ],
    ],
    'catalog:email:notifications:sender_email' => [
        'type' => 'string',
        'default_value' => 'noreply@example.com',
        'secret' => false,
        'storefront' => false,
        'scopes' => ['global'],
        'constraints' => [
            ['type' => 'required'],
            ['type' => 'email'],
        ],
    ],
    'catalog:email:notifications:api_key' => [
        'type' => 'string',
        'default_value' => null,
        'secret' => true,
        'storefront' => false,
        'scopes' => ['global'],
        'constraints' => [],
    ],
];
