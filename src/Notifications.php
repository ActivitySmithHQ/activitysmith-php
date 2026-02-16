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
        return $this->api->sendPushNotification($this->normalizeTargetChannels($request));
    }

    // Backward-compatible alias.
    public function sendPushNotification(
        mixed $pushNotificationRequest,
        string $contentType = PushNotificationsApi::contentTypes['sendPushNotification'][0]
    ): mixed {
        return $this->api->sendPushNotification(
            $this->normalizeTargetChannels($pushNotificationRequest),
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
}
