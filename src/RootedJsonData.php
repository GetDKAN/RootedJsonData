<?php

namespace RootedData;

use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use JsonPath\JsonObject;
use Opis\JsonSchema\ValidationResult;
use RootedData\Exception\ValidationException;

/**
 * RootedJsonData class. Instantiate for a service-like object for working with
 * JSON.
 */
class RootedJsonData
{

    private $schema;
    private $data;

    /**
     * Constructor method.
     *
     * @param string $json
     *   String of JSON data.
     * @param string $schema
     *   JSON schema document for validation.
     */
    public function __construct(string $json = "{}", string $schema = "{}")
    {
        $decoded = json_decode($json);

        if (!isset($decoded)) {
            throw new \InvalidArgumentException("Invalid JSON: " . json_last_error_msg());
        }

        $this->schema = Schema::fromJsonString($schema);

        $data = new JsonObject($json, true);
        $result = self::validate($data, $this->schema);
        if (!$result->isValid()) {
            throw new ValidationException("JSON Schema validation failed.", $result);
        }

        $this->data = $data;
    }

    /**
     * Validate a JsonObject.
     *
     * @param JsonPath\JsonObject $data
     *   JsonData object to validate against schema.
     * @param Opis\JsonSchema\Schema $schema
     *   And Opis Json-Schema schema object to validate data against.
     *
     * @return Opis\JsonSchema\ValidationResult
     *   Validation result object, contains error report if invalid.
     */
    public static function validate(JsonObject $data, Schema $schema): ValidationResult
    {
        $validator = new Validator();
        $result = $validator->schemaValidation(json_decode("{$data}"), $schema);
        return $result;
    }

    /**
     * String version of object is the string version of the JsonObject.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->data;
    }

    /**
     * @param string $path
     *   JSON Path
     *
     * @return mixed
     *   Result of JsonPath\JsonObject::__get()
     */
    public function get($path)
    {
        $result = $this->data->get($path);
        if ($result === false) {
            throw new \Exception("Property {$path} is not set");
        }
        return $result;
    }

    /**
     * @see JsonPath\JsonObject::__get()
     *
     * @param string $path
     *
     * @return mixed
     *   Result of JsonPath\JsonObject::__get()
     */
    public function __get($path)
    {
        return $this->data->get($path);
    }

    /**
     * Set JSON Path to value.
     *
     * @param string $path
     * @param mixed $value
     *
     * @return JsonPath\JsonObject
     */
    public function set($path, $value)
    {
        $validationJsonObject = new JsonObject((string) $this->data);
        $validationJsonObject->set($path, $value);

        $result = self::validate($validationJsonObject, $this->schema);
        if (!$result->isValid()) {
            $keywordArgs = $result->getFirstError()->keywordArgs();
            $message = "{$path} expects a {$keywordArgs['expected']}";
            throw new ValidationException($message, $result);
        }

        return $this->data->set($path, $value);
    }

    /**
     * @see JsonPath\JsonObject::__get()
     *
     * @param mixed $path
     * @param mixed $value
     *
     * @return JsonPath\JsonObject
     */
    public function __set($path, $value)
    {
        return $this->data->set($path, $value);
    }
}
