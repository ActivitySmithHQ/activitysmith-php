<?php

declare(strict_types=1);

namespace ActivitySmith;

use ActivitySmith\Generated\Api\PushNotificationsApi;

final class Notifications
{
    public function __construct(private PushNotificationsApi $api)
    {
    }

    public function send(mixed $request): mixed
    {
        return $this->api->sendPushNotification($request);
    }

    // Backward-compatible alias.
    public function sendPushNotification(
        mixed $pushNotificationRequest,
        string $contentType = PushNotificationsApi::contentTypes['sendPushNotification'][0]
    ): mixed {
        return $this->api->sendPushNotification($pushNotificationRequest, $contentType);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->api->{$name}(...$arguments);
    }
}
