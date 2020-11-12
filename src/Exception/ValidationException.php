<?php

namespace RootedData\Exception;

use Opis\JsonSchema\ValidationResult;

class ValidationException extends \InvalidArgumentException
{
    private $validationResult;

    public function __construct($message, ValidationResult $validationResult)
    {
        $this->validationResult = $validationResult;
        parent::__construct($message);
    }

    public function getResult()
    {
        return $this->validationResult;
    }
}