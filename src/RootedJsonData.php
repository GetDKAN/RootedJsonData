<?php


namespace RootedData;

use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use JsonPath\JsonObject;

/**
 * [Description RootedData]
 */
class RootedJsonData
{

    private $schema;
    private $data;

    public function __construct(string $json = "{}", string $schema = "{}")
    {
        $decoded = json_decode($json);

        if (!isset($decoded)) {
            throw new \Exception("Fix your JSON");
        }

        try {
            $this->schema = Schema::fromJsonString($schema);
        } catch (\Exception $e) {
            throw new \Exception("Fix your Schema");
        }

        $data = new JsonObject($json, true);
        if (!self::validate($data, $this->schema)->isValid()) {
            throw new \Exception("Fix your JSON");
        }

        $this->data = $data;
    }

    public static function validate(JsonObject $data, Schema $schema)
    {
        $validator = new Validator();
        $result = $validator->schemaValidation(json_decode("{$data}"), $schema);
        return $result;
    }

    public function __toString()
    {
        return (string) $this->data;
    }

    public function get($path)
    {
        $result = $this->data->get($path);
        if ($result === false) {
            throw new \Exception("Property {$path} is not set");
        }
        return $result;
    }

    public function __get($jsonPath)
    {
        return $this->data->get($jsonPath);
    }

    public function set($path, $value)
    {
        $validationJsonObject = new JsonObject((string) $this->data);
        $validationJsonObject->set($path, $value);

        $result = self::validate($validationJsonObject, $this->schema);
        if (!$result->isValid()) {
            $keywordArgs = $result->getFirstError()->keywordArgs();
            throw new \Exception("{$path} expects a {$keywordArgs['expected']}");
        }

        return $this->data->set($path, $value);
    }

    public function __set($jsonPath, $value)
    {
        return $this->data->set($jsonPath, $value);
    }
}
