<?php

namespace RootedData;

use InvalidArgumentException;
use JsonPath\InvalidJsonException;
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
     * @throws InvalidJsonException
     */
    public function __construct(string $json = "{}", string $schema = "{}")
    {
        $decoded = json_decode($json);

        if (!isset($decoded)) {
            throw new InvalidArgumentException("Invalid JSON: " . json_last_error_msg());
        }

        if (Schema::fromJsonString($schema)) {
            $this->schema = $schema;
        }

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
     * @param JsonObject $data
     *   JsonData object to validate against schema.
     * @param string $schema
     *   JSON Schema string.
     *
     * @return ValidationResult
     *   Validation result object, contains error report if invalid.
     */
    public static function validate(JsonObject $data, string $schema): ValidationResult
    {
        $opiSchema = Schema::fromJsonString($schema);
        $validator = new Validator();
        return $validator->schemaValidation(json_decode("{$data}"), $opiSchema);
    }

    /**
     * String version of object is the string version of the JsonObject.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->data->getJson();
    }
    
    /**
     * Return pretty-formatted JSON string
     *
     * @return string
     */
    public function pretty()
    {
        return $this->data->getJson(JSON_PRETTY_PRINT);
    }

    /**
     * @param string $path
     *   JSON Path
     *
     * @return mixed
     *   Result of JsonPath\JsonObject::__get()
     */
    public function get(string $path)
    {
        if ($this->__isset($path) === false) {
            return null;
        }
        return $this->data->get($path);
    }

    /**
     * @param string $path
     *
     * @return mixed
     *   Result of JsonPath\JsonObject::__get()
     * @see \JsonPath\JsonObject::__get()
     *
     */
    public function __get(string $path)
    {
        return $this->get($path);
    }

    /**
     * Set JSON Path to value.
     *
     * @param string $path
     * @param mixed $value
     *
     * @return JsonObject
     * @throws InvalidJsonException
     */
    public function set(string $path, $value)
    {
        $this->normalizeSetValue($value);
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

    private function normalizeSetValue(&$value)
    {
        if ($value instanceof RootedJsonData) {
            $value = $value->{"$"};
        }
    }

    /**
     * @see \JsonPath\JsonObject::__get()
     *
     * @param mixed $path
     * @param mixed $value
     *
     * @return JsonObject
     */
    public function __set($path, $value)
    {
        return $this->set($path, $value);
    }

    /**
     * Magic __isset method for a path.
     *
     * @param mixed $path
     *   Check if a property at this path is set or not.
     *
     * @return bool
     */
    public function __isset($path)
    {
        $notSmart = new JsonObject("{$this->data}");
        return $notSmart->get($path) ? true : false;
    }

    /**
     * Get the JSON Schema as a string.
     *
     * @return string
     *   The JSON Schema for this object.
     */
    public function getSchema()
    {
        return $this->schema;
    }
}
