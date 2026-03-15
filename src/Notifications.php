<?php

declare(strict_types=1);

namespace ActivitySmith;

use ActivitySmith\Generated\Api\PushNotificationsApi;
use InvalidArgumentException;

final class Notifications
{
    public function __construct(private PushNotificationsApi $api)
    {
    }

    public function send(mixed $request): mixed
    {
        $normalized = $this->normalizeTargetChannels($request);
        $this->assertValidMediaActionsCombination($normalized);

        return $this->api->sendPushNotification($normalized);
    }

    // Backward-compatible alias.
    public function sendPushNotification(
        mixed $pushNotificationRequest,
        string $contentType = PushNotificationsApi::contentTypes['sendPushNotification'][0]
    ): mixed {
        $normalized = $this->normalizeTargetChannels($pushNotificationRequest);
        $this->assertValidMediaActionsCombination($normalized);

        return $this->api->sendPushNotification(
            $normalized,
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

    private function assertValidMediaActionsCombination(mixed $request): void
    {
        $media = $this->getRequestField($request, 'media');
        $actions = $this->getRequestField($request, 'actions');

        $hasMedia = is_string($media) ? trim($media) !== '' : $media !== null;
        $hasActions = is_countable($actions) ? count($actions) > 0 : $actions !== null;

        if ($hasMedia && $hasActions) {
            throw new InvalidArgumentException('ActivitySmith: media cannot be combined with actions');
        }
    }

    private function getRequestField(mixed $request, string $field): mixed
    {
        if (is_array($request)) {
            return $request[$field] ?? null;
        }

        if (!is_object($request)) {
            return null;
        }

        $getter = 'get' . ucfirst($field);
        if (method_exists($request, $getter)) {
            return $request->{$getter}();
        }

        return property_exists($request, $field) ? $request->{$field} : null;
    }
}
