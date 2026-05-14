<?php

declare(strict_types=1);

namespace ActivitySmith;

final class LiveActivityMetric
{
    /**
     * @return array{label: string, value: mixed, unit?: string, color?: string}
     */
    public static function make(
        string $label,
        mixed $value,
        ?string $unit = null,
        ?string $color = null
    ): array {
        $metric = [
            'label' => $label,
            'value' => $value,
        ];

        if ($unit !== null) {
            $metric['unit'] = $unit;
        }

        if ($color !== null) {
            $metric['color'] = $color;
        }

        return $metric;
    }
}
