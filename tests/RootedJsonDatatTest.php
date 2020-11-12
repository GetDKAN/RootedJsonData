<?php


namespace RootedDataTest;

use PHPUnit\Framework\TestCase;
use RootedData\RootedJsonData;
use Opis\JsonSchema\Exception\InvalidSchemaException;
use RootedData\Exception\ValidationException;

class RootedJsonDataTest extends TestCase
{
    public function testSeamlessExperience()
    {
        $data = new RootedJsonData();
        $data->set("$.title", "Hello");
        $this->assertEquals('{"title":"Hello"}', "{$data}");

        $data->set("$.publisher.name", "Frank");

        $this->assertEquals('{"title":"Hello","publisher":{"name":"Frank"}}', (string) $data);
    }

    public function testMagicGetterAndSetter()
    {
        $data = new RootedJsonData();
        $data->{"$.title"} = "Hello";
        $this->assertEquals('{"title":"Hello"}', "{$data}");
        $this->assertEquals("Hello", $data->{"$.title"});
    }

    public function testAccessToNonExistentProperties()
    {
        $this->expectExceptionMessage("Property $.city is not set");
        $data = new RootedJsonData();
        $city = $data->get("$.city");
    }

    public function testJsonFormat()
    {
      // We want our data to keep its integrity in the in-betweens: From input to output.
        $this->expectExceptionMessage("Invalid JSON: Syntax error");
        $json = "{";
        new RootedJsonData($json);
    }

    public function testJsonIntegrityFailure()
    {
        $json = '{"number":"hello"}';
        $schema = '{"type": "object","properties": {"number":{ "type": "number" }}}';
        try {
            new RootedJsonData($json, $schema);
        } catch (ValidationException $e) {
            $this->assertInstanceOf(ValidationException::class, $e);
            $this->assertEquals("type", $e->getResult()->getFirstError()->keyword());
        }
    }

    public function testSchemaIntegrity()
    {
        $this->expectException(InvalidSchemaException::class);
        $json = '{"number":"hello"}';
        $schema = '{"type":"object","properties":{"number":{"type":"number"}}';
        new RootedJsonData($json, $schema);
    }

    public function testJsonIntegrity()
    {
        $json = '{"number":51}';
        $schema = '{"type": "object","properties":{"number":{"type":"number"}}}';
        $data = new RootedJsonData($json, $schema);
        $this->assertEquals($json, "{$data}");
    }

    public function testJsonIntegrityFailureAfterChange()
    {
        $this->expectExceptionMessage("\$.number expects a number");

        $json = '{"number":51}';
        $schema = '{"type":"object","properties": {"number":{ "type":"number"}}}';
        $data = new RootedJsonData($json, $schema);
        $this->assertEquals($json, "{$data}");

        $data->set("$.number", "Alice");
    }

    public function testJsonPathGetter()
    {
        $json = '{"container":{"number":51}}';
        $data = new RootedJsonData($json);
        $this->assertEquals(51, $data->get("$.container.number"));
    }

    public function testJsonPathSetter()
    {
        $json = '{"container":{"number":51}}';
        $data = new RootedJsonData($json);
        $data->set("$.container.number", 52);
        $this->assertEquals(52, $data->get("$.container.number"));
    }
}
