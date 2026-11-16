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
    'catalog:general:display:description' => [
        'type' => 'text',
        'default_value' => '',
        'secret' => false,
        'storefront' => true,
        'scopes' => ['global'],
        'constraints' => [],
        'sanitize_xss' => [
            'allow_elements' => [
                'a' => ['href', 'class'],
                'strong' => [],
            ],
        ],
    ],
    'theme:storefront:colors:background_brand_primary' => [
        'type' => 'color',
        'default_value' => '#00bebe',
        'secret' => false,
        'storefront' => true,
        'scopes' => ['global', 'store'],
        'constraints' => [
            [
                'type' => 'regex',
                'message' => 'Must be a valid hex color (e.g. #00bebe)',
                'options' => ['pattern' => '^#[0-9A-Fa-f]{6}$'],
            ],
        ],
    ],
    'ai:assistant:vendor:model' => [
        'type' => 'select',
        'default_value' => 'model-small',
        'secret' => false,
        'storefront' => false,
        'scopes' => ['global'],
        'constraints' => [],
        'options' => ['model-small', 'model-large'],
    ],
    'ai:assistant:vendor:provider' => [
        'type' => 'radio',
        'default_value' => 'openai',
        'secret' => false,
        'storefront' => false,
        'scopes' => ['global'],
        'constraints' => [],
        'options' => ['openai', 'anthropic'],
    ],
    'ai:assistant:vendor:required_model' => [
        'type' => 'select',
        'default_value' => 'model-small',
        'secret' => false,
        'storefront' => false,
        'scopes' => ['global'],
        'constraints' => [
            ['type' => 'required'],
        ],
        'options' => ['model-small', 'model-large'],
    ],
];
