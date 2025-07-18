<?php

declare(strict_types=1);

namespace JsonSchema\Tests\Constraints;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

class SchemaValidationTest extends TestCase
{
    protected $validateSchema = true;

    public function getInvalidTests(): array
    {
        return [
            [// invalid v4 schema (uses v3 require)
                '{
                    "$schema": "http://json-schema.org/draft-04/schema#",
                    "properties": {
                        "propertyOne": {
                            "type": "string",
                            "required": true
                        }
                    }
                }'
            ],
            [// invalid v4 schema (uses v3 required), use default spec instead of specifying $schema
                '{
                    "properties": {
                        "propertyOne": {
                            "type": "string",
                            "required": true
                        }
                    }
                }'
            ]
        ];
    }

    public function getValidTests(): array
    {
        return [
            [// valid v4 schema (uses v4 require)
                '{
                    "$schema": "http://json-schema.org/draft-04/schema#",
                    "properties": {
                        "propertyOne": {
                            "type": "string"
                        }
                    },
                    "required": ["propertyOne"]
                }'
            ]
        ];
    }

    /**
     * @dataProvider getInvalidTests
     */
    public function testInvalidCases($schema): void
    {
        $input = json_decode('{"propertyOne":"valueOne"}');
        $schema = json_decode($schema);

        $v = new Validator();
        $errorMask = $v->validate($input, $schema, Constraint::CHECK_MODE_VALIDATE_SCHEMA);

        $this->assertTrue((bool) (Validator::ERROR_SCHEMA_VALIDATION & $errorMask));
        $this->assertGreaterThan(0, $v->numErrors(Validator::ERROR_SCHEMA_VALIDATION));
        $this->assertEquals(0, $v->numErrors(Validator::ERROR_DOCUMENT_VALIDATION));

        $this->assertFalse($v->isValid(), 'Validation succeeded for an invalid test case');
        foreach ($v->getErrors() as $error) {
            $this->assertEquals(Validator::ERROR_SCHEMA_VALIDATION, $error['context']);
        }
    }

    /**
     * @dataProvider getValidTests
     */
    public function testValidCases($schema): void
    {
        $input = json_decode('{"propertyOne":"valueOne"}');
        $schema = json_decode($schema);

        $v = new Validator();
        $errorMask = $v->validate($input, $schema, Constraint::CHECK_MODE_VALIDATE_SCHEMA);
        $this->assertEquals(0, $errorMask);

        if (!$v->isValid()) {
            var_dump($v->getErrors(Validator::ERROR_SCHEMA_VALIDATION));
        }
        $this->assertTrue($v->isValid(), 'Validation failed on a valid test case');
    }

    public function testNonObjectSchema(): void
    {
        $this->expectException(\JsonSchema\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Cannot validate the schema of a non-object');

        $this->testValidCases('"notAnObject"');
    }

    public function testInvalidSchemaException(): void
    {
        $this->expectException(\JsonSchema\Exception\InvalidSchemaException::class);
        $this->expectExceptionMessage('Schema did not pass validation');

        $input = json_decode('{}');
        $schema = json_decode('{"properties":{"propertyOne":{"type":"string","required":true}}}');

        $v = new Validator();
        $v->validate($input, $schema, Constraint::CHECK_MODE_VALIDATE_SCHEMA | Constraint::CHECK_MODE_EXCEPTIONS);
    }
}
