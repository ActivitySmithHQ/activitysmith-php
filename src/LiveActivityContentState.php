<?php

declare(strict_types=1);

namespace ActivitySmith;

final class LiveActivityContentState
{
    /**
     * @param array<int, array<string, mixed>>|null $metrics
     * @return array<string, mixed>
     */
    public static function make(
        string $title,
        ?string $type = null,
        ?string $subtitle = null,
        ?array $metrics = null,
        ?int $numberOfSteps = null,
        ?int $currentStep = null,
        int|float|null $percentage = null,
        int|float|null $value = null,
        int|float|null $upperLimit = null,
        ?string $color = null,
        ?string $stepColor = null,
        ?int $autoDismissSeconds = null,
        ?int $autoDismissMinutes = null
    ): array {
        return array_filter(
            [
                'title' => $title,
                'subtitle' => $subtitle,
                'type' => $type,
                'metrics' => $metrics,
                'number_of_steps' => $numberOfSteps,
                'current_step' => $currentStep,
                'percentage' => $percentage,
                'value' => $value,
                'upper_limit' => $upperLimit,
                'color' => $color,
                'step_color' => $stepColor,
                'auto_dismiss_seconds' => $autoDismissSeconds,
                'auto_dismiss_minutes' => $autoDismissMinutes,
            ],
            static fn (mixed $value): bool => $value !== null
        );
    }
}
