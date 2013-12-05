<?php

return [
    'tree' =>
    [
        'cols' =>
        [
            'id' =>
            [
                'type' => 'int',
                'null' => false,
            ],
            'name' =>
            [
                'type' => 'string',
                'length' => '20',
                'null' => true,
            ],
        ],
        'pkey' =>
        [
            0 => 'id',
        ],
        'refs' =>
        [
            'tree_extra' => 'tree_extra',
            'fruit' => 'fruit',
        ],
    ],
    'tree_extra' =>
    [
        'cols' =>
        [
            'id' =>
            [
                'type' => 'int',
                'null' => false,
            ],
            'height' =>
            [
                'type' => 'int',
                'null' => true,
            ],
            'tree_id' =>
            [
                'type' => 'int',
                'null' => false,
            ],
        ],
        'ukey' =>
        [
            'tree_id' => 'tree_id',
        ],
        'fkey' =>
        [
            'tree' => 'tree_id',
        ],
        'pkey' =>
        [
            0 => 'id',
        ],
    ],
    'fruit' =>
    [
        'cols' =>
        [
            'id' =>
            [
                'type' => 'int',
                'null' => false,
            ],
            'name' =>
            [
                'type' => 'string',
                'length' => '20',
                'null' => false,
            ],
            'color' =>
            [
                'type' => 'string',
                'length' => '5',
                'null' => true,
            ],
            'tree_id' =>
            [
                'type' => 'int',
                'null' => true,
            ],
        ],
        'fkey' =>
        [
            'tree' => 'tree_id',
        ],
        'pkey' =>
        [
            0 => 'id',
        ],
    ],
];
