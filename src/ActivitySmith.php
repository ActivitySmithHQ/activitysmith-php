<?php

declare(strict_types=1);

namespace ActivitySmith;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use InvalidArgumentException;
use RuntimeException;

final class ActivitySmith
{
    private const CONFIGURATION_CLASS = 'ActivitySmith\\Generated\\Configuration';
    private const PUSH_API_CLASS = 'ActivitySmith\\Generated\\Api\\PushNotificationsApi';
    private const LIVE_API_CLASS = 'ActivitySmith\\Generated\\Api\\LiveActivitiesApi';
    private const METRICS_API_CLASS = 'ActivitySmith\\Generated\\Api\\MetricsApi';
    private const APP_ICON_BADGES_API_CLASS = 'ActivitySmith\\Generated\\Api\\AppIconBadgesApi';
    private const SDK_HEADER_NAME = 'X-ActivitySmith-SDK';

    /** @var Notifications */
    public $notifications;

    /** @var LiveActivities */
    public $liveActivities;

    /** @var Metrics */
    public $metrics;

    private $appIconBadges;

    public function __construct(string $apiKey)
    {
        if ($apiKey === '') {
            throw new InvalidArgumentException('ActivitySmith: apiKey is required');
        }

        $this->assertGeneratedClientIsPresent();

        $configurationClass = self::CONFIGURATION_CLASS;
        $pushApiClass = self::PUSH_API_CLASS;
        $liveApiClass = self::LIVE_API_CLASS;
        $metricsApiClass = self::METRICS_API_CLASS;
        $appIconBadgesApiClass = self::APP_ICON_BADGES_API_CLASS;

        $configuration = $configurationClass::getDefaultConfiguration();
        $configuration->setAccessToken($apiKey);
        $configuration->setUserAgent('activitysmith-php/' . Version::VERSION);

        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest(
            static fn ($request) => $request->withHeader(
                self::SDK_HEADER_NAME,
                'php-v' . Version::VERSION
            )
        ));
        $httpClient = new Client(['handler' => $stack]);

        $this->notifications = new Notifications(new $pushApiClass($httpClient, $configuration));
        $this->liveActivities = new LiveActivities(new $liveApiClass($httpClient, $configuration));
        $this->metrics = new Metrics(new $metricsApiClass($httpClient, $configuration));
        $this->appIconBadges = new $appIconBadgesApiClass($httpClient, $configuration);
    }

    /**
     * @param array<int,string>|string|null $channels
     */
    public function badgeCount(int $value, array|string|null $channels = null): mixed
    {
        $request = ['badge' => $value];
        $channels = $this->normalizeChannels($channels);
        if ($channels !== []) {
            $request['target'] = ['channels' => $channels];
        }

        return $this->appIconBadges->updateAppIconBadgeCount($request);
    }

    private function assertGeneratedClientIsPresent(): void
    {
        if (
            !class_exists(self::CONFIGURATION_CLASS)
            || !class_exists(self::PUSH_API_CLASS)
            || !class_exists(self::LIVE_API_CLASS)
            || !class_exists(self::METRICS_API_CLASS)
            || !class_exists(self::APP_ICON_BADGES_API_CLASS)
        ) {
            throw new RuntimeException(
                'Generated PHP client not found. Run SDK regeneration so /generated contains OpenAPI output.'
            );
        }
    }

    /**
     * @param array<int,string>|string|null $channels
     * @return array<int,string>
     */
    private function normalizeChannels(array|string|null $channels): array
    {
        if (is_string($channels)) {
            $channels = explode(',', $channels);
        }

        if (!is_array($channels)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map('trim', $channels),
                static fn (string $channel): bool => $channel !== ''
            )
        );
    }
}
