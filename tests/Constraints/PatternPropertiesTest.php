<?php

declare(strict_types=1);

namespace JsonSchema\Tests\Constraints;

class PatternPropertiesTest extends BaseTestCase
{
    /** @var bool */
    protected $validateSchema = true;

    public function getInvalidTests(): \Generator
    {
        yield 'matches pattern but invalid schema for object' => [
            json_encode([
                'someobject' => [
                    'foobar' => 'foo',
                    'barfoo' => 'bar',
                ]
            ]),
            json_encode([
                'type' => 'object',
                'patternProperties' => [
                    '^someobject$' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'barfoo' => [
                                'type' => 'string',
                            ],
                        ]
                    ]
                ]
            ])
        ];
        yield 'Does not match pattern' => [
            json_encode([
                    'regex_us' => false,
            ]),
            json_encode([
                'type' => 'object',
                'patternProperties' => [
                    '^[a-z]+_(jp|de)$' => [
                        'type' => ['boolean']
                    ]
                ],
                'additionalProperties' => false
            ])
        ];
        yield 'Does not match pattern with unicode' => [
            json_encode([
                '猡猡獛' => false,
            ]),
            json_encode([
                'type' => 'object',
                'patternProperties' => [
                    '^[\\x{0080}-\\x{006FFF}]+$' => [
                        'type' => ['boolean']
                    ]
                ],
                'additionalProperties' => false
            ])
        ];
        yield 'An invalid regular expression pattern' => [
            json_encode([
                    'regex_us' => false,
            ]),
            json_encode([
                'type' => 'object',
                'patternProperties' => [
                    '^[a-z+_jp|de)$' => [
                        'type' => ['boolean']
                    ]
                ],
                'additionalProperties' => false
            ])
        ];
    }

    public function getValidTests(): \Generator
    {
        yield 'validates pattern schema' => [
            json_encode([
                'someobject' => [
                    'foobar' => 'foo',
                    'barfoo' => 'bar',
                ],
                'someotherobject' => [
                    'foobar' => 1234,
                ],
                '/products' => [
                    'get' => []
                ],
                '#products' => [
                    'get' => []
                ],
                '+products' => [
                    'get' => []
                ],
                '~products' => [
                    'get' => []
                ],
                '*products' => [
                    'get' => []
                ],
                '%products' => [
                    'get' => []
                ]
            ]),
            json_encode([
                'type' => 'object',
                'additionalProperties' => false,
                'patternProperties' => [
                    '^someobject$' => [
                        'type' => 'object',
                        'properties' => [
                            'foobar' => ['type' => 'string'],
                            'barfoo' => ['type' => 'string'],
                        ],
                    ],
                    '^someotherobject$' => [
                        'type' => 'object',
                        'properties' => [
                            'foobar' => ['type' => 'number'],
                        ],
                    ],
                    '^/' => [
                        'type' => 'object',
                        'properties' => [
                            'get' => ['type' => 'array']
                        ]
                    ],
                    '^#' => [
                        'type' => 'object',
                        'properties' => [
                            'get' => ['type' => 'array']
                        ]
                    ],
                    '^\+' => [
                        'type' => 'object',
                        'properties' => [
                            'get' => ['type' => 'array']
                        ]
                    ],
                    '^~' => [
                        'type' => 'object',
                        'properties' => [
                            'get' => ['type' => 'array']
                        ]
                    ],
                    '^\*' => [
                        'type' => 'object',
                        'properties' => [
                            'get' => ['type' => 'array']
                        ]
                    ],
                    '^%' => [
                        'type' => 'object',
                        'properties' => [
                            'get' => ['type' => 'array']
                        ]
                    ]
                ]
            ])
        ];
        yield [
            json_encode([
                    'foobar' => true,
                    'regex_us' => 'foo',
                    'regex_de' => 1234
            ]),
            json_encode([
                    'type' => 'object',
                    'properties' => [
                        'foobar' => ['type' => 'boolean']
                    ],
                    'patternProperties' => [
                        '^[a-z]+_(us|de)$' => [
                            'type' => ['string', 'integer']
                        ]
                    ],
                    'additionalProperties' => false
            ])
        ];
        yield 'Does match pattern with unicode' => [
            json_encode([
                'ðæſ' => 'unicode',
            ]),
            json_encode([
                'type' => 'object',
                'patternProperties' => [
                    '^[\\x{0080}-\\x{10FFFF}]+$' => [
                        'type' => ['string']
                    ]
                ],
                'additionalProperties' => false
            ])
        ];
    }
}
