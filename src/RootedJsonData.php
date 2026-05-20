<?php

namespace RootedData;

use InvalidArgumentException;
use JsonPath\InvalidJsonException;
use JsonPath\JsonObject;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Exceptions\SchemaException;
use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Validator;
use RootedData\Exception\InvalidSchemaException;
use RootedData\Exception\ValidationException;

/**
 * RootedJsonData class. Instantiate for a service-like object for working with
 * JSON.
 */
class RootedJsonData
{

    private ?string $schema = null;
    private JsonObject $data;

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
        // opis v2 dropped Schema::fromJsonString() (Schema is now an interface).
        // Parse the schema here and reject anything that isn't a JSON object or
        // boolean, matching the JSON Schema spec and v1's eager-rejection semantics.
        $decodedSchema = json_decode($schema, false);
        if (!is_object($decodedSchema) && !is_bool($decodedSchema)) {
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidSchemaException("Invalid JSON schema: " . json_last_error_msg());
            }
            $type = $decodedSchema === null ? 'null' : gettype($decodedSchema);
            throw new InvalidSchemaException("JSON Schema must be an object or boolean, got {$type}");
        }

        // Eagerly parse the schema via opis's loadObjectSchema (the high-level
        // wrapper for parseRootSchema). This catches "shallow" structural
        // errors at the schema root — e.g. non-string $schema, non-absolute
        // $id — before any validation runs. Deeper errors are surfaced by
        // self::validate() below; opis defers sub-schema parsing via
        // LazySchema until the validator walks into them. Boolean schemas
        // are trivially valid and skip this step.
        if (is_object($decodedSchema)) {
            try {
                (new Validator())->loader()->loadObjectSchema($decodedSchema);
            } catch (SchemaException $e) {
                throw new InvalidSchemaException("Invalid JSON Schema: " . $e->getMessage(), 0, $e);
            }
        }
        $this->schema = $schema;

        // opis surfaces "deep" schema-structural errors (e.g. malformed
        // sub-schemas under `properties`) during validation rather than parse,
        // because parseRootSchema returns a LazySchema. Catch SchemaException
        // here so callers see a single InvalidSchemaException for any "this
        // isn't a valid JSON Schema" failure mode, regardless of when opis
        // detects it.
        try {
            $result = self::validate($json, $this->schema);
        } catch (SchemaException $e) {
            throw new InvalidSchemaException("Invalid JSON Schema: " . $e->getMessage(), 0, $e);
        }
        if (!$result->isValid()) {
            throw new ValidationException("JSON Schema validation failed.", $result);
        }

        $this->data = new JsonObject($json, true);
    }

    /**
     * Validate JSON.
     *
     * @param string $json
     *   JSON string to validate against schema.
     * @param string $schema
     *   JSON Schema string.
     *
     * @return ValidationResult
     *   Validation result object, contains error report if invalid.
     */
    public static function validate(string $json, string $schema): ValidationResult
    {
        $decoded = json_decode($json);

        if (!isset($decoded)) {
            throw new InvalidArgumentException("Invalid JSON: " . json_last_error_msg());
        }

        // Pre-decode the schema so opis v2 never sees a raw string. v2's
        // Validator::validate() tries URI parsing on string schemas first, which
        // would treat bare strings like "true", "false", or arbitrary identifiers
        // as URI references — producing misleading "Schema not found" errors and
        // (for URLs) attempting network fetches. Passing a decoded value bypasses
        // that entire path.
        $decodedSchema = json_decode($schema, false);
        $validator = new Validator();
        return $validator->validate($decoded, $decodedSchema);
    }

    /**
     * String version of object is the string version of the JsonObject.
     *
     * @return string
     */
    public function __toString(): string
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
        return $this->data->getJson(JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
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
            $message = self::buildPathErrorMessage($path, $result);
            throw new ValidationException($message, $result);
        }

        return $this->data->set($path, $value);
    }

    /**
     * Ensure consistent data type whether RootedJsonData or stdClass.
     *
     * @param mixed $value
     */
    private function normalizeSetValue(&$value): void
    {
        if ($value instanceof RootedJsonData) {
            $value = $value->{"$"};
        }
        if ($value instanceof \stdClass) {
            $value = new RootedJsonData(json_encode($value));
            $this->normalizeSetValue($value);
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
    public function __set(string $path, $value)
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
     * Magic __unset method, detects field from path.
     *
     * @param mixed $path
     *   Path to unset, including specific field.
     */
    public function __unset($path)
    {
        $exploded = explode(".", $path);
        $field = array_pop($exploded);
        $imploded = implode(".", $exploded);
        $this->remove($imploded, $field);
    }

    /**
     * Wrapper for JsonObject::remove() method, plus validation.
     *
     * @param mixed $path
     *   jsonPath.
     * @param mixed $field
     *   Field to remove.
     *
     * @return JsonObject
     *  Modified object (self).
     */
    public function remove($path, $field)
    {
        $validationJsonObject = new JsonObject((string) $this->data);
        $validationJsonObject->remove($path, $field);

        $result = self::validate($validationJsonObject, $this->schema);
        if (!$result->isValid()) {
            $message = self::buildPathErrorMessage($path, $result);
            throw new ValidationException($message, $result);
        }

        return $this->data->remove($path, $field);
    }

    /**
     * Walk the v2 validation error tree to a leaf and format a path-prefixed message.
     *
     * v2's $result->error() returns the *container* error (e.g. keyword `properties`),
     * while the actionable error (with the `expected` key for type mismatches) is on
     * a leaf sub-error. For type mismatches we preserve the v1 message format
     * "{path} expects a {type}"; for other keywords we fall back to the leaf message
     * formatted via ErrorFormatter so placeholders like {missing} are interpolated.
     */
    private static function buildPathErrorMessage(string $path, ValidationResult $result): string
    {
        $leaf = self::firstLeafError($result->error());
        $args = $leaf->args();
        if (isset($args['expected'])) {
            return "{$path} expects a {$args['expected']}";
        }
        $formatter = new ErrorFormatter();
        return "{$path}: " . $formatter->formatErrorMessage($leaf);
    }

    /**
     * Descend through subErrors() to the first leaf in a validation error tree.
     */
    private static function firstLeafError(ValidationError $error): ValidationError
    {
        while (!empty($subs = $error->subErrors())) {
            $error = $subs[0];
        }
        return $error;
    }

    /**
     * Get the JSON Schema as a string.
     *
     * @return string|null
     *   The JSON Schema for this object.
     */
    public function getSchema(): ?string
    {
        return $this->schema;
    }

    /**
     * Wrapper for JsonObject add() method
     *
     * @param string $path
     *   Path to an array
     * @param mixed $value
     *   Value to add
     * @param string $field
     *   Key if adding key/value pair
     *
     * @return JsonObject
     * @throws InvalidJsonException
     *
     * @see JsonPath\JsonObject::add()
     */
    public function add($path, $value, $field = null)
    {
        $this->normalizeSetValue($value);
        $validationJsonObject = new JsonObject((string) $this->data);
        $validationJsonObject->add($path, $value, $field);

        $result = self::validate($validationJsonObject, $this->schema);
        if (!$result->isValid()) {
            $message = "JSON Schema validation failed.";
            throw new ValidationException($message, $result);
        }

        return $this->data->add($path, $value, $field);
    }
}
