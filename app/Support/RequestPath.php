<?php

namespace App\Support;

use Illuminate\Http\Request;

final class RequestPath
{
    public static function prefixed(Request $request, string $path): string
    {
        $baseUrl = rtrim($request->getBaseUrl(), '/');

        if ($baseUrl === '') {
            $scriptName = (string) $request->server('SCRIPT_NAME', '');

            if ($scriptName !== '' && str_ends_with($scriptName, '/index.php')) {
                $scriptDirectory = dirname($scriptName);
                $baseUrl = $scriptDirectory === '/' || $scriptDirectory === '.'
                    ? ''
                    : rtrim($scriptDirectory, '/');
            }
        }

        $normalizedPath = '/' . ltrim($path, '/');

        return $baseUrl . $normalizedPath;
    }
}