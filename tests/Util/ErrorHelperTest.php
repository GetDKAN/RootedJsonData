<?php


namespace RootedDataTest\Tests\Util;

use JsonSchema\Exception\InvalidArgumentException;
use JsonSchema\Exception\InvalidSchemaException;
use PHPUnit\Framework\TestCase;
use RootedData\Exception\SchemaException;
use RootedData\Exception\ValidationException;
use RootedData\Util\ErrorHelper;

class RootedJsonDataTest extends TestCase
{
    public function testHandleErrors(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid JSON');

        $errors = [
            [
                'message' => 'Invalid JSON',
            ],
        ];

        $exception = new InvalidArgumentException("Invalid JSON'");
        ErrorHelper::handleErrors($exception, $errors);
    }

    public function testHandleSchemaErrors(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('The JSON Schema is invalid.');

        $errors = [
            [
                'message' => 'Invalid schema',
            ],
        ];

        $exception = new InvalidSchemaException('Invalid schema');
        ErrorHelper::handleErrors($exception, $errors);
    }


    public function testHandleNoErrors(): void
    {
        $this->expectException(InvalidSchemaException::class);

        $errors = [];

        $exception = new InvalidSchemaException('Invalid schema');
        ErrorHelper::handleErrors($exception, $errors);
    }
}