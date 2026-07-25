<?php

declare(strict_types=1);

namespace ActivitySmith\Tests;

use ActivitySmith\LiveActivities;
use ActivitySmith\ActivitySmith;
use ActivitySmith\LiveActivityAction;
use ActivitySmith\LiveActivityAlertBadge;
use ActivitySmith\LiveActivityAlertIcon;
use ActivitySmith\LiveActivityContentState;
use ActivitySmith\LiveActivityMetric;
use ActivitySmith\Metrics;
use ActivitySmith\Notifications;
use ActivitySmith\PushAction;
use ActivitySmith\Generated\Api\LiveActivitiesApi;
use ActivitySmith\Generated\Api\AppIconBadgesApi;
use ActivitySmith\Generated\Api\MetricsApi;
use ActivitySmith\Generated\Api\PushNotificationsApi;
use ActivitySmith\Generated\Model\LiveActivityAction as GeneratedLiveActivityAction;
use ActivitySmith\Generated\Model\LiveActivityActionType;
use ActivitySmith\Generated\Model\PushNotificationAction as GeneratedPushNotificationAction;
use ActivitySmith\Generated\Model\PushNotificationActionType;
use ActivitySmith\Generated\Model\PushNotificationRequest as GeneratedPushNotificationRequest;
use PHPUnit\Framework\TestCase;

final class ResourcesTest extends TestCase
{
    public function testBadgeCountClearsAndTargetsChannels(): void
    {
        $client = new ActivitySmith('test');
        $captured = [];
        $api = $this->getMockBuilder(AppIconBadgesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateAppIconBadgeCount'])
            ->getMock();
        $api->method('updateAppIconBadgeCount')
            ->willReturnCallback(static function (mixed $request) use (&$captured): mixed {
                $captured[] = $request;
                return $request;
            });

        $property = new \ReflectionProperty(ActivitySmith::class, 'appIconBadges');
        $property->setValue($client, $api);

        self::assertSame(['badge' => 0], $client->badgeCount(0));
        self::assertSame(
            [
                'badge' => 3,
                'target' => ['channels' => ['sales', 'customer-success']],
            ],
            $client->badgeCount(3, 'sales,customer-success')
        );
        self::assertSame(
            [
                ['badge' => 0],
                [
                    'badge' => 3,
                    'target' => ['channels' => ['sales', 'customer-success']],
                ],
            ],
            $captured
        );
    }

    public function testNotificationsShortAndLegacyMethods(): void
    {
        $payload = ['title' => 'Build Failed'];
        $response = (object) ['success' => true];
        $captured = [];

        $api = $this->getMockBuilder(PushNotificationsApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendPushNotification'])
            ->getMock();

        $api->expects($this->exactly(2))
            ->method('sendPushNotification')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured[] = $args;
                return $response;
            });

        $resource = new Notifications($api);

        $this->assertSame($response, $resource->send($payload));
        $this->assertSame($response, $resource->sendPushNotification($payload));
        $this->assertSame(
            [
                [$payload, PushNotificationsApi::contentTypes['sendPushNotification'][0]],
                [$payload, PushNotificationsApi::contentTypes['sendPushNotification'][0]],
            ],
            $captured
        );
    }

    public function testNotificationsNamedFields(): void
    {
        $captured = [];
        $response = (object) ['success' => true];

        $api = $this->getMockBuilder(PushNotificationsApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendPushNotification'])
            ->getMock();

        $api->expects($this->once())
            ->method('sendPushNotification')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured[] = $args;
                return $response;
            });

        $resource = new Notifications($api);
        $this->assertSame(
            $response,
            $resource->send(
                title: 'New subscription 💸',
                message: 'Customer upgraded to Pro plan',
                channels: 'sales,customer-success',
                tags: ['user:382', 'billing']
            )
        );

        $this->assertSame(
            [
                [
                    [
                        'title' => 'New subscription 💸',
                        'message' => 'Customer upgraded to Pro plan',
                        'tags' => ['user:382', 'billing'],
                        'target' => ['channels' => ['sales', 'customer-success']],
                    ],
                    PushNotificationsApi::contentTypes['sendPushNotification'][0],
                ],
            ],
            $captured
        );
    }

    public function testPushActionHelper(): void
    {
        $captured = [];
        $response = (object) ['success' => true];

        $api = $this->getMockBuilder(PushNotificationsApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendPushNotification'])
            ->getMock();

        $api->expects($this->once())
            ->method('sendPushNotification')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured[] = $args;
                return $response;
            });

        $resource = new Notifications($api);
        $this->assertSame(
            $response,
            $resource->send(
                title: 'New subscription 💸',
                actions: [
                    PushAction::make(
                        title: 'Open CRM Profile',
                        type: 'open_url',
                        url: 'shortcuts://run-shortcut?name=Open%20CRM'
                    ),
                ],
            )
        );

        $this->assertSame(
            [
                [
                    [
                        'title' => 'New subscription 💸',
                        'actions' => [
                            [
                                'title' => 'Open CRM Profile',
                                'type' => 'open_url',
                                'url' => 'shortcuts://run-shortcut?name=Open%20CRM',
                            ],
                        ],
                    ],
                    PushNotificationsApi::contentTypes['sendPushNotification'][0],
                ],
            ],
            $captured
        );
    }

    public function testGeneratedPushNotificationOpenUrlAllowsShortcuts(): void
    {
        $action = new GeneratedPushNotificationAction([
            'title' => 'Chat',
            'type' => PushNotificationActionType::OPEN_URL,
            'url' => 'shortcuts://run-shortcut?name=JARVIS',
        ]);

        $this->assertTrue($action->valid());
    }

    public function testGeneratedPushNotificationRedirectionAllowsShortcuts(): void
    {
        $request = new GeneratedPushNotificationRequest([
            'title' => 'Task finished',
            'redirection' => 'shortcuts://run-shortcut?name=Jarvis',
        ]);

        $this->assertTrue($request->valid());
    }

    public function testGeneratedLiveActivityOpenUrlAllowsShortcuts(): void
    {
        $action = new GeneratedLiveActivityAction([
            'title' => 'Chat',
            'type' => LiveActivityActionType::OPEN_URL,
            'url' => 'shortcuts://run-shortcut?name=JARVIS',
        ]);

        $this->assertTrue($action->valid());
    }

    public function testNotificationsMapsChannelsToTarget(): void
    {
        $captured = [];
        $response = (object) ['success' => true];

        $api = $this->getMockBuilder(PushNotificationsApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendPushNotification'])
            ->getMock();

        $api->expects($this->exactly(2))
            ->method('sendPushNotification')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured[] = $args;
                return $response;
            });

        $resource = new Notifications($api);
        $resource->send(['title' => 'Build Failed', 'channels' => ['devs', 'ops']]);
        $resource->sendPushNotification(['title' => 'Build Failed', 'channels' => 'devs,ops']);

        $this->assertSame(
            [
                [['title' => 'Build Failed', 'target' => ['channels' => ['devs', 'ops']]], PushNotificationsApi::contentTypes['sendPushNotification'][0]],
                [['title' => 'Build Failed', 'target' => ['channels' => ['devs', 'ops']]], PushNotificationsApi::contentTypes['sendPushNotification'][0]],
            ],
            $captured
        );
    }

    public function testNotificationsPreserveMediaAndRedirection(): void
    {
        $captured = [];
        $response = (object) ['success' => true];

        $api = $this->getMockBuilder(PushNotificationsApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendPushNotification'])
            ->getMock();

        $api->expects($this->exactly(2))
            ->method('sendPushNotification')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured[] = $args;
                return $response;
            });

        $resource = new Notifications($api);
        $payload = [
            'title' => 'Voice Over Generated',
            'media' => 'https://cdn.activitysmith.com/voice_over.mp3',
            'redirection' => 'https://studio.acme.com/voice-overs/482/review',
        ];

        $this->assertSame($response, $resource->send($payload));
        $this->assertSame($response, $resource->send(
            title: 'Run Shortcut',
            redirection: 'shortcuts://run-shortcut?name=Jarvis'
        ));
        $this->assertSame(
            [
                [$payload, PushNotificationsApi::contentTypes['sendPushNotification'][0]],
                [
                    [
                        'title' => 'Run Shortcut',
                        'redirection' => 'shortcuts://run-shortcut?name=Jarvis',
                    ],
                    PushNotificationsApi::contentTypes['sendPushNotification'][0],
                ],
            ],
            $captured
        );
    }

    public function testNotificationsRejectMediaAndActionsCombination(): void
    {
        $api = $this->getMockBuilder(PushNotificationsApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendPushNotification'])
            ->getMock();

        $api->expects($this->never())->method('sendPushNotification');

        $resource = new Notifications($api);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ActivitySmith: media cannot be combined with actions');

        $resource->send([
            'title' => 'Voice Over Generated',
            'media' => 'https://cdn.activitysmith.com/voice_over.mp3',
            'actions' => [
                [
                    'title' => 'Open',
                    'type' => 'open_url',
                    'url' => 'https://example.com',
                ],
            ],
        ]);
    }

    public function testLiveActivitiesShortAndLegacyMethods(): void
    {
        $startPayload = ['content_state' => ['title' => 'Deploy', 'number_of_steps' => 4, 'current_step' => 1, 'type' => 'segmented_progress']];
        $updatePayload = ['activity_id' => 'act-1', 'content_state' => ['title' => 'Deploy', 'current_step' => 2]];
        $endPayload = ['activity_id' => 'act-1', 'content_state' => ['title' => 'Deploy', 'current_step' => 4]];
        $response = (object) ['success' => true];
        $captured = [
            'start' => [],
            'update' => [],
            'end' => [],
        ];

        $api = $this->getMockBuilder(LiveActivitiesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['startLiveActivity', 'updateLiveActivity', 'endLiveActivity'])
            ->getMock();

        $api->expects($this->exactly(2))
            ->method('startLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['start'][] = $args;
                return $response;
            });

        $api->expects($this->exactly(2))
            ->method('updateLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['update'][] = $args;
                return $response;
            });

        $api->expects($this->exactly(2))
            ->method('endLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['end'][] = $args;
                return $response;
            });

        $resource = new LiveActivities($api);

        $this->assertSame($response, $resource->start($startPayload));
        $this->assertSame($response, $resource->startLiveActivity($startPayload));
        $this->assertSame($response, $resource->update($updatePayload));
        $this->assertSame($response, $resource->updateLiveActivity($updatePayload));
        $this->assertSame($response, $resource->end($endPayload));
        $this->assertSame($response, $resource->endLiveActivity($endPayload));
        $this->assertSame(
            [
                [$startPayload, LiveActivitiesApi::contentTypes['startLiveActivity'][0]],
                [$startPayload, LiveActivitiesApi::contentTypes['startLiveActivity'][0]],
            ],
            $captured['start']
        );
        $this->assertSame(
            [
                [$updatePayload, LiveActivitiesApi::contentTypes['updateLiveActivity'][0]],
                [$updatePayload, LiveActivitiesApi::contentTypes['updateLiveActivity'][0]],
            ],
            $captured['update']
        );
        $this->assertSame(
            [
                [$endPayload, LiveActivitiesApi::contentTypes['endLiveActivity'][0]],
                [$endPayload, LiveActivitiesApi::contentTypes['endLiveActivity'][0]],
            ],
            $captured['end']
        );
    }

    public function testLiveActivitiesStartMapsChannelsToTarget(): void
    {
        $captured = [];
        $response = (object) ['success' => true];

        $api = $this->getMockBuilder(LiveActivitiesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['startLiveActivity'])
            ->getMock();

        $api->expects($this->exactly(2))
            ->method('startLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured[] = $args;
                return $response;
            });

        $resource = new LiveActivities($api);

        $payload = [
            'content_state' => [
                'title' => 'Deploy',
                'number_of_steps' => 4,
                'current_step' => 1,
                'type' => 'segmented_progress',
            ],
            'channels' => ['devs', 'ops'],
        ];
        $resource->start($payload);
        $resource->startLiveActivity($payload);

        $expected = [
            'content_state' => [
                'title' => 'Deploy',
                'number_of_steps' => 4,
                'current_step' => 1,
                'type' => 'segmented_progress',
            ],
            'target' => ['channels' => ['devs', 'ops']],
        ];

        $this->assertSame(
            [
                [$expected, LiveActivitiesApi::contentTypes['startLiveActivity'][0]],
                [$expected, LiveActivitiesApi::contentTypes['startLiveActivity'][0]],
            ],
            $captured
        );
    }

    public function testLiveActivitiesPassActionPayloadsThrough(): void
    {
        $response = (object) ['success' => true];
        $captured = [
            'start' => [],
            'update' => [],
            'end' => [],
        ];

        $api = $this->getMockBuilder(LiveActivitiesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['startLiveActivity', 'updateLiveActivity', 'endLiveActivity'])
            ->getMock();

        $api->expects($this->once())
            ->method('startLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['start'][] = $args;
                return $response;
            });

        $api->expects($this->once())
            ->method('updateLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['update'][] = $args;
                return $response;
            });

        $api->expects($this->once())
            ->method('endLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['end'][] = $args;
                return $response;
            });

        $resource = new LiveActivities($api);

        $startPayload = [
            'content_state' => [
                'title' => 'Deploying payments-api',
                'subtitle' => 'Running database migrations',
                'number_of_steps' => 5,
                'current_step' => 3,
                'type' => 'segmented_progress',
            ],
            'action' => [
                'title' => 'Open Workflow',
                'type' => 'open_url',
                'url' => 'shortcuts://run-shortcut?name=Deploy%20Status',
            ],
        ];

        $updatePayload = [
            'activity_id' => 'act-1',
            'content_state' => [
                'title' => 'Reindexing product search',
                'subtitle' => 'Shard 7 of 12',
                'number_of_steps' => 12,
                'current_step' => 7,
            ],
            'action' => [
                'title' => 'Pause Reindex',
                'type' => 'webhook',
                'url' => 'https://ops.example.com/hooks/search/reindex/pause',
                'method' => 'POST',
                'body' => [
                    'job_id' => 'reindex-2026-03-19',
                ],
            ],
        ];

        $endPayload = [
            'activity_id' => 'act-1',
            'content_state' => [
                'title' => 'Deploying payments-api',
                'subtitle' => 'Production rollout complete',
                'number_of_steps' => 5,
                'current_step' => 5,
            ],
            'action' => [
                'title' => 'Open Workflow',
                'type' => 'open_url',
                'url' => 'shortcuts://run-shortcut?name=Deploy%20Status',
            ],
        ];

        $resource->start($startPayload);
        $resource->update($updatePayload);
        $resource->end($endPayload);

        $this->assertSame(
            [[$startPayload, LiveActivitiesApi::contentTypes['startLiveActivity'][0]]],
            $captured['start']
        );
        $this->assertSame(
            [[$updatePayload, LiveActivitiesApi::contentTypes['updateLiveActivity'][0]]],
            $captured['update']
        );
        $this->assertSame(
            [[$endPayload, LiveActivitiesApi::contentTypes['endLiveActivity'][0]]],
            $captured['end']
        );
    }

    public function testLiveActivitiesSupportProgressPayloads(): void
    {
        $captured = [];
        $response = (object) ['success' => true];

        $api = $this->getMockBuilder(LiveActivitiesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['startLiveActivity'])
            ->getMock();

        $api->expects($this->once())
            ->method('startLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured[] = $args;
                return $response;
            });

        $resource = new LiveActivities($api);
        $payload = [
            'content_state' => [
                'title' => 'Render export',
                'subtitle' => 'encoding frames',
                'type' => 'progress',
                'percentage' => 67,
                'color' => 'purple',
            ],
        ];

        $this->assertSame($response, $resource->start($payload));
        $this->assertSame(
            [
                [$payload, LiveActivitiesApi::contentTypes['startLiveActivity'][0]],
            ],
            $captured
        );
    }

    public function testLiveActivitiesSupportTimerPayloads(): void
    {
        $captured = [
            'start' => [],
            'update' => [],
        ];
        $response = (object) ['success' => true];

        $api = $this->getMockBuilder(LiveActivitiesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['startLiveActivity', 'updateLiveActivity'])
            ->getMock();

        $api->expects($this->once())
            ->method('startLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['start'][] = $args;
                return $response;
            });

        $api->expects($this->once())
            ->method('updateLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['update'][] = $args;
                return $response;
            });

        $resource = new LiveActivities($api);
        $state = LiveActivityContentState::make(
            title: 'Benchmark Run',
            subtitle: 'sampling performance',
            type: LiveActivities::TYPE_TIMER,
            durationSeconds: 300,
            countsDown: true,
            color: 'cyan'
        );

        $this->assertSame($response, $resource->start(contentState: $state));
        $this->assertSame(
            $response,
            $resource->update(
                activityId: 'act-1',
                title: 'Benchmark Run',
                type: LiveActivities::TYPE_TIMER,
                subtitle: 'complete',
                color: 'cyan'
            )
        );

        $this->assertSame(
            [
                [
                    [
                        'content_state' => [
                            'title' => 'Benchmark Run',
                            'subtitle' => 'sampling performance',
                            'type' => LiveActivities::TYPE_TIMER,
                            'duration_seconds' => 300,
                            'counts_down' => true,
                            'color' => 'cyan',
                        ],
                    ],
                    LiveActivitiesApi::contentTypes['startLiveActivity'][0],
                ],
            ],
            $captured['start']
        );
        $this->assertSame(
            [
                [
                    [
                        'activity_id' => 'act-1',
                        'content_state' => [
                            'title' => 'Benchmark Run',
                            'subtitle' => 'complete',
                            'type' => LiveActivities::TYPE_TIMER,
                            'color' => 'cyan',
                        ],
                    ],
                    LiveActivitiesApi::contentTypes['updateLiveActivity'][0],
                ],
            ],
            $captured['update']
        );
    }

    public function testLiveActivitiesSupportStatsPayloads(): void
    {
        $captured = [];
        $response = (object) ['success' => true];

        $api = $this->getMockBuilder(LiveActivitiesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['startLiveActivity'])
            ->getMock();

        $api->expects($this->once())
            ->method('startLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured[] = $args;
                return $response;
            });

        $resource = new LiveActivities($api);
        $payload = [
            'content_state' => [
                'title' => 'Sales',
                'subtitle' => 'last hour',
                'type' => LiveActivities::TYPE_STATS,
                'metrics' => [
                    ['label' => 'Revenue', 'value' => '$2430', 'color' => 'blue'],
                    ['label' => 'Orders', 'value' => '37', 'color' => 'green'],
                    ['label' => 'Conversion', 'value' => '4.8%', 'color' => 'magenta'],
                ],
            ],
        ];

        $this->assertSame($response, $resource->start($payload));
        $this->assertSame(
            [
                [$payload, LiveActivitiesApi::contentTypes['startLiveActivity'][0]],
            ],
            $captured
        );
    }

    public function testLiveActivitiesSupportAlertHelpers(): void
    {
        $captured = [];
        $response = (object) ['success' => true];

        $api = $this->getMockBuilder(LiveActivitiesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['reconcileLiveActivityStream'])
            ->getMock();

        $api->expects($this->once())
            ->method('reconcileLiveActivityStream')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured[] = $args;
                return $response;
            });

        $resource = new LiveActivities($api);
        $state = LiveActivityContentState::make(
            title: 'Reactivation',
            type: LiveActivities::TYPE_ALERT,
            message: 'Lumen came back after 2 weeks',
            icon: LiveActivityAlertIcon::make(symbol: 'cloud.sun', color: 'yellow'),
            badge: LiveActivityAlertBadge::make(title: 'Customer', color: 'magenta'),
            color: 'red'
        );

        $this->assertSame(
            $response,
            $resource->stream('customer-ops', contentState: $state)
        );
        $this->assertSame(
            [
                [
                    'customer-ops',
                    [
                        'content_state' => [
                            'title' => 'Reactivation',
                            'type' => LiveActivities::TYPE_ALERT,
                            'message' => 'Lumen came back after 2 weeks',
                            'icon' => ['symbol' => 'cloud.sun', 'color' => 'yellow'],
                            'badge' => ['title' => 'Customer', 'color' => 'magenta'],
                            'color' => 'red',
                        ],
                    ],
                    LiveActivitiesApi::contentTypes['reconcileLiveActivityStream'][0],
                ],
            ],
            $captured
        );
    }

    public function testLiveActivitiesSupportIconAndBadgeOnNonAlertTypes(): void
    {
        $response = (object) ['success' => true];
        $captured = [];

        $api = $this->getMockBuilder(LiveActivitiesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['reconcileLiveActivityStream'])
            ->getMock();

        $api->expects($this->exactly(2))
            ->method('reconcileLiveActivityStream')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured[] = $args;
                return $response;
            });

        $resource = new LiveActivities($api);

        $resource->stream(
            'prod-web-1',
            contentState: LiveActivityContentState::make(
                title: 'Server Health',
                subtitle: 'prod-web-1',
                type: LiveActivities::TYPE_METRICS,
                icon: LiveActivityAlertIcon::make(symbol: 'server.rack', color: 'blue'),
                metrics: [LiveActivityMetric::make(label: 'CPU', value: 18, unit: '%')]
            )
        );
        $resource->stream(
            'nightly-database-backup',
            contentState: LiveActivityContentState::make(
                title: 'Nightly Database Backup',
                subtitle: 'verify restore',
                type: LiveActivities::TYPE_PROGRESS,
                badge: LiveActivityAlertBadge::make(title: 'S3', color: 'cyan'),
                percentage: 62
            )
        );

        $this->assertSame(
            [
                [
                    'prod-web-1',
                    [
                        'content_state' => [
                            'title' => 'Server Health',
                            'subtitle' => 'prod-web-1',
                            'type' => LiveActivities::TYPE_METRICS,
                            'icon' => ['symbol' => 'server.rack', 'color' => 'blue'],
                            'metrics' => [['label' => 'CPU', 'value' => 18, 'unit' => '%']],
                        ],
                    ],
                    LiveActivitiesApi::contentTypes['reconcileLiveActivityStream'][0],
                ],
                [
                    'nightly-database-backup',
                    [
                        'content_state' => [
                            'title' => 'Nightly Database Backup',
                            'subtitle' => 'verify restore',
                            'type' => LiveActivities::TYPE_PROGRESS,
                            'badge' => ['title' => 'S3', 'color' => 'cyan'],
                            'percentage' => 62,
                        ],
                    ],
                    LiveActivitiesApi::contentTypes['reconcileLiveActivityStream'][0],
                ],
            ],
            $captured
        );
    }

    public function testLiveActivitiesBuildRequestsFromNamedFields(): void
    {
        $response = (object) ['success' => true];
        $captured = [
            'start' => [],
            'update' => [],
            'end' => [],
        ];

        $api = $this->getMockBuilder(LiveActivitiesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['startLiveActivity', 'updateLiveActivity', 'endLiveActivity'])
            ->getMock();

        $api->expects($this->once())
            ->method('startLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['start'][] = $args;
                return $response;
            });

        $api->expects($this->once())
            ->method('updateLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['update'][] = $args;
                return $response;
            });

        $api->expects($this->once())
            ->method('endLiveActivity')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['end'][] = $args;
                return $response;
            });

        $resource = new LiveActivities($api);
        $metrics = [
            LiveActivityMetric::make(label: 'CPU', value: 9, unit: '%'),
            LiveActivityMetric::make(label: 'MEM', value: 45, unit: '%'),
        ];
        $action = LiveActivityAction::make(
            title: 'Open Dashboard',
            type: 'open_url',
            url: 'shortcuts://run-shortcut?name=Open%20Dashboard'
        );
        $secondaryAction = LiveActivityAction::make(
            title: 'Deny',
            type: 'webhook',
            url: 'https://ops.example.com/hooks/server-health/deny'
        );
        $state = LiveActivityContentState::make(
            title: 'Server Health',
            subtitle: 'prod-web-1',
            type: LiveActivities::TYPE_METRICS,
            metrics: $metrics
        );

        $this->assertSame(
            $response,
            $resource->start(
                contentState: $state,
                action: $action,
                secondaryAction: $secondaryAction,
                channels: ['ops'],
                tags: ['user:382', 'environment:production']
            )
        );
        $this->assertSame(
            $response,
            $resource->update(
                activityId: 'act-1',
                title: 'Server Health',
                subtitle: 'prod-web-1',
                type: LiveActivities::TYPE_METRICS,
                metrics: $metrics,
                secondaryAction: $secondaryAction
            )
        );
        $this->assertSame(
            $response,
            $resource->end(
                activityId: 'act-1',
                title: 'Server Health',
                subtitle: 'prod-web-1',
                type: LiveActivities::TYPE_METRICS,
                metrics: $metrics,
                autoDismissMinutes: 2,
                secondaryAction: $secondaryAction
            )
        );

        $this->assertSame(
            [
                [
                    [
                        'content_state' => [
                            'title' => 'Server Health',
                            'subtitle' => 'prod-web-1',
                            'type' => LiveActivities::TYPE_METRICS,
                            'metrics' => $metrics,
                        ],
                        'action' => $action,
                        'secondary_action' => $secondaryAction,
                        'tags' => ['user:382', 'environment:production'],
                        'target' => ['channels' => ['ops']],
                    ],
                    LiveActivitiesApi::contentTypes['startLiveActivity'][0],
                ],
            ],
            $captured['start']
        );
        $this->assertSame(
            [
                [
                    [
                        'activity_id' => 'act-1',
                        'content_state' => [
                            'title' => 'Server Health',
                            'subtitle' => 'prod-web-1',
                            'type' => LiveActivities::TYPE_METRICS,
                            'metrics' => $metrics,
                        ],
                        'secondary_action' => $secondaryAction,
                    ],
                    LiveActivitiesApi::contentTypes['updateLiveActivity'][0],
                ],
            ],
            $captured['update']
        );
        $this->assertSame(
            [
                [
                    [
                        'activity_id' => 'act-1',
                        'content_state' => [
                            'title' => 'Server Health',
                            'subtitle' => 'prod-web-1',
                            'type' => LiveActivities::TYPE_METRICS,
                            'metrics' => $metrics,
                            'auto_dismiss_minutes' => 2,
                        ],
                        'secondary_action' => $secondaryAction,
                    ],
                    LiveActivitiesApi::contentTypes['endLiveActivity'][0],
                ],
            ],
            $captured['end']
        );
    }

    public function testLiveActivitiesStreamShortAndLegacyMethods(): void
    {
        $response = (object) ['success' => true];
        $captured = [
            'stream' => [],
            'endStream' => [],
        ];

        $api = $this->getMockBuilder(LiveActivitiesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['reconcileLiveActivityStream', 'endLiveActivityStream'])
            ->getMock();

        $api->expects($this->exactly(2))
            ->method('reconcileLiveActivityStream')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['stream'][] = $args;
                return $response;
            });

        $api->expects($this->exactly(2))
            ->method('endLiveActivityStream')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured['endStream'][] = $args;
                return $response;
            });

        $resource = new LiveActivities($api);
        $streamPayload = [
            'content_state' => [
                'title' => 'Server Health',
                'subtitle' => 'prod-web-1',
                'type' => 'metrics',
                'metrics' => [
                    ['label' => 'CPU', 'value' => 9, 'unit' => '%'],
                    ['label' => 'MEM', 'value' => 45, 'unit' => '%'],
                ],
            ],
            'channels' => ['ops'],
        ];
        $endPayload = [
            'content_state' => [
                'title' => 'Server Health',
                'subtitle' => 'prod-web-1',
                'type' => 'metrics',
                'metrics' => [
                    ['label' => 'CPU', 'value' => 7, 'unit' => '%'],
                    ['label' => 'MEM', 'value' => 38, 'unit' => '%'],
                ],
            ],
        ];

        $this->assertSame(
            $response,
            $resource->stream(
                'prod-web-1',
                $streamPayload,
                tags: ['user:382', 'environment:production']
            )
        );
        $this->assertSame($response, $resource->reconcileLiveActivityStream('prod-web-1', $streamPayload));
        $this->assertSame($response, $resource->endStream('prod-web-1', $endPayload));
        $this->assertSame($response, $resource->endLiveActivityStream('prod-web-1', $endPayload));

        $expectedStreamPayload = [
            'content_state' => $streamPayload['content_state'],
            'target' => ['channels' => ['ops']],
        ];
        $expectedTaggedStreamPayload = [
            'content_state' => $streamPayload['content_state'],
            'tags' => ['user:382', 'environment:production'],
            'target' => ['channels' => ['ops']],
        ];

        $this->assertSame(
            [
                ['prod-web-1', $expectedTaggedStreamPayload, LiveActivitiesApi::contentTypes['reconcileLiveActivityStream'][0]],
                ['prod-web-1', $expectedStreamPayload, LiveActivitiesApi::contentTypes['reconcileLiveActivityStream'][0]],
            ],
            $captured['stream']
        );
        $this->assertSame(
            [
                ['prod-web-1', $endPayload, LiveActivitiesApi::contentTypes['endLiveActivityStream'][0]],
                ['prod-web-1', $endPayload, LiveActivitiesApi::contentTypes['endLiveActivityStream'][0]],
            ],
            $captured['endStream']
        );
    }

    public function testResourcePassthroughMethods(): void
    {
        $payload = ['title' => 'Build Failed'];
        $response = ['ok'];

        $pushApi = $this->getMockBuilder(PushNotificationsApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sendPushNotificationWithHttpInfo'])
            ->getMock();

        $pushApi->expects($this->once())
            ->method('sendPushNotificationWithHttpInfo')
            ->with($payload)
            ->willReturn($response);

        $notifications = new Notifications($pushApi);
        $this->assertSame($response, $notifications->sendPushNotificationWithHttpInfo($payload));

        $liveApi = $this->getMockBuilder(LiveActivitiesApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['startLiveActivityWithHttpInfo'])
            ->getMock();

        $liveApi->expects($this->once())
            ->method('startLiveActivityWithHttpInfo')
            ->with(['content_state' => ['title' => 'Deploy']])
            ->willReturn($response);

        $liveActivities = new LiveActivities($liveApi);
        $this->assertSame(
            $response,
            $liveActivities->startLiveActivityWithHttpInfo(['content_state' => ['title' => 'Deploy']])
        );
    }

    public function testMetricsShortAndLegacyMethods(): void
    {
        $response = (object) ['success' => true];
        $captured = [];

        $api = $this->getMockBuilder(MetricsApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateMetricValue'])
            ->getMock();

        $api->expects($this->exactly(3))
            ->method('updateMetricValue')
            ->willReturnCallback(function (...$args) use (&$captured, $response) {
                $captured[] = $args;
                return $response;
            });

        $resource = new Metrics($api);
        $this->assertSame($response, $resource->update('deploy.success_rate', 99.9, '2026-05-03T12:30:00.000Z'));
        $this->assertSame($response, $resource->update('prod.status', ['value' => 'healthy']));
        $this->assertSame($response, $resource->updateMetricValue('deploy.success_rate', ['value' => 42]));

        $this->assertSame(
            [
                [
                    'deploy.success_rate',
                    [
                        'value' => 99.9,
                        'timestamp' => '2026-05-03T12:30:00.000Z',
                    ],
                    MetricsApi::contentTypes['updateMetricValue'][0],
                ],
                [
                    'prod.status',
                    ['value' => 'healthy'],
                    MetricsApi::contentTypes['updateMetricValue'][0],
                ],
                [
                    'deploy.success_rate',
                    ['value' => 42],
                    MetricsApi::contentTypes['updateMetricValue'][0],
                ],
            ],
            $captured
        );
    }
}
