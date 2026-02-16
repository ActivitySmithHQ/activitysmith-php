<?php

declare(strict_types=1);

namespace ActivitySmith\Tests;

use ActivitySmith\LiveActivities;
use ActivitySmith\Notifications;
use ActivitySmith\Generated\Api\LiveActivitiesApi;
use ActivitySmith\Generated\Api\PushNotificationsApi;
use PHPUnit\Framework\TestCase;

final class ResourcesTest extends TestCase
{
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
}
