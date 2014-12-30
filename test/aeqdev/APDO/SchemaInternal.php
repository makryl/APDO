<?php

return [
    'tree' =>
    [
        'comment' => ' Tree',
        'cols' =>
        [
            'id' =>
            [
                'type' => 'int',
                'null' => false,
                'comment' => '',
            ],
            'name' =>
            [
                'type' => 'string',
                'length' => '20',
                'null' => true,
                'comment' => ' Name',
            ],
        ],
        'pkey' =>
        [
            0 => 'id',
        ],
        'refs' =>
        [
            'tree_extra' =>
            [
                'parent' => 'parent',
                'tree' => 'tree',
            ],
            'fruit' =>
            [
                'tree' => 'tree',
            ],
        ],
    ],
    'tree_extra' =>
    [
        'comment' => ' Tree extra',
        'cols' =>
        [
            'id' =>
            [
                'type' => 'int',
                'null' => false,
                'comment' => '',
            ],
            'height' =>
            [
                'type' => 'int',
                'null' => true,
                'comment' => ' Height',
            ],
            'tree' =>
            [
                'type' => 'int',
                'null' => false,
                'comment' => ' Tree',
            ],
            'parent' =>
            [
                'type' => 'int',
                'null' => true,
                'comment' => ' Parent tree',
            ],
        ],
        'fkey' =>
        [
            'parent' => 'tree',
            'tree' => 'tree',
        ],
        'rkey' => [
            'tree' =>
            [
                'parent' => 'parent',
                'tree' => 'tree',
            ],
        ],
        'ukey' =>
        [
            'tree' => 'tree',
        ],
        'pkey' =>
        [
            0 => 'id',
        ],
    ],
    'fruit' =>
    [
        'comment' => ' Fruit',
        'cols' =>
        [
            'id' =>
            [
                'type' => 'int',
                'null' => false,
                'comment' => '',
            ],
            'name' =>
            [
                'type' => 'string',
                'length' => '20',
                'null' => false,
                'comment' => ' Name',
            ],
            'color' =>
            [
                'type' => 'string',
                'length' => '5',
                'null' => true,
                'comment' => ' Color',
            ],
            'tree' =>
            [
                'type' => 'int',
                'null' => true,
                'comment' => ' Tree',
            ],
        ],
        'fkey' =>
        [
            'tree' => 'tree',
        ],
        'rkey' =>
        [
            'tree' =>
            [
                'tree' => 'tree',
            ],
        ],
        'pkey' =>
        [
            0 => 'id',
        ],
    ],
];
