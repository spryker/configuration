<?php

return [
    'features' => [
        [
            'key' => 'catalog',
            'name' => 'Catalog',
            'tabs' => [
                [
                    'key' => 'general',
                    'name' => 'General',
                    'groups' => [
                        [
                            'key' => 'display',
                            'name' => 'Display',
                            'scopes' => ['global', 'store'],
                            'settings' => [
                                [
                                    'key' => 'items_per_page',
                                    'name' => 'Items Per Page',
                                    'type' => 'integer',
                                    'default_value' => '12',
                                    'scopes' => ['global', 'store'],
                                    'constraints' => [
                                        ['type' => 'required'],
                                        ['type' => 'min', 'options' => ['min' => 1]],
                                        ['type' => 'max', 'options' => ['max' => 100]],
                                    ],
                                    'storefront' => true,
                                    'secret' => false,
                                ],
                                [
                                    'key' => 'sort_order',
                                    'name' => 'Default Sort Order',
                                    'type' => 'enum',
                                    'default_value' => 'name_asc',
                                    'scopes' => ['global', 'store'],
                                    'options' => ['name_asc', 'name_desc', 'price_asc', 'price_desc'],
                                    'constraints' => [
                                        ['type' => 'choice', 'options' => ['choices' => ['name_asc', 'name_desc', 'price_asc', 'price_desc']]],
                                    ],
                                    'storefront' => true,
                                    'secret' => false,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'email',
                    'name' => 'Email',
                    'groups' => [
                        [
                            'key' => 'notifications',
                            'name' => 'Notifications',
                            'scopes' => ['global'],
                            'settings' => [
                                [
                                    'key' => 'sender_email',
                                    'name' => 'Sender Email',
                                    'type' => 'string',
                                    'default_value' => 'noreply@example.com',
                                    'scopes' => ['global'],
                                    'constraints' => [
                                        ['type' => 'required'],
                                        ['type' => 'email'],
                                    ],
                                    'storefront' => false,
                                    'secret' => false,
                                ],
                                [
                                    'key' => 'api_key',
                                    'name' => 'Email API Key',
                                    'type' => 'string',
                                    'default_value' => null,
                                    'scopes' => ['global'],
                                    'constraints' => [],
                                    'storefront' => false,
                                    'secret' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
