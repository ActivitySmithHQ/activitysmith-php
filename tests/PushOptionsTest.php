<?php

declare(strict_types=1);

namespace ActivitySmith\Tests;

use ActivitySmith\Notifications;
use ActivitySmith\PushInterruptionLevel;
use ActivitySmith\Generated\Api\PushNotificationsApi;
use ActivitySmith\Generated\Model\PushNotificationRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class PushOptionsTest extends TestCase
{
    public function testSerializationAcrossNamedArrayAndModelInputs(): void
    {
        foreach ([null, ...PushInterruptionLevel::VALUES] as $level) {
            foreach (['send', 'sendPushNotification'] as $method) {
                foreach (['named', 'array', 'model'] as $form) {
                    foreach ([false, true] as $withIcon) {
                        $history = [];
                        $stack = HandlerStack::create(new MockHandler([new Response(200, ['Content-Type' => 'application/json'], '{"success":true,"timestamp":"2026-09-22T00:00:00Z"}')]));
                        $stack->push(Middleware::history($history));
                        $notifications = new Notifications(new PushNotificationsApi(new Client(['handler' => $stack])));
                        $fields = ['title' => 'GitHub', 'subtitle' => 'Build status', 'tags' => ['ci']];
                        $icon = $withIcon ? 'https://example.com/github.png' : null;
                        if ($icon !== null) $fields['icon'] = $icon;
                        if ($level !== null) {
                            $fields['interruption_level'] = $level;
                        }
                        if ($form === 'named') {
                            $notifications->$method(title: 'GitHub', subtitle: 'Build status', tags: ['ci'], channels: ['ops'], icon: $icon, interruptionLevel: $level);
                        } elseif ($form === 'model') {
                            $modelFields = [...$fields, 'target' => ['channels' => ['ops']]];
                            if ($level !== null) {
                                unset($modelFields['interruption_level']);
                                $modelFields['interruptionLevel'] = $level;
                            }
                            $notifications->$method(new PushNotificationRequest($modelFields));
                        } else {
                            $notifications->$method([...$fields, 'channels' => ['ops']]);
                        }
                        $body = json_decode((string) $history[0]['request']->getBody(), true);
                        $this->assertEquals([...$fields, 'target' => ['channels' => ['ops']]], $body);
                    }
                }
            }
        }
    }

    public function testInvalidLevelNeverReachesTransport(): void
    {
        $api = $this->getMockBuilder(PushNotificationsApi::class)->disableOriginalConstructor()->onlyMethods(['sendPushNotification'])->getMock();
        $api->expects($this->never())->method('sendPushNotification');
        $this->expectException(\InvalidArgumentException::class);
        (new Notifications($api))->send(title: 'Test', interruptionLevel: 'timeSensitive');
    }

    public function testCriticalLevelNeverReachesTransport(): void
    {
        $api = $this->getMockBuilder(PushNotificationsApi::class)->disableOriginalConstructor()->onlyMethods(['sendPushNotification'])->getMock();
        $api->expects($this->never())->method('sendPushNotification');
        $this->expectException(\InvalidArgumentException::class);
        (new Notifications($api))->send(title: 'Test', interruptionLevel: 'critical');
    }
}
