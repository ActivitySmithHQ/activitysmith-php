<?php

declare(strict_types=1);

namespace ActivitySmith;

use ActivitySmith\Generated\Api\LiveActivitiesApi;

final class LiveActivities
{
    public const TYPE_SEGMENTED_PROGRESS = 'segmented_progress';
    public const TYPE_PROGRESS = 'progress';
    public const TYPE_METRICS = 'metrics';
    public const TYPE_STATS = 'stats';

    public function __construct(private LiveActivitiesApi $api)
    {
    }

    public function start(
        mixed $request = null,
        mixed $contentState = null,
        mixed $title = null,
        mixed $subtitle = null,
        mixed $type = null,
        mixed $metrics = null,
        mixed $numberOfSteps = null,
        mixed $currentStep = null,
        mixed $percentage = null,
        mixed $value = null,
        mixed $upperLimit = null,
        mixed $color = null,
        mixed $stepColor = null,
        mixed $action = null,
        mixed $alert = null,
        mixed $target = null,
        mixed $channels = null
    ): mixed
    {
        $request = $this->buildRequest($request, $contentState, [
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
        ], [
            'action' => $action,
            'alert' => $alert,
            'target' => $target,
            'channels' => $channels,
        ]);

        return $this->api->startLiveActivity($this->normalizeTargetChannels($request));
    }

    public function update(
        mixed $request = null,
        mixed $activityId = null,
        mixed $contentState = null,
        mixed $title = null,
        mixed $subtitle = null,
        mixed $type = null,
        mixed $metrics = null,
        mixed $numberOfSteps = null,
        mixed $currentStep = null,
        mixed $percentage = null,
        mixed $value = null,
        mixed $upperLimit = null,
        mixed $color = null,
        mixed $stepColor = null,
        mixed $action = null
    ): mixed
    {
        $request = $this->buildRequest($request, $contentState, [
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
        ], [
            'activity_id' => $activityId,
            'action' => $action,
        ]);

        return $this->api->updateLiveActivity($request);
    }

    public function end(
        mixed $request = null,
        mixed $activityId = null,
        mixed $contentState = null,
        mixed $title = null,
        mixed $subtitle = null,
        mixed $type = null,
        mixed $metrics = null,
        mixed $numberOfSteps = null,
        mixed $currentStep = null,
        mixed $percentage = null,
        mixed $value = null,
        mixed $upperLimit = null,
        mixed $color = null,
        mixed $stepColor = null,
        mixed $autoDismissMinutes = null,
        mixed $action = null
    ): mixed
    {
        $request = $this->buildRequest($request, $contentState, [
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
            'auto_dismiss_minutes' => $autoDismissMinutes,
        ], [
            'activity_id' => $activityId,
            'action' => $action,
        ]);

        return $this->api->endLiveActivity($request);
    }

    public function stream(
        mixed $streamKey,
        mixed $request = null,
        mixed $contentState = null,
        mixed $title = null,
        mixed $subtitle = null,
        mixed $type = null,
        mixed $metrics = null,
        mixed $numberOfSteps = null,
        mixed $currentStep = null,
        mixed $percentage = null,
        mixed $value = null,
        mixed $upperLimit = null,
        mixed $color = null,
        mixed $stepColor = null,
        mixed $action = null,
        mixed $alert = null,
        mixed $target = null,
        mixed $channels = null
    ): mixed
    {
        $request = $this->buildRequest($request, $contentState, [
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
        ], [
            'action' => $action,
            'alert' => $alert,
            'target' => $target,
            'channels' => $channels,
        ]);

        return $this->api->reconcileLiveActivityStream(
            $streamKey,
            $this->normalizeTargetChannels($request)
        );
    }

    public function endStream(
        mixed $streamKey,
        mixed $request = null,
        mixed $contentState = null,
        mixed $title = null,
        mixed $subtitle = null,
        mixed $type = null,
        mixed $metrics = null,
        mixed $numberOfSteps = null,
        mixed $currentStep = null,
        mixed $percentage = null,
        mixed $value = null,
        mixed $upperLimit = null,
        mixed $color = null,
        mixed $stepColor = null,
        mixed $autoDismissMinutes = null,
        mixed $action = null,
        mixed $alert = null
    ): mixed
    {
        $request = $this->buildRequest($request, $contentState, [
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
            'auto_dismiss_minutes' => $autoDismissMinutes,
        ], [
            'action' => $action,
            'alert' => $alert,
        ]);

        return $this->api->endLiveActivityStream($streamKey, $request);
    }

    // Backward-compatible aliases.
    public function startLiveActivity(
        mixed $liveActivityStartRequest,
        string $contentType = LiveActivitiesApi::contentTypes['startLiveActivity'][0]
    ): mixed {
        return $this->api->startLiveActivity(
            $this->normalizeTargetChannels($liveActivityStartRequest),
            $contentType
        );
    }

    public function updateLiveActivity(
        mixed $liveActivityUpdateRequest,
        string $contentType = LiveActivitiesApi::contentTypes['updateLiveActivity'][0]
    ): mixed {
        return $this->api->updateLiveActivity($liveActivityUpdateRequest, $contentType);
    }

    public function endLiveActivity(
        mixed $liveActivityEndRequest,
        string $contentType = LiveActivitiesApi::contentTypes['endLiveActivity'][0]
    ): mixed {
        return $this->api->endLiveActivity($liveActivityEndRequest, $contentType);
    }

    public function reconcileLiveActivityStream(
        mixed $streamKey,
        mixed $liveActivityStreamRequest,
        string $contentType = LiveActivitiesApi::contentTypes['reconcileLiveActivityStream'][0]
    ): mixed {
        return $this->api->reconcileLiveActivityStream(
            $streamKey,
            $this->normalizeTargetChannels($liveActivityStreamRequest),
            $contentType
        );
    }

    public function endLiveActivityStream(
        mixed $streamKey,
        mixed $liveActivityStreamDeleteRequest = null,
        string $contentType = LiveActivitiesApi::contentTypes['endLiveActivityStream'][0]
    ): mixed {
        return $this->api->endLiveActivityStream(
            $streamKey,
            $liveActivityStreamDeleteRequest,
            $contentType
        );
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->api->{$name}(...$arguments);
    }

    private function normalizeTargetChannels(mixed $request): mixed
    {
        if (!is_array($request) || array_key_exists('target', $request) || !array_key_exists('channels', $request)) {
            return $request;
        }

        $channels = $request['channels'];
        unset($request['channels']);

        if (is_string($channels)) {
            $channels = array_values(
                array_filter(
                    array_map('trim', explode(',', $channels)),
                    static fn (string $channel): bool => $channel !== ''
                )
            );
        }

        if (is_array($channels) && $channels !== []) {
            $request['target'] = ['channels' => $channels];
        }

        return $request;
    }

    /**
     * @param array<string, mixed> $contentStateFields
     * @param array<string, mixed> $requestFields
     */
    private function buildRequest(mixed $request, mixed $contentState, array $contentStateFields, array $requestFields): mixed
    {
        $contentStateFields = array_filter(
            $contentStateFields,
            static fn (mixed $value): bool => $value !== null
        );
        $requestFields = array_filter(
            $requestFields,
            static fn (mixed $value): bool => $value !== null
        );

        if ($contentState === null && $contentStateFields === [] && $requestFields === []) {
            return $request;
        }

        if ($request === null) {
            $request = [];
        }

        if (!is_array($request)) {
            throw new \InvalidArgumentException(
                'ActivitySmith: named Live Activity fields can only be combined with an array request'
            );
        }

        if ($contentState !== null) {
            $existingContentState = $request['content_state'] ?? [];
            if (!is_array($existingContentState) || !is_array($contentState)) {
                throw new \InvalidArgumentException('ActivitySmith: content_state must be an array');
            }
            $request['content_state'] = array_merge($existingContentState, $contentState);
        }

        if ($contentStateFields !== []) {
            $existingContentState = $request['content_state'] ?? [];
            if (!is_array($existingContentState)) {
                throw new \InvalidArgumentException('ActivitySmith: content_state must be an array');
            }
            $request['content_state'] = array_merge($existingContentState, $contentStateFields);
        }

        if (array_key_exists('activity_id', $requestFields)) {
            $activityId = $requestFields['activity_id'];
            unset($requestFields['activity_id']);
            unset($request['activity_id']);
            $request = ['activity_id' => $activityId] + $request;
        }

        return array_merge($request, $requestFields);
    }
}
