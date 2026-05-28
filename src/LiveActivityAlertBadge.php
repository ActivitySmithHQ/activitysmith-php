<?php

declare(strict_types=1);

namespace ActivitySmith;

final class LiveActivityAlertBadge
{
    /**
     * @return array{title: string, color?: string}
     */
    public static function make(
        string $title,
        ?string $color = null
    ): array {
        return array_filter(
            [
                'title' => $title,
                'color' => $color,
            ],
            static fn (mixed $value): bool => $value !== null
        );
    }
}
