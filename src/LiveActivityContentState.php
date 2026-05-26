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
        ?string $message = null,
        ?array $icon = null,
        ?array $badge = null,
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
        $state = [
            'title' => $title,
            'subtitle' => $subtitle,
            'type' => $type,
            'message' => $message,
            'icon' => $icon,
            'badge' => $badge,
            'metrics' => $metrics,
            'number_of_steps' => $numberOfSteps,
            'current_step' => $currentStep,
            'percentage' => $percentage,
            'value' => $value,
            'upper_limit' => $upperLimit,
            'color' => $type === LiveActivities::TYPE_ALERT ? null : $color,
            'step_color' => $stepColor,
            'auto_dismiss_seconds' => $autoDismissSeconds,
            'auto_dismiss_minutes' => $autoDismissMinutes,
        ];

        return array_filter(
            $state,
            static fn (mixed $value): bool => $value !== null
        );
    }
}
