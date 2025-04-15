<?php

namespace RootedData\Util;

class ErrorHelper
{
    public static function pathToJsonPath(string $path): string
    {
        // Convert the path to JSONPath format
        $jsonPath = '$' . str_replace('/', '.', $path);
        return $jsonPath;
    }
}