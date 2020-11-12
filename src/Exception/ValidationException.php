<?php

namespace RootedData\Exception;

use Opis\JsonSchema\ValidationResult;

/**
 * Exception class to throw for RootedJsonData objects that fail validation.
 *
 * Passes along the Opis Json Schema validation result.
 */
class ValidationException extends \InvalidArgumentException
{
    /**
     * Validation result report.
     *
     * @var Opis\JsonSchema\ValidationResult
     */
    private $validationResult;

    /**
     * @param string $message
     *   Exception message.
     * @param ValidationResult $validationResult
     *   Validation result report.
     */
    public function __construct(string $message, ValidationResult $validationResult)
    {
        $this->validationResult = $validationResult;
        parent::__construct($message);
    }

    /**
     * Get the validation result object.
     *
     * @return Opis\JsonSchema\ValidationResult
     *   Validation result report.
     */
    public function getResult()
    {
        return $this->validationResult;
    }
}
