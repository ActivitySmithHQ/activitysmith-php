<?php

declare(strict_types=1);

namespace ActivitySmith;

final class LiveActivityAlertIcon
{
    /**
     * @return array{symbol: string, color?: string}
     */
    public static function make(
        string $symbol,
        ?string $color = null
    ): array {
        return array_filter(
            [
                'symbol' => $symbol,
                'color' => $color,
            ],
            static fn (mixed $value): bool => $value !== null
        );
    }
}
