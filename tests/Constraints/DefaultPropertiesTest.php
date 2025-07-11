<?php

declare(strict_types=1);

namespace JsonSchema\Tests\Constraints;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;

class DefaultPropertiesTest extends VeryBaseTestCase
{
    public function getValidTests(): array
    {
        return [
            /*
            // This test case was intended to check whether a default value can be applied for the
            // entire object, however testing this case is impossible, because there is no way to
            // distinguish between a deliberate top-level NULL and a top level that contains nothing.
            // As such, the assumption is that a top-level NULL is deliberate, and should not be
            // altered by replacing it with a default value.
            array(// #0 default value for entire object
                '',
                '{"default":"valueOne"}',
                '"valueOne"'
            ),
            */
            [// #0 default value in an empty object
                '{}',
                '{"properties":{"propertyOne":{"default":"valueOne"}}}',
                '{"propertyOne":"valueOne"}'
            ],
            [// #1 default value for top-level property
                '{"propertyOne":"valueOne"}',
                '{"properties":{"propertyTwo":{"default":"valueTwo"}}}',
                '{"propertyOne":"valueOne","propertyTwo":"valueTwo"}'
            ],
            [// #2 default value for sub-property
                '{"propertyOne":{}}',
                '{"properties":{"propertyOne":{"properties":{"propertyTwo":{"default":"valueTwo"}}}}}',
                '{"propertyOne":{"propertyTwo":"valueTwo"}}'
            ],
            [// #3 default value for sub-property with sibling
                '{"propertyOne":{"propertyTwo":"valueTwo"}}',
                '{"properties":{"propertyOne":{"properties":{"propertyThree":{"default":"valueThree"}}}}}',
                '{"propertyOne":{"propertyTwo":"valueTwo","propertyThree":"valueThree"}}'
            ],
            [// #4 default value for top-level property with type check
                '{"propertyOne":"valueOne"}',
                '{"properties":{"propertyTwo":{"default":"valueTwo","type":"string"}}}',
                '{"propertyOne":"valueOne","propertyTwo":"valueTwo"}'
            ],
            [// #5 default value for top-level property with v3 required check
                '{"propertyOne":"valueOne"}',
                '{"properties":{"propertyTwo":{"default":"valueTwo","required":"true"}}}',
                '{"propertyOne":"valueOne","propertyTwo":"valueTwo"}'
            ],
            [// #6 default value for top-level property with v4 required check
                '{"propertyOne":"valueOne"}',
                '{"properties":{"propertyTwo":{"default":"valueTwo"}},"required":["propertyTwo"]}',
                '{"propertyOne":"valueOne","propertyTwo":"valueTwo"}'
            ],
            [// #7 default value for an already set property
                '{"propertyOne":"alreadySetValueOne"}',
                '{"properties":{"propertyOne":{"default":"valueOne"}}}',
                '{"propertyOne":"alreadySetValueOne"}'
            ],
            [// #8 default item value for an array
                '["valueOne"]',
                '{"type":"array","items":[{},{"type":"string","default":"valueTwo"}]}',
                '["valueOne","valueTwo"]'
            ],
            [// #9 default item value for an empty array
                '[]',
                '{"type":"array","items":[{"type":"string","default":"valueOne"}]}',
                '["valueOne"]'
            ],
            [// #10 property without a default available
                '{"propertyOne":"alreadySetValueOne"}',
                '{"properties":{"propertyOne":{"type":"string"}}}',
                '{"propertyOne":"alreadySetValueOne"}'
            ],
            [// #11 default property value is an object
                '{"propertyOne":"valueOne"}',
                '{"properties":{"propertyTwo":{"default":{}}}}',
                '{"propertyOne":"valueOne","propertyTwo":{}}'
            ],
            [// #12 default item value is an object
                '[]',
                '{"type":"array","items":[{"default":{}}]}',
                '[{}]'
            ],
            [// #13 only set required values (draft-04)
                '{}',
                '{
                    "properties": {
                        "propertyOne": {"default": "valueOne"},
                        "propertyTwo": {"default": "valueTwo"}
                    },
                    "required": ["propertyTwo"]
                }',
                '{"propertyTwo":"valueTwo"}',
                Constraint::CHECK_MODE_ONLY_REQUIRED_DEFAULTS
            ],
            [// #14 only set required values (draft-03)
                '{}',
                '{
                    "properties": {
                        "propertyOne": {"default": "valueOne"},
                        "propertyTwo": {"default": "valueTwo", "required": true}
                    }
                }',
                '{"propertyTwo":"valueTwo"}',
                Constraint::CHECK_MODE_ONLY_REQUIRED_DEFAULTS
            ],
            [// #15 infinite recursion via $ref (object)
                '{}',
                '{"properties":{"propertyOne": {"$ref": "#","default": "valueOne"}}, "default": {}}',
                '{"propertyOne":{}}'
            ],
            [// #16 infinite recursion via $ref (array)
                '[]',
                '{"items":[{"$ref":"#","default":"valueOne"}], "default": []}',
                '[[]]'
            ],
            [// #17 default top value does not overwrite defined null
                'null',
                '{"default":"valueOne"}',
                'null'
            ],
            [// #18 default property value does not overwrite defined null
                '{"propertyOne":null}',
                '{"properties":{"propertyOne":{"default":"valueOne"}}}',
                '{"propertyOne":null}'
            ],
            [// #19 default value in an object is null
                '{}',
                '{"properties":{"propertyOne":{"default":null}}}',
                '{"propertyOne":null}'
            ],
            [// #20 default value in an array is null
                '[]',
                '{"items":[{"default":null}]}',
                '[null]'
            ],
            [// #21 items might be a schema (instead of an array of schema)
                '[{}]',
                '{"items":{"properties":{"propertyOne":{"default":"valueOne"}}}}',
                '[{"propertyOne":"valueOne"}]'
            ],
            [// #22 if items is not an array, it does not create a new item
                '[]',
                '{"items":{"properties":{"propertyOne":{"default":"valueOne"}}}}',
                '[]'
            ],
            [// #23 if items is a schema with a default value and minItems is present, fill the array
                '["a"]',
                '{"items":{"default":"b"}, "minItems": 3}',
                '["a","b","b"]'
            ],
        ];
    }

    /**
     * @dataProvider getValidTests
     */
    public function testValidCases($input, $schema, $expectOutput = null, $checkMode = 0): void
    {
        if (is_string($input)) {
            $inputDecoded = json_decode($input);
        } else {
            $inputDecoded = $input;
        }

        $checkMode |= Constraint::CHECK_MODE_APPLY_DEFAULTS;

        $schemaStorage = new SchemaStorage();
        $schemaStorage->addSchema('local://testSchema', json_decode($schema));
        $factory = new Factory($schemaStorage);
        $validator = new Validator($factory);

        $validator->validate($inputDecoded, json_decode('{"$ref": "local://testSchema"}'), $checkMode);

        $this->assertTrue($validator->isValid(), print_r($validator->getErrors(), true));

        if ($expectOutput !== null) {
            $this->assertEquals($expectOutput, json_encode($inputDecoded));
        }
    }

    /**
     * @dataProvider getValidTests
     */
    public function testValidCasesUsingAssoc($input, $schema, $expectOutput = null, $checkMode = 0): void
    {
        $input = json_decode($input, true);

        $checkMode |= Constraint::CHECK_MODE_TYPE_CAST;
        self::testValidCases($input, $schema, $expectOutput, $checkMode);
    }

    /**
     * @dataProvider getValidTests
     */
    public function testValidCasesUsingAssocWithoutTypeCast($input, $schema, $expectOutput = null, $checkMode = 0): void
    {
        $input = json_decode($input, true);

        self::testValidCases($input, $schema, $expectOutput, $checkMode);
    }

    public function testNoModificationViaReferences(): void
    {
        $input = json_decode('{}');
        $schema = json_decode('{"properties":{"propertyOne":{"default":"valueOne"}}}');

        $validator = new Validator();
        $validator->validate($input, $schema, Constraint::CHECK_MODE_TYPE_CAST | Constraint::CHECK_MODE_APPLY_DEFAULTS);

        $this->assertEquals('{"propertyOne":"valueOne"}', json_encode($input));

        $input->propertyOne = 'valueTwo';
        $this->assertEquals('valueOne', $schema->properties->propertyOne->default);
    }

    public function testLeaveBasicTypesAlone(): void
    {
        $input = json_decode('"ThisIsAString"');
        $schema = json_decode('{"properties": {"propertyOne": {"default": "valueOne"}}}');

        $validator = new Validator();
        $validator->validate($input, $schema, Constraint::CHECK_MODE_APPLY_DEFAULTS);

        $this->assertEquals('"ThisIsAString"', json_encode($input));

        $schema = json_decode('{"items":[{"type":"string","default":"valueOne"}]}');
        $validator->validate($input, $schema, Constraint::CHECK_MODE_APPLY_DEFAULTS);
        $this->assertEquals('"ThisIsAString"', json_encode($input));
    }
}
