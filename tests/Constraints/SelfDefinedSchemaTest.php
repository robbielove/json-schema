<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Tests\Constraints;

use JsonSchema\Validator;

class SelfDefinedSchemaTest extends BaseTestCase
{
    protected $validateSchema = true;

    public function getInvalidTests(): array
    {
        return [
            [
                '{
                    "$schema": {
                        "$schema": "http://json-schema.org/draft-04/schema#",
                        "properties": {
                            "name": {
                                "type": "string"
                            },
                            "age" : {
                                "type": "integer",
                                "maximum": 25
                            }
                        }
                    },
                    "name" : "John Doe",
                    "age" : 30,
                    "type" : "object"
                }',
                ''
            ]
        ];
    }

    public function getValidTests(): array
    {
        return [
            [
                '{
                    "$schema": {
                        "$schema": "http://json-schema.org/draft-04/schema#",
                        "properties": {
                            "name": {
                                "type": "string"
                            },
                            "age" : {
                                "type": "integer",
                                "maximum": 125
                            }
                        }
                    },
                    "name" : "John Doe",
                    "age" : 30,
                    "type" : "object"
                }',
                ''
            ]
        ];
    }

    public function testInvalidArgumentException(): void
    {
        $value = json_decode('{}');
        $schema = json_decode('');

        $v = new Validator();

        $this->expectException('\JsonSchema\Exception\InvalidArgumentException');

        $v->validate($value, $schema);
    }
}
