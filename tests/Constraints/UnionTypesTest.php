<?php

declare(strict_types=1);

namespace JsonSchema\Tests\Constraints;

class UnionTypesTest extends BaseTestCase
{
    /** @var bool */
    protected $validateSchema = true;

    public function getInvalidTests(): \Generator
    {
        yield [
            '{
              "stringOrNumber":4.8,
              "booleanOrNull":5
            }',
            '{
              "type":"object",
              "properties":{
                "stringOrNumber":{"type":["string","number"]},
                "booleanOrNull":{"type":["boolean","null"]}
              }
            }'
        ];
    }

    public function getValidTests(): \Generator
    {
        yield [
            '{
              "stringOrNumber":4.8,
              "booleanOrNull":false
            }',
            '{
              "type":"object",
              "properties":{
                "stringOrNumber":{"type":["string","number"]},
                "booleanOrNull":{"type":["boolean","null"]}
              }
            }'
        ];
    }
}
