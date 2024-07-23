<?php

namespace RootedData\Exception;

use JsonSchema\Validator;

/**
 * Exception class to throw for RootedJsonData objects that fail validation.
 *
 * Passes along the Opis Json Schema validation result.
 */
class ValidationException extends \InvalidArgumentException
{
    /**
     * Validation result report.
     */
    private Validator $validationResult;

    /**
     * @param string $message
     *   Exception message.
     * @param ValidationResult $validationResult
     *   Validation result report.
     */
    public function __construct(string $message, Validator $validation_result)
    {
        $this->validationResult = $validation_result;
        parent::__construct($message);
    }

    /**
     * Get the validation result object.
     *
     * @return ValidationResult
     *   Validation result report.
     */
    public function getResult(): Validator
    {
        return $this->validationResult;
    }
}
