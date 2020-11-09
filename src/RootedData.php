<?php


namespace RootedData;

use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;

class RootedData implements \JsonSerializable {

  private $schema;
  private $data;

  public function __construct(string $json = "{}", string $schema = "{}") {
    $this->schema = $schema;
    $decoded = json_decode($json);

    if (!isset($decoded)) {
      throw new \Exception("Fix your JSON");
    }

    try {
      $opisSchema = Schema::fromJsonString($schema);
    }
    catch (\Exception $e) {
      throw new \Exception("Fix your Schema");
    }

    $validator = new Validator();
    $result = $validator->schemaValidation($decoded, $opisSchema);
    if (!$result->isValid()) {
      throw new \Exception("Fix your JSON");
    }

    $this->data = $decoded;
  }

  public function __get($name) {
    if (!isset($this->data->{$name})) {
      throw new \Exception("Property {$name} is not set");
    }

    if ($this->data->{$name} instanceof \stdClass) {
      $this->data->{$name} = new RootedData(json_encode($this->data->{$name}));
    }

    return $this->data->{$name};
  }

  public function __set($name, $value) {
    try {
      $rootedSchema = new RootedData($this->schema);
      $schema = $rootedSchema->properties->{$name};
    }
    catch (\Exception $e) {
      $schema = new RootedData();
    }

    $validator = new Validator();
    $result = $validator->schemaValidation($value,
      Schema::fromJsonString(json_encode($schema)));

    if (!$result->isValid()) {
      $keywordArgs = $result->getFirstError()->keywordArgs();
      throw new \Exception("{$name} expects a {$keywordArgs['expected']}");
    }

    $this->data->{$name} = $value;
  }

  public function jsonSerialize() {
    return $this->data;
  }

  public function __toString() {
    return json_encode($this);
  }

}