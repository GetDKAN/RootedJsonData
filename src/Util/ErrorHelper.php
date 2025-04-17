<?php

namespace RootedData\Util;

use JsonSchema\Exception\ExceptionInterface;
use JsonSchema\Exception\InvalidSchemaException;
use RootedData\Exception\SchemaException;
use RootedData\Exception\ValidationException;

/**
 * Helper class for handling errors from the justinrainbow JSON Schema library.
 */
class ErrorHelper
{
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

        // Otherwise, throw our own exception that includes the error array.
        if ($e instanceof InvalidSchemaException) {
            throw new SchemaException("The JSON Schema is invalid.", $errors);
        }

        $toperror = reset($errors);
        $message = "JSON Schema validation failed: " . ($toperror['message'] ?? '');

        throw new ValidationException($message, $errors);
    }

}