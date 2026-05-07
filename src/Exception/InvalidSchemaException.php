<?php

namespace RootedData\Exception;

/**
 * Thrown when a JSON Schema string passed to RootedJsonData is malformed.
 *
 * Replaces the v1 dependency on Opis\JsonSchema\Exception\InvalidSchemaException,
 * which was removed in opis/json-schema 2.x. Extends \InvalidArgumentException
 * to preserve catch-block compatibility for callers that did not import the opis class.
 */
class InvalidSchemaException extends \InvalidArgumentException
{
}
