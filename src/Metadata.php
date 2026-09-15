<?php

declare(strict_types=1);
namespace ActivitySmith;

final class Metadata
{
    public static function normalizeRequest(mixed $request): mixed
    {
        if (!is_array($request) || !array_key_exists('metadata', $request)) return $request;
        $metadata = $request['metadata'];
        if (!is_array($metadata) && !$metadata instanceof \stdClass) {
            throw new \InvalidArgumentException('ActivitySmith: metadata must be an object');
        }
        foreach ($metadata as $value) {
            if (!is_string($value) && !is_bool($value) && !is_int($value) && !(is_float($value) && is_finite($value))) {
                throw new \InvalidArgumentException('ActivitySmith: metadata values must be strings, finite numbers, or booleans');
            }
        }
        $request['metadata'] = (object) $metadata;
        return $request;
    }
}
