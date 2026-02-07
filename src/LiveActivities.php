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
        return $this->api->startLiveActivity($request);
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
        return $this->api->startLiveActivity($liveActivityStartRequest, $contentType);
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
}
