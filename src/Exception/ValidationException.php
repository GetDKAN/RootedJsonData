<?php

namespace RootedData\Exception;

/**
 * Exception class to throw for RootedJsonData objects that fail validation.
 *
 * Passes along the justinrainbow errors array.
 */
class ValidationException extends \InvalidArgumentException
{
    /**
     * Validation errors.
     */
    private array $errors;

    /**
     * @param string $message
     *   Exception message.
     * @param array $errors
     *   Validation result report.
     */
    public function __construct(string $message, array $errors)
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    /**
     * Get the validation errors.
     *
     * @return array
     *   Validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get the validation result object.
     *
     * @deprecated since 2.0.0, use :::getErrors() instead.
     *
     * @return array
     *   Errors array.
     */
    public function getResult(): array
    {
        return $this->getErrors();
    }
}
