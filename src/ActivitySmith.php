<?php

declare(strict_types=1);

namespace ActivitySmith;

use GuzzleHttp\Client;
use InvalidArgumentException;
use RuntimeException;

final class ActivitySmith
{
    private const CONFIGURATION_CLASS = 'ActivitySmith\\Generated\\Configuration';
    private const PUSH_API_CLASS = 'ActivitySmith\\Generated\\Api\\PushNotificationsApi';
    private const LIVE_API_CLASS = 'ActivitySmith\\Generated\\Api\\LiveActivitiesApi';

    /** @var Notifications */
    public $notifications;

    /** @var LiveActivities */
    public $liveActivities;

    public function __construct(string $apiKey, ?string $baseUrl = null)
    {
        if ($apiKey === '') {
            throw new InvalidArgumentException('ActivitySmith: apiKey is required');
        }

        $this->assertGeneratedClientIsPresent();

        $configurationClass = self::CONFIGURATION_CLASS;
        $pushApiClass = self::PUSH_API_CLASS;
        $liveApiClass = self::LIVE_API_CLASS;

        $configuration = $configurationClass::getDefaultConfiguration()->setAccessToken($apiKey);

        if ($baseUrl !== null && $baseUrl !== '') {
            $configuration->setHost(rtrim($baseUrl, '/'));
        }

        $httpClient = new Client();

        $this->notifications = new Notifications(new $pushApiClass($httpClient, $configuration));
        $this->liveActivities = new LiveActivities(new $liveApiClass($httpClient, $configuration));
    }

    private function assertGeneratedClientIsPresent(): void
    {
        if (!class_exists(self::CONFIGURATION_CLASS) || !class_exists(self::PUSH_API_CLASS) || !class_exists(self::LIVE_API_CLASS)) {
            throw new RuntimeException(
                'Generated PHP client not found. Run SDK regeneration so /generated contains OpenAPI output.'
            );
        }
    }
}
