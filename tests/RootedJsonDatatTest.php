<?php


namespace RootedDataTest;

use PHPUnit\Framework\TestCase;
use Opis\JsonSchema\Exceptions\ParseException;
use RootedData\Exception\InvalidSchemaException;
use RootedData\Exception\ValidationException;
use RootedData\RootedJsonData;

class RootedJsonDataTest extends TestCase
{
    public function testJsonInOut(): void
    {
        $data = new RootedJsonData();
        $data->set("$.title", "Hello");
        $this->assertEquals('{"title":"Hello"}', "{$data}");

        $data->set("$.publisher.name", "Frank");

        $this->assertEquals('{"title":"Hello","publisher":{"name":"Frank"}}', (string) $data);
    }

    public function testMagicGetterAndSetter(): void
    {
        $data = new RootedJsonData();
        $data->{"$.title"} = "Hello";
        $this->assertEquals('{"title":"Hello"}', "{$data}");
        $this->assertEquals("Hello", $data->{"$.title"});
    }

    public function testBracketSyntax(): void
    {
        $data = new RootedJsonData();
        $data->{"$[title]"} = "Hello";
        $this->assertEquals('{"title":"Hello"}', "{$data}");
        $this->assertEquals("Hello", $data->{"$[title]"});
    }

    public function testAccessToNonExistentProperties(): void
    {
        $data = new RootedJsonData();
        $this->assertNull($data->get("$.city"));
        $this->assertFalse(isset($data->{"$.city"}));
    }

    public function testJsonFormat(): void
    {
      // We want our data to keep its integrity in the in-betweens: From input to output.
        $this->expectExceptionMessage("Invalid JSON: Syntax error");
        $json = "{";
        new RootedJsonData($json);
    }

    public function testJsonIntegrityFailure(): void
    {
        $json = '{"number":"hello"}';
        $schema = '{"type": "object","properties": {"number":{ "type": "number" }}}';
        try {
            new RootedJsonData($json, $schema);
        } catch (ValidationException $e) {
            $this->assertInstanceOf(ValidationException::class, $e);
            // v2's root error is the *container* keyword (e.g. `properties`); walk to a leaf.
            $rootError = $e->getResult()->error();
            $leaf = $rootError;
            while (!empty($subs = $leaf->subErrors())) {
                $leaf = $subs[0];
            }
            $this->assertEquals("type", $leaf->keyword());
        }
    }

    // Schema does not follow JSON Schema spec
    public function testSchemaIntegrity(): void
    {
        // Structural schema errors surface at construction time wrapped in
        // our InvalidSchemaException. opis returns a LazySchema, so this
        // particular error (properties as array) is detected during the
        // validate() step rather than the loadObjectSchema step — both are
        // caught and rewrapped uniformly.
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessageMatches('/^Invalid JSON Schema: /');
        $json = '{"number":"hello"}';
        // Keyword "properties" should be an object not an array.
        $schema = '{"type":"object","properties":[{"number":{"type":"number"}}]}';
        new RootedJsonData($json, $schema);
    }

    /**
     * Some structural schema errors are caught eagerly by loadObjectSchema()
     * (the parseRootSchema wrapper) before any validation runs — covering
     * the "shallow" catch branch in the constructor.
     */
    public function testEagerSchemaParseError(): void
    {
        try {
            // $schema (the JSON Schema draft URI) must be a string. opis
            // rejects this in parseRootSchema before returning a LazySchema.
            new RootedJsonData('{}', '{"$schema": 42}');
            $this->fail("Expected InvalidSchemaException for non-string \$schema");
        } catch (InvalidSchemaException $e) {
            $this->assertStringStartsWith('Invalid JSON Schema: ', $e->getMessage());
            $this->assertInstanceOf(ParseException::class, $e->getPrevious());
        }
    }

    /**
     * Sub-schema structural errors surface via the validate() catch (deferred
     * by opis's LazySchema). Confirms the *second* SchemaException catch in
     * the constructor and that the original ParseException is preserved on
     * the exception chain.
     */
    public function testSchemaParsedAtConstruction(): void
    {
        $json = '{}';
        // "properties" must be an object, not a string. Surfaces lazily.
        $schema = '{"type":"object","properties":"not-an-object"}';
        try {
            new RootedJsonData($json, $schema);
            $this->fail("Expected InvalidSchemaException for structurally-invalid schema");
        } catch (InvalidSchemaException $e) {
            $this->assertStringStartsWith('Invalid JSON Schema: ', $e->getMessage());
            // InvalidKeywordException extends ParseException — assertion holds either way.
            $this->assertInstanceOf(ParseException::class, $e->getPrevious());
        }
    }

    // Schema is not even valid JSON
    public function testSchemaJsonIntegrity(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $json = '{"number":"hello"}';
        // Missing a closing bracket
        $schema = '{"type":"object","properties":{"number":{"type":"number"}}';
        new RootedJsonData($json, $schema);
    }

    /**
     * JSON Schema spec allows boolean schemas: `true` accepts everything.
     */
    public function testBooleanSchemaTrueAcceptsAll(): void
    {
        $data = new RootedJsonData('{"anything":[1,2,3]}', 'true');
        $this->assertEquals([1, 2, 3], $data->get('$.anything'));
    }

    /**
     * JSON Schema spec allows boolean schemas: `false` rejects everything.
     */
    public function testBooleanSchemaFalseRejectsAll(): void
    {
        $this->expectException(ValidationException::class);
        new RootedJsonData('{"anything":1}', 'false');
    }

    /**
     * Schemas that decode to non-object, non-boolean values (null, array, string,
     * number) are spec violations and must be rejected at construction time.
     */
    public function testSchemaTypeRejection(): void
    {
        $cases = [
            'null literal' => 'null',
            'array' => '[]',
            'string' => '"hello"',
            'number' => '42',
        ];
        foreach ($cases as $label => $schema) {
            try {
                new RootedJsonData('{}', $schema);
                $this->fail("Expected InvalidSchemaException for {$label} schema");
            } catch (InvalidSchemaException $e) {
                $this->assertStringContainsString('must be an object or boolean', $e->getMessage());
            }
        }
    }

    public function testJsonIntegrity(): void
    {
        $json = '{"number":51}';
        $schema = '{"type": "object","properties":{"number":{"type":"number"}}}';
        $data = new RootedJsonData($json, $schema);
        $this->assertEquals($json, "{$data}");
    }

    public function testJsonIntegrityFailureAfterChange(): void
    {
        $this->expectExceptionMessage("\$.number expects a number");

        $json = '{"number":51}';
        $schema = '{"type":"object","properties": {"number":{ "type":"number"}}}';
        $data = new RootedJsonData($json, $schema);

        $data->set("$.number", "Alice");
    }

    /**
     * Do schemas still work with magic setter?
     */
    public function testJsonIntegrityFailureMagicSetter(): void
    {
        $this->expectExceptionMessage("\$[number] expects a number");

        $json = '{"number":51}';
        $schema = '{"type":"object","properties": {"number":{ "type":"number"}}}';
        $data = new RootedJsonData($json, $schema);
        $data->{"$[number]"} = "Alice";
    }

    /**
     * Simple get value from JSON path.
     */
    public function testJsonPathGetter(): void
    {
        $json = '{"container":{"number":51}}';
        $data = new RootedJsonData($json);
        $this->assertEquals(51, $data->get("$.container.number"));
    }

    /**
     * Simple set by JSON path.
     */
    public function testJsonPathSetter(): void
    {
        $json = '{"container":{"number":51}}';
        $data = new RootedJsonData($json);
        $data->set("$.container.number", 52);
        $this->assertEquals(52, $data->get("$.container.number"));
    }

    /**
     * Adding JSON structures in multiple formats should have predictable results.
     */
    public function testAddJsonData(): void
    {
        // Test adding RootedJsonData structure.
        $json = '{}';
        $containerSchema = '{"type":"object","properties":{"number":{"type":"number"}}}';
        $schema = '{"type":"object","properties":{"container":'.$containerSchema.'}}';
        $subJson = '{"number":51}';
        $data = new RootedJsonData($json, $schema);
        $data->set("$.container", new RootedJsonData($subJson));
        $this->assertEquals(51, $data->get("$.container.number"));
        
        // If we add stdClass object, it should be work and be an array.
        $data2 = new RootedJsonData($json, $schema);
        $data2->set("$.container", json_decode($subJson));
        $this->assertEquals(51, $data2->get("$.container.number"));
        $this->assertIsArray($data2->get("$.container"));
    }
    
    /**
     * getSchema() should return the same string that was provided to constructor.
     */
    public function testSchemaGetter(): void
    {
        $json = '{"number":51}';
        $schema = '{"type": "object","properties":{"number":{"type":"number"}}}';
        $data = new RootedJsonData($json, $schema);
        $this->assertEquals($schema, $data->getSchema());
    }

    /**
     * Regular string should be one line, pretty() should return multiple lines.
     */
    public function testPretty(): void
    {
        $json = '{"number":51}';
        $data = new RootedJsonData($json);
        $this->assertEquals(0, substr_count("$data", "\n"));
        $this->assertEquals(2, substr_count($data->pretty(), "\n"));
    }

    /**
     * Adds string elements to an array.
     */
    public function testAdd(): void
    {
        $json = '{"numbers":["zero","one","two"]}';
        $data = new RootedJsonData($json);
        $data->add("$.numbers", "three");
        $this->assertEquals("three", $data->{"$.numbers[3]"});
    }

    /**
     * Adds object elements to an array.
     */
    public function testAddObject(): void
    {
        $json = '{"numbers":[{"name":"zero","value":0}]}';
        $data = new RootedJsonData($json);
        $data->add("$.numbers", ["name" => "one", "value" => 1]);
        $this->assertEquals("one", current($data->{"$.numbers[?(@.value == 1)].name"}));
    }

    /**
     * If a schema is provided, adding elements that match array should work,
     * elements that violate schema will fail.
     */
    public function testAddWithSchema(): void
    {
        $json = '{"numbers":["zero","one"]}';
        $schema = '{"type": "object","properties":{"numbers":{"type":"array","items":{"type":"string"}}}}';
        $data = new RootedJsonData($json, $schema);
        $data->add("$.numbers", "two");
        $this->assertEquals("two", $data->{"$.numbers[2]"});
        $this->expectException(ValidationException::class);
        $data->add("$.numbers", ["name" => "three", "value" => 3]);
    }

    /**
     * If a schema is provided, adding elements that match array should work,
     * elements that violate schema will fail.
     */
    public function testRemove(): void
    {
        $json = '{"field1":"foo","field2":"bar"}';
        $schema = '
            {
                "type": "object",
                "required":["field1"],
                "properties": {
                    "field1": {
                        "type":"string"
                    },
                    "field2": {
                        "type":"string"
                    }
                }
            }';
        $data = new RootedJsonData($json, $schema);
        $data->remove("$", "field2");
        $this->assertEquals("foo", $data->{"$.field1"});
        $this->expectException(ValidationException::class);
        $data->remove("$", "field1");
    }

    /**
     * Non-`type` validation errors should produce a path-prefixed, interpolated message.
     *
     * Covers the fallback branch of buildPathErrorMessage() when the failing leaf
     * keyword is something other than `type` (here: `required`). The message is
     * formatted through opis ErrorFormatter so placeholders like {missing} are
     * substituted with actual values.
     */
    public function testValidationFallbackMessageForRequiredKeyword(): void
    {
        $json = '{"field1":"foo","field2":"bar"}';
        $schema = '{
            "type":"object",
            "required":["field1"],
            "properties":{
                "field1":{"type":"string"},
                "field2":{"type":"string"}
            }
        }';
        $data = new RootedJsonData($json, $schema);
        try {
            $data->remove("$", "field1");
            $this->fail("Expected ValidationException for removing a required field");
        } catch (ValidationException $e) {
            $this->assertStringStartsWith('$: ', $e->getMessage());
            $this->assertStringContainsString('required', $e->getMessage());
            $this->assertStringContainsString('field1', $e->getMessage());
            $this->assertStringNotContainsString('{missing}', $e->getMessage());
        }
    }

    /**
     * If a schema is provided, adding elements that match array should work,
     * elements that violate schema will fail.
     */
    public function testUnset(): void
    {
        $json = '{"field1":"foo","field2":"bar"}';
        $schema = '
            {
                "type": "object",
                "required":["field1"],
                "properties": {
                    "field1": {
                        "type":"string"
                    },
                    "field2": {
                        "type":"string"
                    }
                }
            }';
        $data = new RootedJsonData($json, $schema);
        unset($data->{"$.field2"});
        $this->assertEquals("foo", $data->{"$.field1"});
        $this->expectException(ValidationException::class);
        unset($data->{"$.field1"});
    }
}
