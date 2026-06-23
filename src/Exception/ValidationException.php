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
     */
    private ValidationResult $validationResult;

    /**
     * @param string $message
     *   Exception message.
     * @param ValidationResult $validationResult
     *   Validation result report.
     */
    public function __construct(string $message, ValidationResult $validationResult)
    {
        $this->validationResult = $validationResult;
        $errors = $validationResult->getErrors();
        $i = 1;
        foreach ($errors as $error) {
            $pointer = implode(" -> ", $error->dataPointer());
            $invalidValue = $error->data();
            if (is_array($invalidValue) || is_object($invalidValue)) {
                $invalidValue = json_encode($invalidValue);
            }
            $message .= "\n {$i}) {$pointer}: '{$invalidValue}'";
            $i++;
        }
        parent::__construct($message);
    }

    /**
     * Get the validation result object.
     *
     * @return ValidationResult
     *   Validation result report.
     */
    public function getResult(): ValidationResult
    {
        return $this->validationResult;
    }
}
