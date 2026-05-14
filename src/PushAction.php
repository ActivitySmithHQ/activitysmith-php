<?php

declare(strict_types=1);

namespace ActivitySmith;

final class PushAction
{
    /**
     * @param array<string,mixed>|null $body
     * @return array<string,mixed>
     */
    public static function make(
        string $title,
        string $type,
        string $url,
        ?string $method = null,
        ?array $body = null
    ): array {
        return array_filter(
            [
                'title' => $title,
                'type' => $type,
                'url' => $url,
                'method' => $method,
                'body' => $body,
            ],
            static fn (mixed $value): bool => $value !== null
        );
    }
}
