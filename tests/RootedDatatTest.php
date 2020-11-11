<?php


namespace RootedDataTest;


use PHPUnit\Framework\TestCase;
use RootedData\RootedData;

class RootedDatatTest extends TestCase {

  /**

  -- We want an interface/API to modify the values of properties of a data object without having to worry about the schema constraints being violated.
  -- The interface should make access to the serialized json or php structure available.
  -- All data properties are private, modification must be made through setter methods/function that validate any modifications.
  -- Allow bulk modifications of the RootedData.

  Consolidate our business logic around this new way of working with JSON data.
   * We are assuming that we start with a json object.
   * Can we use the schema to inform non-existing structure?
   * Pass schema to sub rooteddata objects.
   * sub rooteddata objects can not set their own schema
   * we should fail when the schema is violated.
   * Test array manipulations
   */

  public function testSeamlessExperience() {

    // we want a seemless experience between json strings and their structure they represent.
    $data = new RootedData();
    $data->set("$.title", "Hello");
    $this->assertEquals('{"title":"Hello"}', "{$data}");

    $data->set("$.publisher.name", "Frank");

    $this->assertEquals('{"title":"Hello","publisher":{"name":"Frank"}}', (string) $data);
  }

  public function testMagicGetterAndSetter() {
    $data = new RootedData();
    $data->{"$.title"} = "Hello";
    $this->assertEquals('{"title":"Hello"}', "{$data}");
    $this->assertEquals("Hello", $data->{"$.title"});
  }

  public function testAccessToNonExistentProperties() {
    $this->expectExceptionMessage("Property $.city is not set");
    $data = new RootedData();
    $city = $data->get("$.city");
  }

  public function testJsonFormat() {
    // We want our data to keep its integrity in the in-betweens: From input to output.
    $this->expectExceptionMessage("Fix your JSON");
    $json = "{";
    new RootedData($json);
  }

  public function testJsonIntegrityFailure() {
    $this->expectExceptionMessage("Fix your JSON");
    $json = '{"number":"hello"}';
    $schema = '{
      "type": "object",
      "properties": {
        "number":      { "type": "number" }
      }
    }';
    new RootedData($json, $schema);
  }

  public function testSchemaIntegrity() {
    $this->expectExceptionMessage("Fix your Schema");
    $json = '{"number":"hello"}';
    $schema = '{
      "type": "object",
      "properties": {
        "number":      { "type": "number" }
      }';
    new RootedData($json, $schema);
  }

  public function testJsonIntegrity() {
    $json = '{"number":51}';
    $schema = '{
      "type": "object",
      "properties": {
        "number":      { "type": "number" }
      }
    }';
    $data = new RootedData($json, $schema);
    $this->assertEquals($json, "{$data}");
  }

  public function testJsonIntegrityFailureAfterChange() {
    $this->expectExceptionMessage("\$.number expects a number");

    $json = '{"number":51}';
    $schema = '{
      "type": "object",
      "properties": {
        "number":      { "type": "number" }
      }
    }';
    $data = new RootedData($json, $schema);
    $this->assertEquals($json, "{$data}");

    $data->set("$.number", "Alice");
  }

  public function testJsonPathGetter() {
    $json = '{"container":{"number":51}}';
    $data = new RootedData($json);
    $this->assertEquals(51, $data->get("$.container.number"));
  }

  public function testJsonPathSetter() {
    $json = '{"container":{"number":51}}';
    $data = new RootedData($json);
    $data->set("$.container.number", 52);
    $this->assertEquals(52, $data->get("$.container.number"));
  }

}