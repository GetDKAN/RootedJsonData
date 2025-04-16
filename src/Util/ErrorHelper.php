<?php

namespace RootedData\Util;

use JsonSchema\Exception\ExceptionInterface;
use JsonSchema\Exception\InvalidSchemaException;
use RootedData\Exception\SchemaException;
use RootedData\Exception\ValidationException;

class ErrorHelper
{
    public static function pathToJsonPath(string $path): string
    {
        // Convert the path to JSONPath format
        $jsonPath = '$' . str_replace('/', '.', $path);
        return $jsonPath;
    }

        /**
     * Handle errors from the validator.
     *
     * @param \JsonSchema\Exception\ExceptionInterface $e
     *   A justinrainbow JSON Schema library exception.
     * @param array $errors 
     *   Validation errors array.
     * 
     * @throws \RootedData\Exception\SchemaException 
     *   If the schema is invalid.
     * @throws \RootedData\Exception\ValidationException 
     *   If any other exception is passed.
     */
    public static function handleErrors(ExceptionInterface $e, array $errors): void
    {
        // If somehow we got here without any errors, throw the exception.
        if (empty($errors)) {
            throw $e;
        }

        // Iterate through the errors and add a JSON path based on the pointer.
        foreach ($errors as $key => $error) {
            if (isset($error['pointer'])) {
                $errors[$key]['jsonpath'] = static::pathToJsonPath($error['pointer']);
            }
        }

        // Otherwise, throw our own exception that includes the error array.
        if ($e instanceof InvalidSchemaException) {
            throw new SchemaException("The JSON Schema is invalid.", $errors);
        }
        throw new ValidationException("JSON Schema validation failed.", $errors);
    }
}