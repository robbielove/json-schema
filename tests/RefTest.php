<?php

declare(strict_types=1);

namespace JsonSchema\Tests;

use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

class RefTest extends TestCase
{
    public function dataRefIgnoresSiblings(): array
    {
        return [
            // #0 check that $ref is resolved and the instance is validated against
            // the referenced schema
            [
                '{
                    "definitions":{"test": {"type": "integer"}},
                    "properties": {
                        "propertyOne": {"$ref": "#/definitions/test"}
                    }
                }',
                '{"propertyOne": "not an integer"}',
                false
            ],
            // #1 check that sibling properties of $ref are ignored during validation
            [
                '{
                    "definitions":{
                        "test": {"type": "integer"}
                    },
                    "properties": {
                        "propertyOne": {
                            "$ref": "#/definitions/test",
                            "maximum": 5
                        }
                    }
                }',
                '{"propertyOne": 10}',
                true
            ],
            // #2 infinite-loop / unresolveable circular reference
            [
                '{
                    "definitions": {
                        "test1": {"$ref": "#/definitions/test2"},
                        "test2": {"$ref": "#/definitions/test1"}
                    },
                    "properties": {"propertyOne": {"$ref": "#/definitions/test1"}}
                }',
                '{"propertyOne": 5}',
                true,
                \JsonSchema\Exception\UnresolvableJsonPointerException::class
            ]
        ];
    }

    /** @dataProvider dataRefIgnoresSiblings */
    public function testRefIgnoresSiblings($schema, $document, $isValid, $exception = null): void
    {
        $document = json_decode($document);
        $schema = json_decode($schema);

        $v = new Validator();
        if ($exception) {
            $this->expectException($exception);
        }

        $v->validate($document, $schema);

        $this->assertEquals($isValid, $v->isValid());
    }
}
