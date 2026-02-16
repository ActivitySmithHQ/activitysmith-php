<?php

declare(strict_types=1);

namespace ActivitySmith;

use ActivitySmith\Generated\Api\LiveActivitiesApi;

final class LiveActivities
{
    public function __construct(private LiveActivitiesApi $api)
    {
    }

    public function start(mixed $request): mixed
    {
        return $this->api->startLiveActivity($this->normalizeTargetChannels($request));
    }

    public function update(mixed $request): mixed
    {
        return $this->api->updateLiveActivity($request);
    }

    public function end(mixed $request): mixed
    {
        return $this->api->endLiveActivity($request);
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
}
